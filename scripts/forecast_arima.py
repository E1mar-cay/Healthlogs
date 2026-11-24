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
import numpy as np
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


def trim_trailing_sparse_gap(series: pd.Series) -> pd.Series:
    """Trim trailing unseeded sparse/partial days so ARIMA does not anchor to an incomplete final day."""
    recent = series.tail(min(len(series), 180))
    n = len(series)
    if len(recent) < 45:
        return series

    non_zero = recent[recent > 0]
    if len(non_zero) < 30:
        return series

    median_active = float(non_zero.median())
    if median_active < 5.0:
        return series

    threshold = median_active * 0.45
    rolling_7 = series.rolling(window=7, min_periods=1).mean()

    cut_idx = n - 1
    vals = series.values
    r_vals = rolling_7.values
    while cut_idx >= (n - len(recent) + 30):
        if r_vals[cut_idx] >= threshold and vals[cut_idx] >= threshold:
            break
        cut_idx -= 1

    if 30 <= cut_idx < n - 1:
        return series.iloc[: cut_idx + 1]
    return series


def load_series(series_key, source="operational"):
    if source == "demo":
        if series_key not in {"visits_total", "medicine_total"}:
            raise ValueError("Demo forecasts are available only for visits and medicine demand.")
        sql = """
            SELECT series_date, value
            FROM timeseries_daily
            WHERE series_key = %s
            ORDER BY series_date ASC
        """
        params = ("demo_" + series_key,)
    elif series_key == "visits_total":
        sql = """
            SELECT DATE(v_date) AS series_date, COUNT(*) AS value
            FROM (
                SELECT visit_datetime AS v_date FROM visits WHERE visit_type = 'general'
                UNION ALL
                SELECT visit_datetime AS v_date FROM prenatal_visits
                UNION ALL
                SELECT visit_datetime AS v_date FROM postnatal_visits
                UNION ALL
                SELECT CAST(administered_on AS DATETIME) AS v_date FROM immunization_records
                UNION ALL
                SELECT CAST(visit_date AS DATETIME) AS v_date FROM fp_visits
                UNION ALL
                SELECT CAST(visit_date AS DATETIME) AS v_date FROM ncd_visits
            ) all_v
            WHERE DATE(v_date) <= CURDATE()
            GROUP BY DATE(v_date)
            ORDER BY series_date ASC
        """
        params = ()
    elif series_key == "medicine_total":
        sql = """
            SELECT DATE(transaction_datetime) AS series_date, COALESCE(SUM(ABS(quantity)), 0) AS value
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
              AND transaction_datetime < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
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

    # Keep track of absent dates because a missing record is not proof of zero activity.
    full_index = pd.date_range(start=series.index.min(), end=series.index.max(), freq="D")
    missing_days = len(full_index.difference(series.index))
    series = series.reindex(full_index, fill_value=0.0)
    series.index.name = "series_date"
    series.attrs["missing_day_count"] = missing_days
    series.attrs["source"] = source

    return trim_trailing_sparse_gap(series)


def fit_arima(training_series):
    seasonal = len(training_series) >= 56
    return auto_arima(
        training_series,
        seasonal=seasonal,
        m=7 if seasonal else 1,
        d=0,
        D=1 if seasonal else 0,
        error_action="ignore",
        suppress_warnings=True,
        stepwise=True,
        approximation=True,
        max_p=3,
        max_q=3,
        max_P=2,
        max_Q=2,
        with_intercept=True,
    )


def compute_error_metrics(actual, predicted):
    actual = np.asarray(actual, dtype=float)
    predicted = np.maximum(0.0, np.asarray(predicted, dtype=float))
    errors = actual - predicted
    return {
        "mae": float(np.mean(np.abs(errors))),
        "rmse": float(np.sqrt(np.mean(errors ** 2))),
    }


def rolling_backtest(series, horizon):
    """Evaluate equal-horizon rolling holdouts against a same-weekday baseline."""
    history = series.tail(min(len(series), 180))
    minimum_training_points = 28
    fold_count = min(3, (len(history) - minimum_training_points) // horizon)
    if fold_count < 1:
        return {
            "evaluation_method": "rolling_origin",
            "status": "insufficient_history",
            "message": (
                f"At least {minimum_training_points + horizon} daily points are needed "
                "to evaluate this forecast horizon."
            ),
            "folds": 0,
            "test_points": 0,
            "horizon": horizon,
            "mae": None,
            "rmse": None,
            "baseline_mae": None,
            "baseline_rmse": None,
            "mae_skill_percent": None,
        }

    first_origin = len(history) - fold_count * horizon
    actual_values = []
    arima_values = []
    baseline_values = []

    for fold in range(fold_count):
        origin = first_origin + fold * horizon
        training = history.iloc[:origin].tail(180)
        actual = history.iloc[origin:origin + horizon].to_numpy(dtype=float)

        model = fit_arima(training)
        arima_prediction = np.maximum(0.0, model.predict(n_periods=horizon))
        weekly_pattern = training.iloc[-7:].to_numpy(dtype=float)
        baseline_prediction = np.resize(weekly_pattern, horizon)

        actual_values.extend(actual)
        arima_values.extend(arima_prediction)
        baseline_values.extend(baseline_prediction)

    arima_metrics = compute_error_metrics(actual_values, arima_values)
    baseline_metrics = compute_error_metrics(actual_values, baseline_values)
    baseline_mae = baseline_metrics["mae"]
    skill = (
        ((baseline_mae - arima_metrics["mae"]) / baseline_mae) * 100.0
        if baseline_mae > 0
        else None
    )

    return {
        "evaluation_method": "rolling_origin",
        "status": "evaluated",
        "folds": fold_count,
        "test_points": len(actual_values),
        "horizon": horizon,
        "mae": round(arima_metrics["mae"], 4),
        "rmse": round(arima_metrics["rmse"], 4),
        "baseline_name": "same weekday last week",
        "baseline_mae": round(baseline_metrics["mae"], 4),
        "baseline_rmse": round(baseline_metrics["rmse"], 4),
        "mae_skill_percent": round(skill, 2) if skill is not None else None,
        "assessment": (
            "better_than_baseline"
            if arima_metrics["mae"] < baseline_mae
            else (
                "tie_with_baseline"
                if arima_metrics["mae"] == baseline_mae
                else "not_better_than_baseline"
            )
        ),
    }


def forecast(series, horizon):
    training_series = series.tail(min(len(series), 180))
    model = fit_arima(training_series)
    preds, conf_int = model.predict(n_periods=horizon, return_conf_int=True, alpha=0.2)
    preds = [max(0.0, float(v)) for v in preds]
    conf_int = [[max(0.0, float(low)), max(0.0, float(high))] for low, high in conf_int]

    last_date = max(training_series.index.max(), pd.Timestamp(date.today()))
    future_dates = pd.date_range(start=last_date + pd.Timedelta(days=1), periods=horizon, freq="D")

    metrics = rolling_backtest(series, horizon)

    return model, future_dates, preds, conf_int, len(training_series), metrics


def build_summary(series_key, series, dates, preds, conf_int, model, training_points):
    recent_window = min(30, len(series))
    previous_window = min(recent_window, max(len(series) - recent_window, 0))

    recent_avg = float(series.tail(recent_window).mean())
    forecast_avg = sum(preds) / len(preds) if preds else 0.0
    total_forecast = sum(preds)
    peak_value = max(preds) if preds else 0.0
    peak_index = preds.index(peak_value) if preds else 0
    peak_date = dates[peak_index].date().isoformat() if len(dates) else None

    if previous_window > 0:
        previous_avg = float(series.iloc[-(recent_window + previous_window):-recent_window].mean())
    else:
        previous_avg = recent_avg

    if recent_avg <= 0:
        change_ratio = 0.0 if forecast_avg <= 0 else 1.0
    else:
        change_ratio = (forecast_avg - recent_avg) / recent_avg

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
            "value": round(float(v), 2),
            "lower": round(ci[0], 2),
            "upper": round(ci[1], 2),
        }
        for d, v, ci in zip(dates, preds, conf_int)
    ]

    return {
        "intro": intro,
        "metric_label": metric_label,
        "recent_average": round(recent_avg, 2),
        "previous_average": round(previous_avg, 2),
        "forecast_average": round(forecast_avg, 2),
        "expected_total": round(total_forecast, 2),
        "peak_value": round(float(peak_value), 2),
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
        parser.add_argument("--source", choices=("operational", "demo"), default="operational")
        args = parser.parse_args()

        if args.horizon < 1:
            raise ValueError("Please choose at least 1 day for the forecast.")
        if args.series_key not in {"visits_total", "medicine_total"}:
            raise ValueError("Unsupported forecast series.")

        series = load_series(args.series_key, args.source)
        if len(series) < 14:
            raise ValueError("Not enough history yet. At least 14 days of data are required.")

        model, dates, preds, conf_int, training_points, metrics = forecast(series, args.horizon)
        summary = build_summary(args.series_key, series, dates, preds, conf_int, model, training_points)

        aic = float(model.aic())
        bic = float(model.bic())
        log_likelihood = float(model.arima_res_.llf) if hasattr(model, 'arima_res_') and hasattr(model.arima_res_, 'llf') else None

        sigma2 = None
        if hasattr(model, 'arima_res_') and hasattr(model.arima_res_, 'params'):
            if 'sigma2' in model.arima_res_.params:
                sigma2 = float(model.arima_res_.params['sigma2'])
            elif 'sigma2' in model.arima_res_.param_names:
                try:
                    idx = model.arima_res_.param_names.index('sigma2')
                    sigma2 = float(model.arima_res_.params[idx])
                except Exception:
                    pass

        params = {}
        if hasattr(model, 'arima_res_') and hasattr(model.arima_res_, 'params') and hasattr(model.arima_res_, 'param_names'):
            try:
                for name, val in zip(model.arima_res_.param_names, model.arima_res_.params):
                    params[name] = float(val)
            except Exception:
                pass

        output = {
            "series_key": args.series_key,
            "data_source": args.source,
            "is_demo": args.source == "demo",
            "generated_on": date.today().isoformat(),
            "horizon": args.horizon,
            "history": summary["history"],
            "forecast": summary["forecast_rows"],
            "metrics": metrics,
            "data_quality": {
                "imputed_zero_days": int(series.attrs.get("missing_day_count", 0)),
                "missing_day_handling": "Missing dates are treated as zero activity; verify that no record means no activity.",
            },
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
                "mae": metrics["mae"],
                "rmse": metrics["rmse"],
            },
            "diagnostics": {
                "aic": aic,
                "bic": bic,
                "log_likelihood": log_likelihood,
                "sigma2": sigma2,
                "mae": metrics["mae"],
                "rmse": metrics["rmse"],
                "baseline_mae": metrics["baseline_mae"],
                "baseline_rmse": metrics["baseline_rmse"],
                "mae_skill_percent": metrics["mae_skill_percent"],
                "assessment": metrics.get("assessment"),
                "params": params
            }
        }

        print(json.dumps(output))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
