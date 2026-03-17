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
        $this->db->beginTransaction();
        try {
            // Delete child records first (in correct order)
            $this->db->prepare("DELETE pv FROM prenatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE pv FROM postnatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE tf FROM tb_followups tf INNER JOIN tb_cases tc ON tf.tb_case_id = tc.id WHERE tc.patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM immunization_records WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM immunization_schedule WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM patient_allergies WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM patient_conditions WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM pregnancies WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM reminders WHERE patient_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM tb_cases WHERE patient_id = ?")->execute([$id]);
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
