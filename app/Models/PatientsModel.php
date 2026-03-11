<?php

class PatientsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM patients ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO patients (
                    household_id, philhealth_no, national_id,
                    first_name, middle_name, last_name, suffix,
                    sex, birth_date, blood_type,
                    contact_no, email, address_line, barangay,
                    city_municipality, province, status
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['household_id'],
            $data['philhealth_no'],
            $data['national_id'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'],
            $data['contact_no'],
            $data['email'],
            $data['address_line'],
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['status'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE patients SET
                    household_id = ?, philhealth_no = ?, national_id = ?,
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    sex = ?, birth_date = ?, blood_type = ?,
                    contact_no = ?, email = ?, address_line = ?, barangay = ?,
                    city_municipality = ?, province = ?, status = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['household_id'],
            $data['philhealth_no'],
            $data['national_id'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'],
            $data['contact_no'],
            $data['email'],
            $data['address_line'],
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['status'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$id]);
    }
}
