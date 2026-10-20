<?php

require_once __DIR__ . '/../Core/PythonRunner.php';
require_once __DIR__ . '/../Core/ArimaService.php';

class ForecastController extends Controller
{
    public function index(): void
    {
        $this->view('forecast');
    }

    public function run(): void
    {
        global $pdo;
        $seriesKey = $_POST['series_key'] ?? 'visits_total';
        $horizon = (int)($_POST['horizon'] ?? 30);

        if (PythonRunner::isAvailable(__DIR__ . '/../..')) {
            $python = PythonRunner::executable(__DIR__ . '/../..');
            $script = __DIR__ . '/../../scripts/forecast_arima.py';
            $cmd = PythonRunner::buildCommand($python, $script, [
                '--series-key' => $seriesKey,
                '--horizon' => (string)$horizon,
            ]);

            $output = @shell_exec($cmd);
            if ($output) {
                $data = json_decode($output, true);
                if (is_array($data) && empty($data['error'])) {
                    $this->json($data);
                    return;
                }
            }
        }

        try {
            $data = ArimaService::forecast($pdo, $seriesKey, $horizon);
            $this->json($data);
        } catch (Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}

