<?php

require_once __DIR__ . '/Database.php';

class ForecastLogger
{
    private static ?PDO $db = null;

    private static function init(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::connection();
        }
        return self::$db;
    }

    /**
     * Start a new forecast run logging session
     */
    public static function startRun(string $seriesKey, int $horizon, string $modelType): int
    {
        $db = self::init();
        $sql = "INSERT INTO forecast_runs (series_key, horizon, model_type, status) 
                VALUES (?, ?, ?, 'failed')";
        $stmt = $db->prepare($sql);
        $stmt->execute([$seriesKey, $horizon, $modelType]);
        return (int)$db->lastInsertId();
    }

    /**
     * Log a successful forecast run with its results and diagnostics
     */
    public static function logSuccess(
        int $runId,
        int $historyPoints,
        int $trainingPoints,
        float $executionTime,
        array $forecastRows,
        ?array $diagnostics = null
    ): void {
        $db = self::init();
        
        try {
            $db->beginTransaction();

            // 1. Update forecast_runs log details
            $sql = "UPDATE forecast_runs 
                    SET status = 'success', 
                        history_points = ?, 
                        training_points = ?, 
                        execution_time_seconds = ?,
                        error_message = NULL
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$historyPoints, $trainingPoints, $executionTime, $runId]);

            // 2. Save ARIMA parameters if provided
            if ($diagnostics !== null) {
                // Get model order and seasonal order from parameters if present or leave blank
                $modelOrder = isset($diagnostics['model_order']) ? $diagnostics['model_order'] : null;
                $seasonalOrder = isset($diagnostics['seasonal_order']) ? $diagnostics['seasonal_order'] : null;
                
                $aic = isset($diagnostics['aic']) ? (float)$diagnostics['aic'] : null;
                $bic = isset($diagnostics['bic']) ? (float)$diagnostics['bic'] : null;
                $logLikelihood = isset($diagnostics['log_likelihood']) ? (float)$diagnostics['log_likelihood'] : null;
                $sigma2 = isset($diagnostics['sigma2']) ? (float)$diagnostics['sigma2'] : null;
                
                $paramsJson = isset($diagnostics['params']) ? json_encode($diagnostics['params']) : null;

                $sqlParam = "INSERT INTO arima_parameters (run_id, model_order, seasonal_order, aic, bic, log_likelihood, sigma2, parameters_json) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtParam = $db->prepare($sqlParam);
                $stmtParam->execute([
                    $runId,
                    $modelOrder,
                    $seasonalOrder,
                    $aic,
                    $bic,
                    $logLikelihood,
                    $sigma2,
                    $paramsJson
                ]);
            }

            // 3. Save forecast predictions
            if (!empty($forecastRows)) {
                $sqlResult = "INSERT INTO forecast_results (run_id, forecast_date, forecast_value, lower_bound, upper_bound) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmtResult = $db->prepare($sqlResult);
                
                foreach ($forecastRows as $row) {
                    $stmtResult->execute([
                        $runId,
                        $row['date'],
                        $row['value'],
                        $row['lower'],
                        $row['upper']
                    ]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // If the transaction fails, fall back to marking the run as failed
            self::logFailure($runId, "Database save transaction failed: " . $e->getMessage(), $executionTime);
        }
    }

    /**
     * Log a failed forecast execution
     */
    public static function logFailure(int $runId, string $errorMessage, float $executionTime): void
    {
        $db = self::init();
        $sql = "UPDATE forecast_runs 
                SET status = 'failed', 
                    error_message = ?, 
                    execution_time_seconds = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$errorMessage, $executionTime, $runId]);
    }

    /**
     * Get recent forecast runs
     */
    public static function getRecentLogs(int $limit = 10): array
    {
        $db = self::init();
        $sql = "SELECT r.*, p.model_order, p.seasonal_order, p.aic, p.bic 
                FROM forecast_runs r
                LEFT JOIN arima_parameters p ON r.id = p.run_id
                ORDER BY r.created_at DESC 
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get full details of a specific forecast run
     */
    public static function getRunDetails(int $runId): ?array
    {
        $db = self::init();
        
        // 1. Fetch run info
        $sqlRun = "SELECT * FROM forecast_runs WHERE id = ?";
        $stmtRun = $db->prepare($sqlRun);
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch(PDO::FETCH_ASSOC);
        
        if (!$run) {
            return null;
        }

        // 2. Fetch parameters
        $sqlParams = "SELECT * FROM arima_parameters WHERE run_id = ?";
        $stmtParams = $db->prepare($sqlParams);
        $stmtParams->execute([$runId]);
        $params = $stmtParams->fetch(PDO::FETCH_ASSOC) ?: null;

        // 3. Fetch results
        $sqlResults = "SELECT * FROM forecast_results WHERE run_id = ? ORDER BY forecast_date ASC";
        $stmtResults = $db->prepare($sqlResults);
        $stmtResults->execute([$runId]);
        $results = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

        return [
            'run' => $run,
            'parameters' => $params,
            'results' => $results
        ];
    }
}
