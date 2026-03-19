"""ARIMA forecasting for HealthLogs.

Usage:
  python forecast_arima.py --series-key visits_total --horizon 12

The script rebuilds a daily series from the raw transactional tables,
fits an ARIMA model, and outputs a forecast plus plain-language summary data.
"""

import argparse
import json
import sys
import warnings
from datetime import date

import pandas as pd
import pymysql
from pmdarima import auto_arima

# Suppress statsmodels warnings
warnings.filterwarnings("ignore", category=FutureWarning, module="statsmodels")
warnings.filterwarnings("ignore", message="No supported index is available")


def get_connection():
    return pymysql.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="healthlogs",
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def load_series(series_key):
    if series_key == "visits_total":
        sql = """
            SELECT DATE(visit_datetime) AS series_date, COUNT(*) AS value
            FROM visits
            GROUP BY DATE(visit_datetime)
            ORDER BY series_date ASC
        """
        params = ()
    elif series_key == "medicine_total":
        sql = """
            SELECT DATE(transaction_datetime) AS series_date, COALESCE(SUM(ABS(quantity)), 0) AS value
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
            GROUP BY DATE(transaction_datetime)
            ORDER BY series_date ASC
        """
        params = ()
    else:
        sql = """
            SELECT series_date, value
            FROM timeseries_daily
            WHERE series_key = %s
            ORDER BY series_date ASC
        """
        params = (series_key,)

    with get_connection() as conn, conn.cursor() as cur:
        cur.execute(sql, params)
        rows = cur.fetchall()

    df = pd.DataFrame(rows)
    if df.empty:
        raise ValueError("No data for series_key")

    df["series_date"] = pd.to_datetime(df["series_date"])
    df.set_index("series_date", inplace=True)
    series = df["value"].astype(float).sort_index()

    # Missing days mean no recorded activity, so treat them as zero.
    full_index = pd.date_range(start=series.index.min(), end=series.index.max(), freq="D")
    series = series.reindex(full_index, fill_value=0.0)
    series.index.name = "series_date"

    return series


def forecast(series, horizon):
    training_series = series.tail(min(len(series), 180))
    seasonal = len(training_series) >= 56
    model = auto_arima(
        training_series,
        seasonal=seasonal,
        m=7 if seasonal else 1,
        error_action="ignore",
        suppress_warnings=True,
        stepwise=True,
        approximation=True,
        max_p=3,
        max_q=3,
        max_d=2,
        max_P=2,
        max_Q=2,
        max_D=1,
        with_intercept="auto",
    )

    preds, conf_int = model.predict(n_periods=horizon, return_conf_int=True, alpha=0.2)
    preds = [max(0.0, float(v)) for v in preds]
    conf_int = [[max(0.0, float(low)), max(0.0, float(high))] for low, high in conf_int]

    last_date = training_series.index.max()
    future_dates = pd.date_range(start=last_date + pd.Timedelta(days=1), periods=horizon, freq="D")

    return model, future_dates, preds, conf_int, len(training_series)


def build_summary(series_key, series, dates, preds, conf_int, model, training_points):
    recent_window = min(30, len(series))
    previous_window = min(recent_window, max(len(series) - recent_window, 0))

    recent_avg = float(series.tail(recent_window).mean())
    forecast_avg = float(sum(preds) / len(preds)) if preds else 0.0
    total_forecast = float(sum(preds))
    peak_value = max(preds) if preds else 0.0
    peak_index = preds.index(peak_value) if preds else 0
    peak_date = dates[peak_index].date().isoformat() if len(dates) else None

    if previous_window > 0:
        previous_avg = float(series.iloc[-(recent_window + previous_window):-recent_window].mean())
    else:
        previous_avg = recent_avg

    if previous_avg <= 0:
        change_ratio = 0.0 if forecast_avg <= 0 else 1.0
    else:
        change_ratio = (forecast_avg - previous_avg) / previous_avg

    if change_ratio > 0.05:
        trend_label = "higher than recent activity"
    elif change_ratio < -0.05:
        trend_label = "lower than recent activity"
    else:
        trend_label = "close to recent activity"

    metric_label = "visits" if series_key == "visits_total" else "medicine units"
    intro = (
        f"Expected average for the next {len(preds)} days is about "
        f"{forecast_avg:.1f} {metric_label} per day, which is {trend_label}."
    )

    history = [
        {
            "date": idx.date().isoformat(),
            "value": float(val),
        }
        for idx, val in series.tail(60).items()
    ]

    forecast_rows = [
        {
            "date": d.date().isoformat(),
            "value": float(v),
            "lower": ci[0],
            "upper": ci[1],
        }
        for d, v, ci in zip(dates, preds, conf_int)
    ]

    return {
        "intro": intro,
        "metric_label": metric_label,
        "recent_average": recent_avg,
        "previous_average": previous_avg,
        "forecast_average": forecast_avg,
        "expected_total": total_forecast,
        "peak_value": float(peak_value),
        "peak_date": peak_date,
        "trend_label": trend_label,
        "history_points": len(series),
        "training_points": training_points,
        "history_start": series.index.min().date().isoformat(),
        "history_end": series.index.max().date().isoformat(),
        "model": str(model.order),
        "seasonal_model": str(model.seasonal_order) if hasattr(model, "seasonal_order") else None,
        "history": history,
        "forecast_rows": forecast_rows,
    }


def main():
    try:
        parser = argparse.ArgumentParser()
        parser.add_argument("--series-key", required=True)
        parser.add_argument("--horizon", type=int, default=30)
        args = parser.parse_args()

        if args.horizon < 1:
            raise ValueError("Please choose at least 1 day for the forecast.")

        series = load_series(args.series_key)
        if len(series) < 14:
            raise ValueError("Not enough history yet. At least 14 days of data are required.")

        model, dates, preds, conf_int, training_points = forecast(series, args.horizon)
        summary = build_summary(args.series_key, series, dates, preds, conf_int, model, training_points)

        output = {
            "series_key": args.series_key,
            "generated_on": date.today().isoformat(),
            "horizon": args.horizon,
            "history": summary["history"],
            "forecast": summary["forecast_rows"],
            "summary": {
                "intro": summary["intro"],
                "metric_label": summary["metric_label"],
                "recent_average": summary["recent_average"],
                "previous_average": summary["previous_average"],
                "forecast_average": summary["forecast_average"],
                "expected_total": summary["expected_total"],
                "peak_value": summary["peak_value"],
                "peak_date": summary["peak_date"],
                "trend_label": summary["trend_label"],
                "history_points": summary["history_points"],
                "training_points": summary["training_points"],
                "history_start": summary["history_start"],
                "history_end": summary["history_end"],
                "model": summary["model"],
                "seasonal_model": summary["seasonal_model"],
            },
        }

        print(json.dumps(output))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
