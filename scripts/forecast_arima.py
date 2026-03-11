"""ARIMA forecasting template for HealthLogs.

Usage:
  python forecast_arima.py --series-key visits_total --horizon 12

The script reads time series from MySQL, fits an ARIMA model, and outputs JSON.
"""

import argparse
import json
from datetime import date

import pandas as pd
import pymysql
from pmdarima import auto_arima


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
    sql = """
        SELECT series_date, value
        FROM timeseries_daily
        WHERE series_key = %s
        ORDER BY series_date ASC
    """
    with get_connection() as conn, conn.cursor() as cur:
        cur.execute(sql, (series_key,))
        rows = cur.fetchall()
    df = pd.DataFrame(rows)
    if df.empty:
        raise ValueError("No data for series_key")
    df["series_date"] = pd.to_datetime(df["series_date"])
    df.set_index("series_date", inplace=True)
    return df["value"].astype(float)


def forecast(series, horizon):
    model = auto_arima(series, seasonal=False, error_action="ignore", suppress_warnings=True)
    preds = model.predict(n_periods=horizon)
    last_date = series.index.max().date()
    future_dates = pd.date_range(last_date, periods=horizon + 1, freq="D")[1:]
    return future_dates, preds


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--series-key", required=True)
    parser.add_argument("--horizon", type=int, default=30)
    args = parser.parse_args()

    series = load_series(args.series_key)
    dates, preds = forecast(series, args.horizon)

    output = {
        "series_key": args.series_key,
        "generated_on": date.today().isoformat(),
        "horizon": args.horizon,
        "forecast": [
            {"date": d.date().isoformat(), "value": float(v)}
            for d, v in zip(dates, preds)
        ],
    }

    print(json.dumps(output))


if __name__ == "__main__":
    main()
