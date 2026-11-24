"""Flask API wrapper for synthetic-demo ARIMA forecasting.

Run:
  python forecast_api.py --host 127.0.0.1 --port 5005

Request:
  POST /forecast (reads demo-prefixed rows from timeseries_daily only)
  {
    "series_key": "visits_total",
    "horizon": 30
  }
"""

import argparse
from datetime import date

from flask import Flask, jsonify, request
import pandas as pd
import pymysql
from forecast_arima import fit_arima, rolling_backtest


app = Flask(__name__)


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
    if series_key not in {"visits_total", "medicine_total"}:
        raise ValueError("Unsupported demo series_key")

    sql = """
        SELECT series_date, value
        FROM timeseries_daily
        WHERE series_key = %s
        ORDER BY series_date ASC
    """
    with get_connection() as conn, conn.cursor() as cur:
        cur.execute(sql, ("demo_" + series_key,))
        rows = cur.fetchall()
    df = pd.DataFrame(rows)
    if df.empty:
        raise ValueError("No data for series_key")
    df["series_date"] = pd.to_datetime(df["series_date"])
    df.set_index("series_date", inplace=True)
    series = df["value"].astype(float)
    full_index = pd.date_range(start=series.index.min(), end=series.index.max(), freq="D")
    series.attrs["missing_day_count"] = len(full_index.difference(series.index))
    return series.reindex(full_index, fill_value=0.0)


def forecast(series, horizon):
    model = fit_arima(series.tail(min(len(series), 180)))
    preds = model.predict(n_periods=horizon)
    last_date = series.index.max().date()
    future_dates = pd.date_range(last_date, periods=horizon + 1, freq="D")[1:]
    metrics = rolling_backtest(series, horizon)
    return future_dates, preds, metrics


@app.post("/forecast")
def forecast_endpoint():
    payload = request.get_json(silent=True) or {}
    series_key = payload.get("series_key")
    try:
        horizon = int(payload.get("horizon", 30))
    except (TypeError, ValueError):
        return jsonify({"error": "horizon must be a whole number"}), 400

    if not series_key:
        return jsonify({"error": "series_key is required"}), 400
    if horizon < 1 or horizon > 90:
        return jsonify({"error": "horizon must be between 1 and 90 days"}), 400

    try:
        series = load_series(series_key)
        if len(series) < 14:
            raise ValueError("Not enough history yet. At least 14 days of data are required.")
        dates, preds, metrics = forecast(series, horizon)
    except Exception as exc:
        return jsonify({"error": str(exc)}), 400

    output = {
        "series_key": series_key,
        "data_source": "demo",
        "is_demo": True,
        "generated_on": date.today().isoformat(),
        "horizon": horizon,
        "metrics": metrics,
        "data_quality": {
            "imputed_zero_days": int(series.attrs.get("missing_day_count", 0)),
            "missing_day_handling": "Missing dates are treated as zero activity; verify that no record means no activity.",
        },
        "forecast": [
            {"date": d.date().isoformat(), "value": float(v)}
            for d, v in zip(dates, preds)
        ],
    }

    return jsonify(output)


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=5005)
    args = parser.parse_args()

    app.run(host=args.host, port=args.port, debug=False)
