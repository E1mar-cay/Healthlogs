<?php

class MedicineModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
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

        return (int)$this->db->lastInsertId();
    }

    public function updateMedicine(int $id, array $data): void
    {
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
    }

    public function getBatchesByMedicine(int $medicineId): array
    {
        $sql = "SELECT mb.*,
                       COALESCE(SUM(mt.quantity), 0) AS on_hand
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

        return (int)$this->db->lastInsertId();
    }

    public function getLowStockMedicines(): array
    {
        $sql = "SELECT m.*,
                       COALESCE(SUM(mt.quantity), 0) AS total_stock
                FROM medicines m
                LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id
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
