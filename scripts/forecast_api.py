"""Flask API wrapper for HealthLogs ARIMA forecasting.

Run:
  python forecast_api.py --host 127.0.0.1 --port 5005

Request:
  POST /forecast
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
from pmdarima import auto_arima


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


import numpy as np


def compute_evaluation_metrics(actual, fitted):
    actual = np.asarray(actual, dtype=float)
    fitted = np.maximum(0.0, np.asarray(fitted, dtype=float))
    errors = actual - fitted
    mae = float(np.mean(np.abs(errors))) if len(actual) > 0 else 0.0
    rmse = float(np.sqrt(np.mean(errors ** 2))) if len(actual) > 0 else 0.0
    nz = actual > 0
    mape = float(np.mean(np.abs(errors[nz] / actual[nz])) * 100.0) if np.any(nz) else (0.0 if np.all(fitted == 0) else 100.0)
    rating = "High Accuracy" if mape < 10 else ("Good Fit" if mape < 20 else ("Reasonable" if mape < 50 else "High Variance"))
    return {"mae": round(mae, 4), "rmse": round(rmse, 4), "mape": round(mape, 2), "accuracy_rating": rating}


def forecast(series, horizon):
    model = auto_arima(series, seasonal=False, error_action="ignore", suppress_warnings=True)
    preds = model.predict(n_periods=horizon)
    last_date = series.index.max().date()
    future_dates = pd.date_range(last_date, periods=horizon + 1, freq="D")[1:]
    try:
        in_sample = model.predict_in_sample()
    except Exception:
        in_sample = np.full(len(series), float(series.mean()))
    metrics = compute_evaluation_metrics(series.values, in_sample)
    return future_dates, preds, metrics


@app.post("/forecast")
def forecast_endpoint():
    payload = request.get_json(silent=True) or {}
    series_key = payload.get("series_key")
    horizon = int(payload.get("horizon", 30))

    if not series_key:
        return jsonify({"error": "series_key is required"}), 400

    try:
        series = load_series(series_key)
        dates, preds, metrics = forecast(series, horizon)
    except Exception as exc:
        return jsonify({"error": str(exc)}), 500

    output = {
        "series_key": series_key,
        "generated_on": date.today().isoformat(),
        "horizon": horizon,
        "metrics": metrics,
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
