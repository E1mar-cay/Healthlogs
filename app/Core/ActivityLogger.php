<?php

class ActivityLogger
{
    private static ?PDO $pdo = null;

    /**
     * Set or initialize the PDO instance for logging.
     */
    public static function init(?PDO $pdo = null): void
    {
        if ($pdo !== null) {
            self::$pdo = $pdo;
        } elseif (self::$pdo === null) {
            require_once __DIR__ . '/../../config/db.php';
            self::$pdo = $GLOBALS['pdo'] ?? null;
        }
    }

    /**
     * Get the active PDO instance safely.
     */
    private static function getDb(): ?PDO
    {
        if (self::$pdo === null) {
            self::init();
        }
        return self::$pdo;
    }

    /**
     * Retrieve the client IP address.
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return substr(trim((string)$_SERVER['HTTP_CLIENT_IP']), 0, 45);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            return substr(trim($ips[0]), 0, 45);
        }
        return substr(trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')), 0, 45);
    }

    /**
     * Retrieve the user agent string.
     */
    public static function getUserAgent(): string
    {
        return substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown/CLI')), 0, 255);
    }

    /**
     * Main logging method. Fault-tolerant so it never breaks critical business flow.
     */
    public static function log(string $module, string $action, string $description, array $options = []): ?int
    {
        try {
            $db = self::getDb();
            if (!$db) {
                return null;
            }

            // Determine user context
            $userId = $options['user_id'] ?? ($_SESSION['user_id'] ?? null);
            $username = $options['username'] ?? ($_SESSION['username'] ?? null);
            $userRole = $options['user_role'] ?? ($_SESSION['role'] ?? null);

            // If user_id is provided but username is missing, fetch username from DB
            if ($userId && !$username) {
                try {
                    $uStmt = $db->prepare("SELECT u.username, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?");
                    $uStmt->execute([$userId]);
                    if ($uRow = $uStmt->fetch(PDO::FETCH_ASSOC)) {
                        $username = $uRow['username'];
                        $userRole = $userRole ?: $uRow['role_name'];
                    }
                } catch (Throwable $ignore) {}
            }

            $entityType = $options['entity_type'] ?? null;
            $entityId = isset($options['entity_id']) ? (string)$options['entity_id'] : null;
            $ipAddress = $options['ip_address'] ?? self::getClientIp();
            $userAgent = $options['user_agent'] ?? self::getUserAgent();

            $details = null;
            if (isset($options['details'])) {
                if (is_string($options['details'])) {
                    $details = $options['details'];
                } else {
                    $details = json_encode($options['details'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            $createdAt = $options['created_at'] ?? date('Y-m-d H:i:s');

            $sql = "INSERT INTO activity_logs (
                        user_id, username, user_role, module, action,
                        entity_type, entity_id, description, details,
                        ip_address, user_agent, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId ? (int)$userId : null,
                $username ? substr((string)$username, 0, 60) : null,
                $userRole ? substr((string)$userRole, 0, 50) : null,
                substr($module, 0, 50),
                substr($action, 0, 50),
                $entityType ? substr($entityType, 0, 60) : null,
                $entityId ? substr($entityId, 0, 60) : null,
                substr($description, 0, 255),
                $details,
                $ipAddress,
                $userAgent,
                $createdAt
            ]);

            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            error_log("ActivityLogger error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Log authentication events (login, logout, failed login).
     */
    public static function logAuth(string $action, string $username, bool $success = true, array $extra = []): ?int
    {
        $desc = $success
            ? ($action === 'logout' ? "User '{$username}' logged out" : "User '{$username}' logged in successfully")
            : "Failed login attempt for username '{$username}'";

        $details = array_merge([
            'username' => $username,
            'success' => $success,
            'ip' => self::getClientIp()
        ], $extra);

        return self::log('auth', $action, $desc, [
            'username' => $username,
            'user_role' => $extra['role'] ?? null,
            'user_id' => $extra['user_id'] ?? null,
            'entity_type' => 'users',
            'entity_id' => $extra['user_id'] ?? null,
            'details' => $details
        ]);
    }

    /**
     * Log medicine inventory audit events (additions, stock updates, issuances, returns, adjustments, deletions).
     */
    public static function logInventory(string $action, string $description, ?string $entityType = 'medicine_transactions', ?string $entityId = null, array $details = []): ?int
    {
        return self::log('inventory', $action, $description, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'created_at' => $details['transaction_datetime'] ?? null
        ]);
    }

    /**
     * Log user management operations.
     */
    public static function logUser(string $action, string $description, ?int $targetUserId = null, array $details = []): ?int
    {
        return self::log('users', $action, $description, [
            'entity_type' => 'users',
            'entity_id' => $targetUserId ? (string)$targetUserId : null,
            'details' => $details
        ]);
    }

    /**
     * Log patient records and clinical visits.
     */
    public static function logClinical(string $module, string $action, string $description, ?int $patientId = null, array $details = []): ?int
    {
        return self::log($module, $action, $description, [
            'entity_type' => 'patients',
            'entity_id' => $patientId ? (string)$patientId : null,
            'details' => $details
        ]);
    }

    /**
     * Helper to compute current stock on-hand for a given medicine.
     */
    public static function getMedicineStock(int $medicineId): int
    {
        $db = self::getDb();
        if (!$db || $medicineId <= 0) {
            return 0;
        }
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM medicine_transactions WHERE medicine_id = ?");
        $stmt->execute([$medicineId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper to get medicine name and details by ID.
     */
    public static function getMedicineInfo(int $medicineId): ?array
    {
        $db = self::getDb();
        if (!$db || $medicineId <= 0) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmt->execute([$medicineId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Helper to get batch info by ID.
     */
    public static function getBatchInfo(int $batchId): ?array
    {
        $db = self::getDb();
        if (!$db || $batchId <= 0) {
            return null;
        }
        $stmt = $db->prepare("SELECT b.*, m.name AS medicine_name FROM medicine_batches b JOIN medicines m ON m.id = b.medicine_id WHERE b.id = ?");
        $stmt->execute([$batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Backfill initial audit trail logs from existing medicine_transactions if activity_logs has no inventory logs.
     * Guarantees complete historical audit trail for pre-existing system data.
     */
    public static function backfillFromTransactions(): int
    {
        $db = self::getDb();
        if (!$db) {
            return 0;
        }

        // Check if inventory logs already exist
        $countStmt = $db->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory'");
        if ((int)$countStmt->fetchColumn() > 0) {
            return 0;
        }

        // Fetch transactions with medicine & user details
        $sql = "SELECT t.*, m.name AS medicine_name, m.unit, b.batch_no, u.username, u.full_name, r.name AS role_name
                FROM medicine_transactions t
                JOIN medicines m ON m.id = t.medicine_id
                LEFT JOIN medicine_batches b ON b.id = t.batch_id
                LEFT JOIN users u ON u.id = t.recorded_by
                LEFT JOIN roles r ON r.id = u.role_id
                ORDER BY t.transaction_datetime ASC, t.id ASC";

        $stmt = $db->query($sql);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($transactions)) {
            return 0;
        }

        $inserted = 0;
        $runningStock = [];

        foreach ($transactions as $t) {
            $medId = (int)$t['medicine_id'];
            $oldStock = $runningStock[$medId] ?? 0;
            $qty = (int)$t['quantity'];
            $newStock = $oldStock + $qty;
            $runningStock[$medId] = $newStock;

            $action = 'stock_receive';
            $desc = "Stock received: " . abs($qty) . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];

            if ($t['transaction_type'] === 'dispensed') {
                $action = 'issuance';
                $desc = "Stock released/issued: " . abs($qty) . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];
            } elseif ($t['transaction_type'] === 'adjustment') {
                $action = 'adjustment';
                $desc = "Inventory adjustment: " . ($qty >= 0 ? "+{$qty}" : "{$qty}") . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];
            } elseif ($t['transaction_type'] === 'expired') {
                $action = 'adjustment';
                $desc = "Expired stock written off: " . abs($qty) . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];
            } elseif ($t['transaction_type'] === 'returned') {
                $action = 'return';
                $desc = "Medicine returned to stock: " . abs($qty) . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];
            } elseif ($t['transaction_type'] === 'received') {
                $action = 'stock_receive';
                $desc = "Stock received: " . abs($qty) . " " . ($t['unit'] ?? 'units') . " of " . $t['medicine_name'];
            }

            if (!empty($t['reference'])) {
                $desc .= " (Ref: " . $t['reference'] . ")";
            }

            $details = [
                'medicine_id' => $medId,
                'medicine_name' => $t['medicine_name'],
                'unit' => $t['unit'],
                'batch_id' => $t['batch_id'],
                'batch_no' => $t['batch_no'] ?? null,
                'transaction_type' => $t['transaction_type'],
                'quantity' => $qty,
                'abs_quantity' => abs($qty),
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reference' => $t['reference'],
                'notes' => $t['notes'],
                'recorded_by' => $t['recorded_by'],
                'recorded_by_name' => $t['full_name'] ?: ($t['username'] ?: 'System'),
                'transaction_datetime' => $t['transaction_datetime']
            ];

            self::log('inventory', $action, $desc, [
                'user_id' => $t['recorded_by'],
                'username' => $t['username'] ?: 'system',
                'user_role' => $t['role_name'] ?: 'system',
                'entity_type' => 'medicine_transactions',
                'entity_id' => (string)$t['id'],
                'details' => $details,
                'created_at' => $t['transaction_datetime'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Migration'
            ]);

            $inserted++;
        }

        return $inserted;
    }
}
