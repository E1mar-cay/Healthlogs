<?php

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

        $script = __DIR__ . '/../../scripts/forecast_arima.py';
        $cmd = 'python ' . escapeshellarg($script) .
            ' --series-key ' . escapeshellarg($seriesKey) .
            ' --horizon ' . escapeshellarg((string)$horizon);

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
