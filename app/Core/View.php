<?php

class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/layout.php';
    }
}
