<?php

class PythonRunner
{
    private static ?string $cachedExecutable = null;

    public static function executable(string $projectRoot): string
    {
        if (self::$cachedExecutable !== null) {
            return self::$cachedExecutable;
        }

        $candidates = [];

        // 1. Configured via .env or server environment
        $configured = getenv('PYTHON_PATH') ?: ($_ENV['PYTHON_PATH'] ?? '');
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        // 2. Project local virtual environments (.venv, venv)
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        } else {
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
        }

        // 3. Known Windows user / system locations
        if (PHP_OS_FAMILY === 'Windows') {
            $localAppData = getenv('LOCALAPPDATA') ?: '';
            if ($localAppData !== '') {
                $pyPaths = glob($localAppData . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'Python' . DIRECTORY_SEPARATOR . 'Python*' . DIRECTORY_SEPARATOR . 'python.exe') ?: [];
                foreach ($pyPaths as $p) {
                    $candidates[] = $p;
                }
            }

            // Check user profiles in C:\Users\
            $userPyPaths = glob('C:\\Users\\*\\AppData\\Local\\Programs\\Python\\Python*\\python.exe') ?: [];
            foreach ($userPyPaths as $p) {
                $candidates[] = $p;
            }

            // Check standard program files directories
            $pfPaths = glob('C:\\Program Files\\Python*\\python.exe') ?: [];
            foreach ($pfPaths as $p) {
                $candidates[] = $p;
            }
            $pf86Paths = glob('C:\\Program Files (x86)\\Python*\\python.exe') ?: [];
            foreach ($pf86Paths as $p) {
                $candidates[] = $p;
            }
            $rootPy = glob('C:\\Python*\\python.exe') ?: [];
            foreach ($rootPy as $p) {
                $candidates[] = $p;
            }

            // Windows Py launcher
            $candidates[] = 'py';
            $candidates[] = 'C:\\Windows\\py.exe';
        }

        // 4. Generic command names
        $candidates[] = 'python';
        $candidates[] = 'python3';

        // Check each candidate
        foreach (array_unique($candidates) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if (self::canRun($candidate)) {
                self::$cachedExecutable = $candidate;
                return $candidate;
            }
        }

        // Fallback default
        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    public static function canRun(string $command): bool
    {
        // If it looks like a path, check file existence first
        if (str_contains($command, DIRECTORY_SEPARATOR) || str_contains($command, '/') || str_ends_with(strtolower($command), '.exe')) {
            if (!file_exists($command)) {
                return false;
            }
        }

        $output = [];
        $status = 1;
        $redirect = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
        $testCmd = escapeshellarg($command) . ' -c "import sys; sys.exit(0)" ' . $redirect;
        @exec($testCmd, $output, $status);

        return $status === 0;
    }

    public static function buildCommand(string $python, string $script, array $args = []): string
    {
        $parts = [escapeshellarg($python), escapeshellarg($script)];
        foreach ($args as $key => $val) {
            if (is_int($key)) {
                $parts[] = escapeshellarg((string)$val);
            } else {
                $parts[] = $key;
                if ($val !== null && $val !== '') {
                    $parts[] = escapeshellarg((string)$val);
                }
            }
        }

        return implode(' ', $parts) . ' 2>&1';
    }
}