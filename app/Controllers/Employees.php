<?php

namespace App\Controllers;

use App\Models\Module;
use App\Models\Stock_location;
use Config\Services;

/**
 *
 *
 * @property module module
 * @property Stock_location stock_location
 *
 */
class Employees extends Persons
{
    /** Modules a newly ticked branch grants (same list the old Accesos switch used). */
    private const BRANCH_MODULES = ['items', 'sales', 'receivings', 'expenses', 'service_tickets'];

    public function __construct()
    {
        parent::__construct('employees');

        $this->module = model('Module');
        $this->stock_location = model(Stock_location::class);
    }

    /**
     * Branches the current session is working in (register and inventory selections).
     *
     * @return int[]
     */
    private function session_locations(): array
    {
        $ids = [];
        foreach (['sales_location', 'item_location'] as $key) {
            $value = session()->get($key);
            if (!empty($value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, bool> $grants permission_id => true
     */
    private function employee_has_location(array $grants, int $location_id): bool
    {
        foreach ($this->module->get_all_permissions()->getResult() as $permission) {
            if ((int) $permission->location_id === $location_id && isset($grants[$permission->permission_id])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns employee table data rows. This will be called with AJAX.
     *
     * @return void
     */
    public function getSearch(): void
    {
        $search = $this->request->getGet('search');
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort   = $this->sanitizeSortColumn(employee_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'people.person_id');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($sort === 'name') {
            $sort = 'last_name';    // the list shows first + last name together
        }

        $employees = $this->employee->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->employee->get_found_rows($search);

        $rows = $employees->getResult();
        $branch_names = $this->employee->get_branch_names(array_map(static fn ($person) => (int) $person->person_id, $rows));

        $data_rows = [];
        foreach ($rows as $person) {
            $data_rows[] = get_employee_data_row($person, $branch_names[(int) $person->person_id] ?? []);
        }

        echo json_encode(['total' => $total_rows, 'rows' => $data_rows]);
    }

    /**
     * AJAX called function gives search suggestions based on what is being searched for.
     *
     * @return void
     */
    public function getSuggest(): void
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->employee->get_search_suggestions($search, 25, true);

        echo json_encode($suggestions);
    }

    /**
     * @return void
     */
    public function suggest_search(): void
    {
        $search = $this->request->getPost('term');
        $suggestions = $this->employee->get_search_suggestions($search);

        echo json_encode($suggestions);
    }

    /**
     * Loads the employee edit form
     */
    public function getView(int $employee_id = NEW_ENTRY): void
    {
        $person_info = $this->employee->get_info($employee_id);
        foreach (get_object_vars($person_info) as $property => $value) {
            $person_info->$property = $value;
        }
        $data['person_info'] = $person_info;
        $data['employee_id'] = $employee_id;

        $modules = [];
        foreach ($this->module->get_all_modules()->getResult() as $module) {
            $module->grant = $this->employee->has_grant($module->module_id, $person_info->person_id);
            $module->menu_group = $this->employee->get_menu_group($module->module_id, $person_info->person_id);

            $modules[] = $module;
        }
        $data['all_modules'] = $modules;

        $permissions = [];
        foreach ($this->module->get_all_subpermissions()->getResult() as $permission) {    // TODO: subpermissions does not follow naming standards.
            $permission->permission_id = str_replace(' ', '_', $permission->permission_id);
            $permission->grant = $this->employee->has_grant($permission->permission_id, $person_info->person_id);

            $permissions[] = $permission;
        }
        $data['all_subpermissions'] = $permissions;

        // Branch access (same rule the old Accesos matrix used) + what an employee cannot take away from themselves
        $logged_in_id = (int) $this->employee->get_logged_in_employee_info()->person_id;
        $is_self = $employee_id !== NEW_ENTRY && (int) $employee_id === $logged_in_id;

        $data['branches'] = $this->stock_location->get_all()->getResultArray();
        $data['branch_access'] = $employee_id === NEW_ENTRY ? [] : $this->employee->get_branch_access((int) $employee_id);
        $data['is_self'] = $is_self;
        $data['protected_locations'] = $is_self ? $this->session_locations() : [];
        $data['protected_permissions'] = $is_self
            ? array_values(array_filter(Module::SELF_PROTECTED_PERMISSIONS, fn ($id) => $this->employee->has_grant($id, $logged_in_id)))
            : [];

        echo view('employees/form', $data);
    }

    /**
     * Inserts/updates an employee
     */
    public function postSave(int $employee_id = NEW_ENTRY): void
    {
        $first_name = $this->request->getPost('first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);    // TODO: duplicated code
        $last_name = $this->request->getPost('last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = strtolower($this->request->getPost('email', FILTER_SANITIZE_EMAIL));

        // format first and last name properly
        $first_name = $this->nameize($first_name);
        $last_name = $this->nameize($last_name);

        $person_data = [
            'first_name'          => $first_name,
            'last_name'           => $last_name,
            'gender'              => $this->request->getPost('gender', FILTER_SANITIZE_NUMBER_INT) ?? 0,
            'email'               => $email,
            'phone_number'        => $this->request->getPost('phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'address_1'           => $this->request->getPost('address_1', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'address_2'           => $this->request->getPost('address_2', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'city'                => $this->request->getPost('city', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'country'             => $this->request->getPost('country', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'comments'            => $this->request->getPost('comments', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'identification_type' => $this->request->getPost('identification_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'identification'      => $this->request->getPost('identification', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''
        ];

        $errors = [];
        $logged_in_id = (int) $this->employee->get_logged_in_employee_info()->person_id;
        $is_self = $employee_id != NEW_ENTRY && (int) $employee_id === $logged_in_id;

        // Branch access now comes from this form (checkboxes `branch_access[]`, marker `branch_access_sent`).
        // Without the marker the old behavior stays: existing location grants are kept untouched.
        $branches_sent = $this->request->getPost('branch_access_sent') !== null;
        $active_locations = array_map('intval', array_column($this->stock_location->get_all()->getResultArray(), 'location_id'));
        $posted_locations = array_values(array_intersect($active_locations, array_map('intval', (array) $this->request->getPost('branch_access'))));

        if ($branches_sent && $is_self) {
            // Cannot take away the branch the session is working in, nor every branch
            foreach ($this->session_locations() as $location_id) {
                if (in_array($location_id, $active_locations, true) && !in_array($location_id, $posted_locations, true)) {
                    $errors['branch_access'] = lang('Employees.self_branch_protected', [$this->stock_location->get_location_name($location_id)]);
                }
            }
            if (empty($posted_locations) && empty($errors['branch_access'])) {
                $errors['branch_access'] = lang('Employees.self_branch_protected_all');
            }
        }

        $current_location_grants = [];
        if ($employee_id != NEW_ENTRY) {
            foreach ($this->employee->get_employee_grants((int) $employee_id) as $grant_row) {
                $current_location_grants[$grant_row['permission_id']] = true;
            }
        }

        $grants_array = [];
        foreach ($this->module->get_all_permissions()->getResult() as $permission) {
            // Location-scoped permissions (Ventas/Gastos/etc. per sucursal)
            if (!empty($permission->location_id)) {
                $location_id = (int) $permission->location_id;
                $had_grant = isset($current_location_grants[$permission->permission_id]);

                if (!$branches_sent || !in_array($location_id, $active_locations, true)) {
                    // Not managed from this request: keep what the employee already has
                    if ($had_grant) {
                        $grants_array[] = ['permission_id' => $permission->permission_id, 'menu_group' => '--'];
                    }
                } elseif (in_array($location_id, $posted_locations, true)) {
                    // Checked: keep existing grants of that branch; a branch the employee did not have gets the
                    // same modules the old Accesos switch gave
                    $had_branch = $this->employee_has_location($current_location_grants, $location_id);
                    if ($had_grant || (!$had_branch && in_array($permission->module_id, self::BRANCH_MODULES, true))) {
                        $grants_array[] = ['permission_id' => $permission->permission_id, 'menu_group' => '--'];
                    }
                }
                continue;
            }

            $grants = [];
            $grant = $this->request->getPost('grant_' . $permission->permission_id) != null ? $this->request->getPost('grant_' . $permission->permission_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

            if ($grant == $permission->permission_id) {
                $grants['permission_id'] = $permission->permission_id;
                $grants['menu_group'] = $this->request->getPost('menu_group_' . $permission->permission_id) != null ? $this->request->getPost('menu_group_' . $permission->permission_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '--';
                $grants_array[] = $grants;
            }
        }

        if ($is_self) {
            // Cannot take away the permissions that open this panel
            $posted_ids = array_column($grants_array, 'permission_id');
            foreach (Module::SELF_PROTECTED_PERMISSIONS as $protected_id) {
                if ($this->employee->has_grant($protected_id, $logged_in_id) && !in_array($protected_id, $posted_ids, true)) {
                    $errors['permissions'] = lang('Employees.self_permission_protected', [lang("Module.$protected_id")]);
                }
            }
        }

        $app_config = config(\Config\OSPOS::class)->settings;
        $language_post = $this->request->getPost('language', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $exploded = !empty($language_post) ? explode(":", $language_post) : [];
        $language_code = !empty($exploded[0]) ? $exploded[0] : ($app_config['language_code'] ?? 'en');
        $language      = !empty($exploded[1]) ? $exploded[1] : ($app_config['language'] ?? 'english');

        $pin_raw = trim((string) $this->request->getPost('pin', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $pin = null;
        if ($pin_raw !== '') {
            if (!ctype_digit($pin_raw) || strlen($pin_raw) !== 4) {
                $errors['pin'] = lang('Employees.pin_invalid');
            } elseif ($this->employee->pin_in_use($pin_raw, (int) $employee_id)) {
                $errors['pin'] = lang('Employees.pin_in_use');
            } else {
                $pin = $pin_raw;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => reset($errors),
                'errors'  => $errors,
                'id'      => NEW_ENTRY
            ]);

            return;
        }

        // Password has been changed OR first time password set
        if (!empty($this->request->getPost('password')) && ENVIRONMENT != 'testing') {
            $employee_data = [
                'username'      => $this->request->getPost('username', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'password'      => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'hash_version'  => 2,
                'language_code' => $language_code,
                'language'      => $language,
                'pin'           => $pin
            ];
        } else { // Password not changed
            $employee_data = [
                'username'      => $this->request->getPost('username', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'language_code' => $language_code,
                'language'      => $language,
                'pin'           => $pin
            ];
        }

        if ($this->employee->save_employee($person_data, $employee_data, $grants_array, $employee_id)) {
            // New employee
            if ($employee_id == NEW_ENTRY) {
                echo json_encode([
                    'success' => true,
                    'message' => lang('Employees.successful_adding') . ' ' . $first_name . ' ' . $last_name,
                    'id'      => $employee_data['person_id']
                ]);
            } else { // Existing employee
                echo json_encode([
                    'success' => true,
                    'message' => lang('Employees.successful_updating') . ' ' . $first_name . ' ' . $last_name,
                    'id'      => $employee_id
                ]);
            }
        } else { // Failure
            echo json_encode([
                'success' => false,
                'message' => lang('Employees.error_adding_updating') . ' ' . $first_name . ' ' . $last_name,
                'id'      => NEW_ENTRY
            ]);
        }
    }

    /**
     * This deletes employees from the employees table
     */
    public function postDelete(): void
    {
        $employees_to_delete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($this->employee->delete_list($employees_to_delete)) {    // TODO: this is passing a string, but delete_list expects an array
            echo json_encode([
                'success' => true,
                'message' => lang('Employees.successful_deleted') . ' ' . count($employees_to_delete) . ' ' . lang('Employees.one_or_multiple')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Employees.cannot_be_deleted')]);
        }
    }

    /**
     * Checks an employee username against the database. Used in app\Views\employees\form.php
     *
     * @param $employee_id
     * @return void
     * @noinspection PhpUnused
     */
    public function getCheckUsername($employee_id): void
    {
        $exists = $this->employee->username_exists($employee_id, $this->request->getGet('username'));
        echo !$exists ? 'true' : 'false';
    }
}
