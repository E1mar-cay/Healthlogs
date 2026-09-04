-- Migration to add MAE, RMSE, and MAPE error evaluation columns to forecast tables

-- 1. Add error metrics to forecast_runs (available for both ARIMA and Fast Forecast)
ALTER TABLE forecast_runs
  ADD COLUMN IF NOT EXISTS mae DECIMAL(12,4) NULL AFTER execution_time_seconds,
  ADD COLUMN IF NOT EXISTS rmse DECIMAL(12,4) NULL AFTER mae,
  ADD COLUMN IF NOT EXISTS mape DECIMAL(12,4) NULL AFTER rmse;

-- 2. Add error metrics to arima_parameters (for detailed statistical audit)
ALTER TABLE arima_parameters
  ADD COLUMN IF NOT EXISTS mae DECIMAL(12,4) NULL AFTER sigma2,
  ADD COLUMN IF NOT EXISTS rmse DECIMAL(12,4) NULL AFTER mae,
  ADD COLUMN IF NOT EXISTS mape DECIMAL(12,4) NULL AFTER rmse;
