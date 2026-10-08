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

    private const MODULES      = ['items', 'sales', 'receivings', 'expenses', 'service_tickets'];
    private const BACKUP_PATH  = 'D:\\Backups\\';

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

        $data = [
            'branches'               => $this->stock_location->get_all()->getResultArray(),
            'employee_table_headers' => get_employee_manage_table_headers(),
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

    /**
     * Renames a branch keeping every employee's access: permissions are named after the branch, so the
     * permission rows are re-keyed and the existing grants moved over (Stock_location::save_value would
     * wipe the grants and re-grant every employee).
     */
    public function postRenameBranch(): void
    {
        $location_id = (int) ($this->request->getPost('location_id') ?? 0);
        $name        = trim((string) ($this->request->getPost('branch_name') ?? ''));

        if ($name === '' || mb_strlen($name) > 255) {
            echo json_encode(['success' => false, 'message' => lang('Admin_panel.name_required')]);
            return;
        }

        $branches = $this->stock_location->get_all()->getResultArray();
        $current  = null;
        foreach ($branches as $branch) {
            if ((int) $branch['location_id'] === $location_id) {
                $current = $branch;
            } elseif (mb_strtolower($branch['location_name']) === mb_strtolower($name)) {
                echo json_encode(['success' => false, 'message' => lang('Admin_panel.name_duplicate')]);
                return;
            }
        }

        if ($current === null) {
            echo json_encode(['success' => false, 'message' => lang('Admin_panel.branch_not_found')]);
            return;
        }

        if ($current['location_name'] === $name) {
            echo json_encode(['success' => true, 'message' => lang('Admin_panel.branch_renamed', [$name])]);
            return;
        }

        $permissions = $this->db->table('permissions')->where('location_id', $location_id)->get()->getResultArray();
        $renames     = [];
        foreach ($permissions as $permission) {
            $new_id = $permission['module_id'] . '_' . str_replace(' ', '_', $name);
            if ($new_id !== $permission['permission_id'] && $this->db->table('permissions')->where('permission_id', $new_id)->countAllResults() > 0) {
                echo json_encode(['success' => false, 'message' => lang('Admin_panel.name_duplicate')]);
                return;
            }
            $renames[] = [$permission, $new_id];
        }

        $this->db->transStart();
        $this->db->table('stock_locations')->where('location_id', $location_id)->update(['location_name' => $name]);
        foreach ($renames as [$permission, $new_id]) {
            if ($new_id === $permission['permission_id']) {
                continue;
            }
            $this->db->table('permissions')->insert(['permission_id' => $new_id, 'module_id' => $permission['module_id'], 'location_id' => $location_id]);
            $this->db->table('grants')->where('permission_id', $permission['permission_id'])->update(['permission_id' => $new_id]);
            $this->db->table('permissions')->where('permission_id', $permission['permission_id'])->delete();
        }
        $this->db->transComplete();

        $success = $this->db->transStatus();

        echo json_encode([
            'success' => $success,
            'message' => $success ? lang('Admin_panel.branch_renamed', [$name]) : lang('Admin_panel.branch_rename_failed'),
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

    /**
     * Downloads a backup. The Backup controller needs a `backup` module grant that no role has, so the panel
     * serves the file itself under the same permission as the rest of the panel.
     */
    public function getBackupDownload(string $filename = '')
    {
        $filename = rawurldecode($filename);
        $filepath = self::BACKUP_PATH . $filename;

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename) || !is_file($filepath)) {
            return $this->response->setStatusCode(404)->setBody(lang('Backup.backup_failed'));
        }

        return $this->response->download($filepath, null)->setFileName($filename);
    }

    public function getBackupList(): void
    {
        echo json_encode(['backups' => $this->_get_backups()]);
    }

    public function postBackupCreate(): void
    {
        if (!is_dir(self::BACKUP_PATH)) {
            mkdir(self::BACKUP_PATH, 0755, true);
        }

        $db_config = config('Database')->default;
        $mysqli    = new \mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database'], (int)($db_config['port'] ?? 3306));
        $mysqli->set_charset('utf8mb4');

        $output  = "-- OSPOS Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Database: " . $db_config['database'] . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET NAMES utf8mb4;\n\n";

        $tables = [];
        $res = $mysqli->query('SHOW TABLES');
        while ($row = $res->fetch_row()) { $tables[] = $row[0]; }

        foreach ($tables as $table) {
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $cr = $mysqli->query("SHOW CREATE TABLE `$table`")->fetch_row();
            $output .= $cr[1] . ";\n\n";

            $rows = $mysqli->query("SELECT * FROM `$table`");
            if ($rows->num_rows > 0) {
                $cols = [];
                $mc = $mysqli->query("SHOW COLUMNS FROM `$table`");
                while ($c = $mc->fetch_assoc()) { $cols[] = '`' . $c['Field'] . '`'; }
                $cols_str = implode(', ', $cols);
                $batch = [];
                while ($r = $rows->fetch_row()) {
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . $mysqli->real_escape_string($v) . "'", $r);
                    $batch[] = '(' . implode(', ', $vals) . ')';
                    if (count($batch) >= 100) {
                        $output .= "INSERT INTO `$table` ($cols_str) VALUES\n" . implode(",\n", $batch) . ";\n";
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    $output .= "INSERT INTO `$table` ($cols_str) VALUES\n" . implode(",\n", $batch) . ";\n";
                }
                $output .= "\n";
            }
        }
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $mysqli->close();

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents(self::BACKUP_PATH . $filename, $output);

        echo json_encode(['success' => true, 'message' => lang('Backup.backup_created'), 'new_file' => $filename, 'backups' => $this->_get_backups()]);
    }

    public function postBackupDelete(): void
    {
        $filename = $this->request->getPost('filename');
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
            echo json_encode(['success' => false, 'message' => 'Archivo inválido.']);
            return;
        }

        $filepath = self::BACKUP_PATH . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
            echo json_encode(['success' => true, 'message' => lang('Backup.backup_deleted'), 'backups' => $this->_get_backups()]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Backup.backup_failed')]);
        }
    }

    private function _get_backups(): array
    {
        $backups = [];
        if (is_dir(self::BACKUP_PATH)) {
            foreach (glob(self::BACKUP_PATH . 'backup_*.sql') as $file) {
                $size  = filesize($file);
                $mtime = filemtime($file);
                $backups[] = [
                    'filename'     => basename($file),
                    'date'         => date('Y-m-d H:i:s', $mtime),
                    'date_display' => date('d/m/Y H:i', $mtime),
                    'age_days'     => (int) floor((time() - $mtime) / 86400),
                    'size'         => $size >= 1048576 ? number_format($size / 1048576, 1, ',', '.') . ' MB' : number_format(max($size, 1) / 1024, 0, ',', '.') . ' KB',
                ];
            }
            usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
        }
        return $backups;
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
