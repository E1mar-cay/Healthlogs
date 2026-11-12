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
                    contact_no, email, address_line, barangay, status,
                    mother_name, father_name, guardian_name, parent_contact_no, mother_patient_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['household_id'] ?? null,
            $data['philhealth_no'] ?? null,
            $data['national_id'] ?? null,
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['suffix'] ?? null,
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'] ?? null,
            $data['contact_no'] ?? null,
            $data['email'] ?? null,
            $data['address_line'] ?? null,
            $data['barangay'],
            $data['status'] ?? 'active',
            $data['mother_name'] ?? null,
            $data['father_name'] ?? null,
            $data['guardian_name'] ?? null,
            $data['parent_contact_no'] ?? null,
            $data['mother_patient_id'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE patients SET
                    household_id = ?, philhealth_no = ?, national_id = ?,
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    sex = ?, birth_date = ?, blood_type = ?,
                    contact_no = ?, email = ?, address_line = ?, barangay = ?, status = ?,
                    mother_name = ?, father_name = ?, guardian_name = ?, parent_contact_no = ?, mother_patient_id = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['household_id'] ?? null,
            $data['philhealth_no'] ?? null,
            $data['national_id'] ?? null,
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['suffix'] ?? null,
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'] ?? null,
            $data['contact_no'] ?? null,
            $data['email'] ?? null,
            $data['address_line'] ?? null,
            $data['barangay'],
            $data['status'] ?? 'active',
            $data['mother_name'] ?? null,
            $data['father_name'] ?? null,
            $data['guardian_name'] ?? null,
            $data['parent_contact_no'] ?? null,
            $data['mother_patient_id'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->beginTransaction();
        try {
            // Delete child records first (in correct order)
            $this->db->prepare("DELETE pv FROM prenatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE pv FROM postnatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM immunization_records WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM immunization_schedule WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM patient_allergies WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM patient_conditions WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM pregnancies WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM reminders WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM visits WHERE patient_id = ?")->execute([$id]);
            
            // Finally delete the patient
            $this->db->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
