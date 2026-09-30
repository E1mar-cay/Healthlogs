<?php
require_once __DIR__ . '/../Core/Database.php';

class MedicineModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function getAllMedicines(): array
    {
        $sql = "SELECT m.*,
                       COALESCE(SUM(mt.quantity), 0) AS total_stock,
                       COUNT(DISTINCT mb.id) AS batch_count
                FROM medicines m
                LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id
                LEFT JOIN medicine_batches mb ON mb.medicine_id = m.id
                GROUP BY m.id
                ORDER BY m.name";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getMedicineById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createMedicine(array $data): int
    {
        $sql = "INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['generic_name'],
            $data['formulation'],
            $data['strength'],
            $data['unit'],
            $data['reorder_level'] ?? 100,
        ]);

        $newId = (int)$this->db->lastInsertId();

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logInventory('medicine_add', "Added new medicine: {$data['name']}", 'medicines', (string)$newId, [
                'medicine_id' => $newId,
                'name' => $data['name'],
                'generic_name' => $data['generic_name'] ?? null,
                'formulation' => $data['formulation'] ?? null,
                'strength' => $data['strength'] ?? null,
                'unit' => $data['unit'] ?? 'units',
                'reorder_level' => $data['reorder_level'] ?? 100
            ]);
        }

        return $newId;
    }

    public function updateMedicine(int $id, array $data): void
    {
        $oldMed = class_exists('ActivityLogger') ? ActivityLogger::getMedicineInfo($id) : null;

        $sql = "UPDATE medicines SET
                name = ?, generic_name = ?, formulation = ?,
                strength = ?, unit = ?, reorder_level = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['generic_name'],
            $data['formulation'],
            $data['strength'],
            $data['unit'],
            $data['reorder_level'],
            $id,
        ]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logInventory('medicine_update', "Updated medicine profile: {$data['name']}", 'medicines', (string)$id, [
                'medicine_id' => $id,
                'old' => $oldMed,
                'new' => $data
            ]);
        }
    }

    /**
     * Classifies a batch expiration date as 'Valid', 'Near Expiry', or 'Expired'.
     */
    public static function classifyExpiry(string $expiryDate): array
    {
        $today = strtotime(date('Y-m-d'));
        $expTime = strtotime($expiryDate);
        $daysRemaining = (int)round(($expTime - $today) / 86400);

        if ($daysRemaining < 0) {
            return [
                'status' => 'Expired',
                'key' => 'expired',
                'days_remaining' => $daysRemaining,
                'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300',
                'is_expired' => true,
                'is_near_expiry' => false,
                'is_valid' => false,
                'can_issue' => false
            ];
        } elseif ($daysRemaining <= 60) {
            return [
                'status' => 'Near Expiry',
                'key' => 'near_expiry',
                'days_remaining' => $daysRemaining,
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300',
                'is_expired' => false,
                'is_near_expiry' => true,
                'is_valid' => false,
                'can_issue' => true
            ];
        } else {
            return [
                'status' => 'Valid',
                'key' => 'valid',
                'days_remaining' => $daysRemaining,
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'is_expired' => false,
                'is_near_expiry' => false,
                'is_valid' => true,
                'can_issue' => true
            ];
        }
    }

    public function getBatchesByMedicine(int $medicineId): array
    {
        $sql = "SELECT mb.*,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand,
                       CASE
                           WHEN mb.expiry_date < CURDATE() THEN 'Expired'
                           WHEN mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Near Expiry'
                           ELSE 'Valid'
                       END AS expiry_status,
                       DATEDIFF(mb.expiry_date, CURDATE()) AS days_remaining
                FROM medicine_batches mb
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.medicine_id = ?
                GROUP BY mb.id
                HAVING on_hand > 0
                ORDER BY mb.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }

    /**
     * Get available batches for issuance strictly excluding expired medicines.
     */
    public function getAvailableBatchesForIssuance(int $medicineId): array
    {
        $sql = "SELECT mb.*,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand,
                       DATEDIFF(mb.expiry_date, CURDATE()) AS days_remaining
                FROM medicine_batches mb
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.medicine_id = ?
                  AND mb.expiry_date >= CURDATE()
                GROUP BY mb.id
                HAVING on_hand > 0
                ORDER BY mb.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }

    /**
     * Get non-expired available stock for medicine issuance and forecasting.
     */
    public function getAvailableStock(int $medicineId): int
    {
        $sql = "SELECT COALESCE(SUM(mt.quantity), 0)
                FROM medicine_batches mb
                JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.medicine_id = ?
                  AND mb.expiry_date >= CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$medicineId]);
        return max(0, (int)$stmt->fetchColumn());
    }

    /**
     * Get near-expiry batches approaching expiration date for proactive notifications.
     */
    public function getNearExpiryBatches(int $daysThreshold = 60): array
    {
        $sql = "SELECT mb.*, m.name AS medicine_name, m.unit,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand,
                       DATEDIFF(mb.expiry_date, CURDATE()) AS days_remaining
                FROM medicine_batches mb
                JOIN medicines m ON m.id = mb.medicine_id
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                GROUP BY mb.id
                HAVING on_hand > 0
                ORDER BY mb.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll();
    }

    /**
     * Get expired batches with remaining stock to notify staff.
     */
    public function getExpiredBatchesWithStock(): array
    {
        $sql = "SELECT mb.*, m.name AS medicine_name, m.unit,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand,
                       DATEDIFF(CURDATE(), mb.expiry_date) AS days_expired
                FROM medicine_batches mb
                JOIN medicines m ON m.id = mb.medicine_id
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date < CURDATE()
                GROUP BY mb.id
                HAVING on_hand > 0
                ORDER BY mb.expiry_date ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }


    public function addBatch(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $sql = "INSERT INTO medicine_batches
                    (medicine_id, batch_no, expiry_date, received_date, quantity_received)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['medicine_id'],
                $data['batch_no'],
                $data['expiry_date'],
                $data['received_date'],
                $data['quantity_received'],
            ]);

            $batchId = (int)$this->db->lastInsertId();

            $transactionSql = "INSERT INTO medicine_transactions
                              (medicine_id, batch_id, transaction_datetime, transaction_type, quantity, reference, notes, recorded_by)
                              VALUES (?, ?, ?, 'received', ?, ?, ?, ?)";
            $transactionStmt = $this->db->prepare($transactionSql);
            $transactionStmt->execute([
                $data['medicine_id'],
                $batchId,
                $data['received_date'] . ' 09:00:00',
                $data['quantity_received'],
                'BATCH-' . $data['batch_no'],
                'Opening stock from batch entry',
                $data['recorded_by'] ?? null,
            ]);

            $this->db->commit();

            if (class_exists('ActivityLogger')) {
                $medInfo = ActivityLogger::getMedicineInfo((int)$data['medicine_id']);
                ActivityLogger::logInventory('stock_receive', "Received batch {$data['batch_no']} (+{$data['quantity_received']} {$medInfo['unit']}) for {$medInfo['name']}", 'medicine_batches', (string)$batchId, [
                    'batch_id' => $batchId,
                    'batch_no' => $data['batch_no'],
                    'medicine_id' => $data['medicine_id'],
                    'medicine_name' => $medInfo['name'] ?? null,
                    'quantity' => $data['quantity_received'],
                    'expiry_date' => $data['expiry_date'],
                    'received_date' => $data['received_date']
                ]);
            }

            return $batchId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recordTransaction(array $data): int
    {
        $sql = "INSERT INTO medicine_transactions
                (medicine_id, batch_id, transaction_datetime, transaction_type, quantity, reference, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['medicine_id'],
            $data['batch_id'],
            $data['transaction_datetime'],
            $data['transaction_type'],
            $data['quantity'],
            $data['reference'],
            $data['notes'],
            $data['recorded_by'],
        ]);

        $txId = (int)$this->db->lastInsertId();

        if (class_exists('ActivityLogger')) {
            $medInfo = ActivityLogger::getMedicineInfo((int)$data['medicine_id']);
            $type = $data['transaction_type'] ?? 'received';
            $action = $type === 'dispensed' ? 'issuance' : ($type === 'returned' ? 'return' : ($type === 'adjustment' ? 'adjustment' : 'stock_receive'));
            ActivityLogger::logInventory($action, "Transaction #{$txId} logged: {$type} (" . ($data['quantity'] >= 0 ? "+{$data['quantity']}" : "{$data['quantity']}") . ") for {$medInfo['name']}", 'medicine_transactions', (string)$txId, [
                'transaction_id' => $txId,
                'medicine_id' => $data['medicine_id'],
                'medicine_name' => $medInfo['name'] ?? null,
                'quantity' => $data['quantity'],
                'transaction_type' => $type,
                'reference' => $data['reference'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null
            ]);
        }

        return $txId;
    }

    public function getLowStockMedicines(): array
    {
        $sql = "SELECT m.*,
                       COALESCE(stk.valid_stock, 0) AS total_stock
                FROM medicines m
                LEFT JOIN (
                    SELECT mb.medicine_id, SUM(mt.quantity) AS valid_stock
                    FROM medicine_batches mb
                    JOIN medicine_transactions mt ON mt.batch_id = mb.id
                    WHERE mb.expiry_date >= CURDATE()
                    GROUP BY mb.medicine_id
                ) stk ON stk.medicine_id = m.id
                GROUP BY m.id
                HAVING total_stock <= COALESCE(m.reorder_level, 0)
                ORDER BY total_stock ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getExpiringBatches(int $days = 30): array
    {
        $sql = "SELECT mb.*, m.name AS medicine_name,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand
                FROM medicine_batches mb
                JOIN medicines m ON mb.medicine_id = m.id
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                GROUP BY mb.id
                HAVING on_hand > 0
                ORDER BY mb.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
