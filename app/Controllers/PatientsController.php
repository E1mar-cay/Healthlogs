<?php

class PatientsController extends Controller
{
    private PatientsModel $model;

    public function __construct()
    {
        $this->model = new PatientsModel();
    }

    public function index(): void
    {
        $patients = $this->model->all();
        $this->view('patients/index', ['patients' => $patients]);
    }

    public function create(): void
    {
        $this->view('patients/form', ['patient' => null]);
    }

    public function store(): void
    {
        $data = $this->sanitize($_POST);
        $this->model->create($data);
        $this->redirect('/patients');
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->model->find($id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found.';
            return;
        }
        $this->view('patients/form', ['patient' => $patient]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->sanitize($_POST);
        $this->model->update($id, $data);
        $this->redirect('/patients');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->model->delete($id);
        $this->redirect('/patients');
    }

    private function sanitize(array $input): array
    {
        return [
            'household_id' => $input['household_id'] !== '' ? (int)$input['household_id'] : null,
            'philhealth_no' => trim($input['philhealth_no'] ?? '') ?: null,
            'national_id' => trim($input['national_id'] ?? '') ?: null,
            'first_name' => trim($input['first_name'] ?? ''),
            'middle_name' => trim($input['middle_name'] ?? '') ?: null,
            'last_name' => trim($input['last_name'] ?? ''),
            'suffix' => trim($input['suffix'] ?? '') ?: null,
            'sex' => $input['sex'] ?? 'male',
            'birth_date' => $input['birth_date'] ?? '2000-01-01',
            'blood_type' => trim($input['blood_type'] ?? '') ?: null,
            'contact_no' => trim($input['contact_no'] ?? '') ?: null,
            'email' => trim($input['email'] ?? '') ?: null,
            'address_line' => trim($input['address_line'] ?? '') ?: null,
            'barangay' => trim($input['barangay'] ?? ''),
            'city_municipality' => trim($input['city_municipality'] ?? ''),
            'province' => trim($input['province'] ?? ''),
            'status' => $input['status'] ?? 'active',
        ];
    }
}
