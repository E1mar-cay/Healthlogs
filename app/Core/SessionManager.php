<?php
// app/Core/SessionManager.php - Database-backed Sessions for Cloud & Serverless compatibility

class SessionManager implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function start(PDO $pdo): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Ensure session cookies are valid across entire domain
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_samesite', 'Lax');

        $handler = new self($pdo);
        session_set_save_handler($handler, true);
        session_start();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string)$data : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare("REPLACE INTO php_sessions (id, data, last_accessed) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, time()]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $oldest = time() - $max_lifetime;
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE last_accessed < ?");
            $stmt->execute([$oldest]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}
