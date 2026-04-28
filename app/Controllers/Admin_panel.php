<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Stock_location;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

require_once('Secure_Controller.php');

class Admin_panel extends Secure_Controller
{
    private Stock_location $stock_location;
    private Employee $employee_model;
    private BaseConnection $db;

    private const MODULES = ['items', 'sales', 'receivings', 'expenses', 'service_tickets'];

    public function __construct()
    {
        parent::__construct('config');

        $this->stock_location = model(Stock_location::class);
        $this->employee_model = model(Employee::class);
        $this->db             = Database::connect();
    }

    public function getIndex(): void
    {
        helper('tabular');

        $branches   = $this->stock_location->get_all()->getResultArray();
        $employees  = $this->employee_model->get_all()->getResultArray();

        // Build access matrix: [person_id][location_id] = bool
        $access = [];
        foreach ($employees as $emp) {
            $pid = $emp['person_id'];
            $access[$pid] = [];
            foreach ($branches as $branch) {
                $lid = $branch['location_id'];
                $count = $this->db->table('grants g')
                    ->join('permissions p', 'g.permission_id = p.permission_id')
                    ->where('g.person_id', $pid)
                    ->where('p.location_id', $lid)
                    ->countAllResults();
                $access[$pid][$lid] = $count > 0;
            }
        }

        $data = [
            'branches'              => $branches,
            'employees'             => $employees,
            'access'                => $access,
            'employee_table_headers' => get_people_manage_table_headers(),
        ];

        echo view('admin_panel/manage', $data);
    }

    public function postCreateBranch(): void
    {
        $name = trim($this->request->getPost('branch_name') ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Nombre requerido.']);
            return;
        }

        $location_data = ['location_name' => $name];
        $success = $this->stock_location->save_value($location_data, NEW_ENTRY);

        echo json_encode([
            'success' => $success,
            'message' => $success ? "Sucursal '$name' creada." : 'Error al crear sucursal.',
        ]);
    }

    public function postDeleteBranch(): void
    {
        $location_id = (int)($this->request->getPost('location_id') ?? 0);

        $all = $this->stock_location->get_all()->getNumRows();
        if ($all <= 1) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar la única sucursal.']);
            return;
        }

        $success = $this->stock_location->delete($location_id);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Sucursal eliminada.' : 'Error al eliminar sucursal.',
        ]);
    }

    public function postToggleAccess(): void
    {
        $person_id   = (int)($this->request->getPost('person_id')   ?? 0);
        $location_id = (int)($this->request->getPost('location_id') ?? 0);
        $grant       = (bool)($this->request->getPost('grant') == '1');

        if ($person_id <= 0 || $location_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
            return;
        }

        if ($grant) {
            $this->_grant_access($person_id, $location_id);
        } else {
            $this->_revoke_access($person_id, $location_id);
        }

        echo json_encode(['success' => true]);
    }

    private function _grant_access(int $person_id, int $location_id): void
    {
        $location_name = $this->stock_location->get_location_name($location_id);

        foreach (self::MODULES as $module) {
            $permission_id = $module . '_' . str_replace(' ', '_', $location_name);

            $exists = $this->db->table('grants')
                ->where('permission_id', $permission_id)
                ->where('person_id', $person_id)
                ->countAllResults();

            if (!$exists) {
                $menu_group = $this->employee_model->get_menu_group($module, $person_id);
                $this->db->table('grants')->insert([
                    'permission_id' => $permission_id,
                    'person_id'     => $person_id,
                    'menu_group'    => $menu_group ?: 'home',
                ]);
            }
        }
    }

    private function _revoke_access(int $person_id, int $location_id): void
    {
        $sub = $this->db->table('permissions')
            ->select('permission_id')
            ->where('location_id', $location_id)
            ->get()
            ->getResultArray();

        $permission_ids = array_column($sub, 'permission_id');

        if (!empty($permission_ids)) {
            $this->db->table('grants')
                ->where('person_id', $person_id)
                ->whereIn('permission_id', $permission_ids)
                ->delete();
        }
    }
}
