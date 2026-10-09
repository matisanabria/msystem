<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

/**
 * Module class
 */
class Module extends Model
{
    protected $table = 'modules';
    protected $primaryKey = 'module_id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'name_lang_key',
        'desc_lang_key',
        'sort'
    ];

    /**
     * Display order shared by the navbar and the home grid. Modules not listed keep their DB `sort`
     * and go after these (the `home` link always stays first in the navbar).
     */
    public const DISPLAY_ORDER = [
        'sales', 'customers', 'service_tickets', 'assistances', 'items',
        'receivings', 'suppliers', 'expenses', 'reports', 'office',
    ];

    /**
     * Home grid zones: lang key (Module.php) => module_ids. Empty zones are not rendered.
     */
    public const HOME_GROUPS = [
        'group_sell'    => ['sales', 'customers'],
        'group_service' => ['service_tickets', 'assistances'],
        'group_stock'   => ['items', 'receivings', 'suppliers'],
        'group_admin'   => ['expenses', 'reports', 'office'],
    ];

    /**
     * Employee form (Permissions tab): module groups, same titles as the home grid plus a leading "general"
     * zone for Inicio. Modules not listed here and not hidden go to the last group.
     */
    public const EMPLOYEE_FORM_GROUPS = [
        'group_general' => ['home'],
        'group_sell'    => ['sales', 'customers'],
        'group_service' => ['service_tickets', 'assistances'],
        'group_stock'   => ['items', 'receivings', 'suppliers'],
        'group_admin'   => ['expenses', 'expenses_categories', 'reports', 'office', 'admin_panel', 'discount_approvals', 'inventory_output', 'logs', 'config', 'employees'],
    ];

    /** Modules never offered in the employee form (disabled features). */
    public const EMPLOYEE_FORM_HIDDEN = ['attributes', 'item_kits', 'taxes', 'messages', 'giftcards', 'cashups', 'timeclocks', 'timeclocks_categories', 'migrate'];

    /** Sub-permissions never offered in the employee form. */
    public const EMPLOYEE_FORM_HIDDEN_SUBPERMISSIONS = ['reports_discounts', 'reports_taxes'];

    /**
     * Permission profiles for the employee form. Edit the lists here to change what each profile ticks.
     * 'cashier' ticks the module-level grants listed; 'admin' ticks everything offered in the form.
     */
    public const PERMISSION_PROFILES = [
        'cashier' => ['home', 'sales', 'customers', 'service_tickets'],
        'admin'   => '*',
    ];

    /** Modules that always live under "Oficina" (queries force it; new grants also use menu_group 'office'). */
    public const OFFICE_MODULES = ['admin_panel', 'discount_approvals', 'logs', 'inventory_output'];

    /** Permissions an employee cannot remove from their own account (they open this panel). */
    public const SELF_PROTECTED_PERMISSIONS = ['config', 'admin_panel', 'office'];

    /**
     * @param array $modules Rows from get_allowed_*_modules(), already in DB `sort` order.
     * @return array Same rows in display order.
     */
    public static function sortForDisplay(array $modules): array
    {
        $rank = array_flip(self::DISPLAY_ORDER);
        $pos  = 0;
        $keyed = [];
        foreach ($modules as $module) {
            $keyed[] = [
                $module->module_id === 'home' ? -1 : ($rank[$module->module_id] ?? count($rank)),
                $pos++,
                $module
            ];
        }
        usort($keyed, static fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_column($keyed, 2);
    }

    /**
     * @param string $module_id
     * @return string
     */
    public function get_module_name(string $module_id): string
    {
        $builder = $this->db->table('modules');
        $query = $builder->getWhere(['module_id' => $module_id], 1);

        if ($query->getNumRows() == 1) {    // TODO: ===
            $row = $query->getRow();

            return lang($row->name_lang_key);
        }

        return lang('Errors.unknown');
    }

    /**
     * @param string $module_id
     * @return string
     */
    public function get_module_desc(string $module_id): string    // TODO: This method doesn't seem to be called in the code.  Is it needed?  Also, probably should change the name to get_module_description()
    {
        $builder = $this->db->table('modules');
        $query = $builder->getWhere(['module_id' => $module_id], 1);

        if ($query->getNumRows() == 1) {    // TODO: ===
            $row = $query->getRow();

            return lang($row->desc_lang_key);
        }

        return lang('Errors.unknown');
    }

    /**
     * @return ResultInterface
     */
    public function get_all_permissions(): ResultInterface
    {
        $builder = $this->db->table('permissions');

        return $builder->get();
    }

    /**
     * @return ResultInterface
     */
    public function get_all_subpermissions(): ResultInterface
    {
        $builder = $this->db->table('permissions');
        $builder->join('modules AS modules', 'modules.module_id = permissions.module_id');    // TODO: can the table parameter just be modules instead of modules AS modules?

        // Can't quote the parameters correctly when using different operators
        $builder->where('modules.module_id != ', 'permission_id', false);

        return $builder->get();
    }

    /**
     * @return ResultInterface
     */
    public function get_all_modules(): ResultInterface
    {
        $builder = $this->db->table('modules');
        $builder->orderBy('sort', 'asc');

        return $builder->get();
    }

    /**
     * @param int $person_id
     * @return ResultInterface
     */
    public function get_allowed_home_modules(int $person_id): ResultInterface
    {
        $menus = ['home', 'both'];
        $builder = $this->db->table('modules');    // TODO: this is duplicated with the code below... probably refactor a method and just pass through whether home/office modules are needed.
        $builder->join('permissions', 'permissions.permission_id = modules.module_id');
        $builder->join('grants', 'permissions.permission_id = grants.permission_id');
        $builder->where('person_id', $person_id);
        $builder->whereIn('menu_group', $menus);
        // Registros, Administración, Descuentos and Salidas always live under Oficina, whatever the grant says
        $builder->whereNotIn('modules.module_id', array_merge(['expenses_categories', 'cashups', 'giftcards', 'messages', 'item_kits', 'config', 'attributes'], self::OFFICE_MODULES));
        $builder->where('sort !=', 0);
        $builder->orderBy('sort', 'asc');

        return $builder->get();
    }

    /**
     * @param int $person_id
     * @return ResultInterface
     */
    public function get_allowed_office_modules(int $person_id): ResultInterface
    {
        $menus = ['office', 'both'];
        $builder = $this->db->table('modules');    // TODO: Duplicated code
        $builder->join('permissions', 'permissions.permission_id = modules.module_id');
        $builder->join('grants', 'permissions.permission_id = grants.permission_id');
        $builder->where('person_id', $person_id);
        $builder->groupStart();
        $builder->whereIn('menu_group', $menus);
        $builder->orWhereIn('modules.module_id', self::OFFICE_MODULES);    // always under Oficina, whatever the grant says
        $builder->groupEnd();
        $builder->whereNotIn('modules.module_id', ['expenses_categories', 'cashups', 'giftcards', 'messages', 'item_kits', 'config', 'attributes']);
        $builder->where('sort !=', 0);
        $builder->orderBy('sort', 'asc');

        return $builder->get();
    }

    /**
     * This method is used to set the show the office navigation icon on the home page
     * which happens when the sort value is greater than zero
     */
    public function set_show_office_group(bool $show_office_group): void    // TODO: Should we return the value of update() as a bool for consistency?
    {
        if ($show_office_group) {    // TODO: This should be replaced with ternary notation
            $sort = 999;
        } else {
            $sort = 0;
        }

        $modules_data = ['sort' => $sort];

        $builder = $this->db->table('modules');
        $builder->where('module_id', 'office');
        $builder->update($modules_data);
    }

    /**
     * This method is used to show the office navigation icon on the home page
     * which happens when the sort value is greater than zero
     */
    public function get_show_office_group(): int
    {
        $builder = $this->db->table('modules');
        $builder->select('sort');
        $builder->where('module_id', 'office');

        return $builder->get()->getRow()->sort;
    }
}
