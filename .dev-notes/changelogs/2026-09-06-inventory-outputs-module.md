# 2026-09-06 — Módulo "Salidas de Inventario" (inventory_output)

Implementado según `.dev-notes/context/inventory-output-plan.md`.

## Archivos nuevos
- `app/Database/Migrations/20260906000003_add_inventory_outputs.php`
- `app/Models/Inventory_output.php`
- `app/Controllers/Inventory_outputs.php`
- `app/Views/inventory_outputs/manage.php` + `form.php`
- `app/Language/{en,es-MX}/Inventory_outputs.php`
- `public/images/menubar/inventory_output.svg` (gitignored, `git add -f` al commitear)

## Archivos modificados
- `app/Helpers/tabular_helper.php` — headers/data_row para el listado
- `app/Models/Activity_log.php` — `inventory_output` agregado a `$activity_types`
- `app/Views/logs/manage.php` — nueva opción de filtro + label/color
- `app/Language/{en,es-MX}/{Module,Logs}.php` — strings del módulo y del log

## Hallazgo importante durante la implementación (no estaba en el plan original)
`Stock_location::get_allowed_locations()`/`get_undeleted_all()` hacen `JOIN permissions ON permissions.location_id = stock_locations.location_id`. Un permiso único global (`location_id = NULL`, como usa `discount_approvals`) **no matchea ese join** — hubiera dejado `get_allowed_locations('inventory_output')` vacío para todos, rompiendo la búsqueda de ítems por sucursal.

Corregido: la migración crea **dos tipos de permiso**, igual que `items`/`expenses`/`service_tickets`:
1. Uno "bare" (`inventory_output`, `location_id NULL`) — necesario para que el módulo aparezca en el menú (`Module::get_allowed_home_modules()` hace `permissions.permission_id = modules.module_id`, match exacto).
2. Uno por sucursal activa (`inventory_output_Asuncion`, `inventory_output_Encarnacion`, ...) — necesario para que `get_allowed_locations()` funcione.

Ambos otorgados solo a empleados que ya tienen `config` (admins), no a todos los empleados — decisión deliberada por ser una acción sensible (puede vaciar stock sin que sea venta).

## Reglas de negocio implementadas
1. Descuenta stock vía el mismo patrón que `Sale::save_value()` usa para una venta completada (`Item_quantity::save_value()` con resta decimal-safe — **no** se usó `Item_quantity::change_quantity()` porque su parámetro `$quantity_change` está tipado `int` y truncaría cantidades fraccionarias).
2. No toca `Monthly_financial_summary` ni ninguna tabla de ventas/gastos — confirmado, no hay ningún JOIN compartido.
3. Motivo obligatorio, lista fija (`Inventory_output::REASONS`); "Otro" exige comentario (validado en `postSave()`).
4. Gateado por permiso de módulo (`Secure_Controller('inventory_output')`), sin flujo de código como descuentos.
5. Registrado en 3 lugares, todos cross-referenciados:
   - `ospos_inventory` (kardex) — mismo patrón que ventas/recepción.
   - `ospos_activity_logs` con `log_type = 'inventory_output'`, `reference_id = output_id`.
   - `ospos_inventory_outputs`, con `inventory_trans_id` guardando el `trans_id` del insert en `ospos_inventory` de ese mismo evento.
6. `ospos_inventory_outputs.deleted TINYINT(1)` (soft delete manual, no `deleted_at` — sigue la convención real de OSPOS, confirmada en la fase de investigación previa).
7. **Validación de stock agregada** (pedida explícitamente): si `quantity > stock_disponible_en_esa_sucursal`, bloquea antes de tocar nada y muestra error con el stock real disponible.

## Verificación hecha
- `php -l` en todos los archivos nuevos/modificados — sin errores.
- Migración corrida contra DB dev real: tabla creada, módulo + permiso bare + permisos por sucursal (Asunción, Encarnación) + grants a admins — verificado con queries directas. `down()` probado (rollback limpio) y re-aplicado.
- Simulación completa del flujo de escritura (stock → kardex → inventory_outputs → activity_log) en una transacción SQL de prueba (con ROLLBACK al final, sin tocar datos reales): confirmado que `inventory_outputs.inventory_trans_id` enlaza con `inventory.trans_id`, y `activity_logs.reference_id` enlaza con `inventory_outputs.output_id`. Stock decrementado correctamente.
- Encontrado (y evitado en el código nuevo, no corregido en el existente por estar fuera de alcance) un bug preexistente compartido con `Expenses`: ordenar por la columna `created_by` en el listado rompe con "Unknown column" porque no hay alias SQL con ese nombre — se marcó `sortable: false` en `inventory_output_headers()` para no heredar el bug.

## Pendiente / no probado
- No se probó el flujo completo en navegador (autocomplete de ítem, modal, submit real vía HTTP). Recomendado antes de que el usuario final lo use: crear una salida real desde `/inventory_outputs`, confirmar que aparece en `/logs` y que el stock del ítem bajó en `/items`.
