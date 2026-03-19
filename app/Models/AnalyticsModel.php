<?php

class AnalyticsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function recordMetric(string $metricName, string $date, float $value): void
    {
        $sql = "INSERT INTO analytics_daily (metric_name, metric_date, metric_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$metricName, $date, $value]);
    }

    public function getMetricData(string $metricName, string $startDate, string $endDate): array
    {
        $sql = "SELECT metric_date, metric_value 
                FROM analytics_daily 
                WHERE metric_name = ? AND metric_date BETWEEN ? AND ?
                ORDER BY metric_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$metricName, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getDashboardStats(): array
    {
        $stats = [];
        
        // Total patients
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
        $stats['total_patients'] = $stmt->fetch()['total'];
        
        // Total visits this month
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visits WHERE MONTH(visit_datetime) = MONTH(CURDATE()) AND YEAR(visit_datetime) = YEAR(CURDATE())");
        $stats['monthly_visits'] = $stmt->fetch()['total'];
        
        // Pending reminders
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM reminders WHERE status = 'pending' AND due_date <= CURDATE()");
        $stats['pending_reminders'] = $stmt->fetch()['total'];
        
        // Low stock medicines
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM (SELECT m.id FROM medicines m LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id GROUP BY m.id HAVING COALESCE(SUM(mb.current_stock), 0) <= m.reorder_level) as low_stock");
        $stats['low_stock_medicines'] = $stmt->fetch()['total'];
        
        // Ongoing pregnancies
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM pregnancies WHERE status = 'ongoing'");
        $stats['ongoing_pregnancies'] = $stmt->fetch()['total'];
        
        return $stats;
    }

    public function getVisitTrends(int $days = 30): array
    {
        $sql = "SELECT DATE(visit_datetime) as visit_date, COUNT(*) as visit_count
                FROM visits 
                WHERE visit_datetime >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(visit_datetime)
                ORDER BY visit_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function updateDailyMetrics(): void
    {
        $today = date('Y-m-d');
        
        // Count total visits today
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM visits WHERE DATE(visit_datetime) = ?");
        $stmt->execute([$today]);
        $visitsCount = $stmt->fetch()['count'];
        $this->recordMetric('visits_total', $today, $visitsCount);
        
        // Count medicine transactions today
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(ABS(quantity)), 0) as total FROM medicine_transactions WHERE DATE(transaction_datetime) = ? AND transaction_type = 'dispensed'");
        $stmt->execute([$today]);
        $medicineTotal = $stmt->fetch()['total'];
        $this->recordMetric('medicine_total', $today, $medicineTotal);
    }
}