<?php

require_once __DIR__ . '/../Core/PythonRunner.php';

class ForecastController extends Controller
{
    public function index(): void
    {
        $this->view('forecast');
    }

    public function run(): void
    {
        $seriesKey = $_POST['series_key'] ?? 'visits_total';
        $horizon = (int)($_POST['horizon'] ?? 30);

        $python = PythonRunner::executable(__DIR__ . '/../..');
        $script = __DIR__ . '/../../scripts/forecast_arima.py';
        $cmd = PythonRunner::buildCommand($python, $script, [
            '--series-key' => $seriesKey,
            '--horizon' => (string)$horizon,
        ]);

        $output = shell_exec($cmd);
        if (!$output) {
            $this->json(['error' => 'No output from ARIMA script.'], 500);
            return;
        }

        $data = json_decode($output, true);
        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON from ARIMA script.'], 500);
            return;
        }

        if (!empty($data['error'])) {
            $this->json(['error' => $data['error']], 500);
            return;
        }

        $this->json($data);
    }
}
