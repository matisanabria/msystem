# Plan Multi-Sucursal — Progreso

**Fecha inicio:** 2026-04-27  
**Rama:** master  
**Estado general:** FASES 0 y 1 COMPLETAS

---

## Objetivo

Implementar separación total de datos por sucursal:
- Inventario separado por sucursal (cada ítem pertenece a UNA sola sucursal)
- Ventas, egresos, asistencias y fichas técnicas por sucursal
- Usuario ve solo su sucursal; admin puede ver todas

---

## Decisiones confirmadas

- [x] Catálogo completamente separado por sucursal. Cada ítem pertenece a UNA sola sucursal.
- [x] El IMEI va como barcode (`item_number`), identifica el equipo físico.
- [x] Mismo barcode permitido en distintas sucursales, bloqueado dentro de la misma.
- [x] Asistencias ligadas a permisos de `items` (sin cambio de permisos).

---

## Fase 0 — Items con location_id + barcode por sucursal ✅ COMPLETA

### Migraciones aplicadas en producción
- `20260428000000_add_location_to_items` — ADD `location_id` a `ospos_items`, backfill a primera sucursal activa
- `20260428000001_allow_barcode_per_location` — DROP UNIQUE `item_number` global, ADD UNIQUE `(item_number, location_id)`. Maneja duplicados: soft-delete activos duplicados, NULL item_number en borrados que conflictúen.

### Archivos modificados
- `app/Models/Item.php`
  - `'location_id'` en `$allowedFields`
  - `search()`: filtro `whereIn('items.location_id', $allowed_location_ids)` cuando no hay stock_location_id específico
  - `get_all()`: filtro por `items.location_id` cuando se pasa stock_location_id
  - `item_number_exists(string $item_number, string $item_id = '', ?int $location_id = null)`: acepta location_id para unicidad por sucursal; si null → chequeo global

- `app/Controllers/Items.php`
  - `getSearch()`: pasa `allowed_location_ids` al filtro
  - `getView()`: split NEW_ENTRY vs existente; pasa `$item_location_id` y `$allowed_locations` a la vista
  - `postSave()`: determina `location_id` del POST o del ítem existente; guarda solo `quantity_{location_id}` del ítem
  - `postCheckItemNumber()`: lee `location_id` del POST, lo pasa a `item_number_exists()`

- `app/Views/items/form.php`
  - Selector de sucursal (dropdown si nuevo + múltiples sucursales, hidden si no)
  - `form_hidden('location_id', (string)...)` — cast a string requerido por CI4
  - `$item_info->item_id < 0` para detectar nuevo ítem correctamente
  - Validación AJAX de barcode: pasa `location_id` via `$('[name="location_id"]').val()`
  - `onkeyup: false` — error de barcode solo aparece al enviar, no al escribir

- `app/Models/Stock_location.php`
  - Eliminado backfill de `item_quantities` al crear nueva sucursal
  - `get_location_name()`: null-safe `?->location_name ?? ''`

- `app/Language/en/Common.php` + `es-MX/Common.php`
  - Agregado `'location' => 'Location'` / `'Sucursal'`

### Bug importante resuelto
`form_hidden('location_id', $int)` lanzaba `TypeError` en CI4 porque requiere string.
Causa: el modal del formulario quedaba en blanco (AJAX devolvía HTTP 500 y jQuery no ejecutaba el callback).

---

## Fase 1 — Fichas técnicas con sucursal ✅ COMPLETA

### Migración aplicada
- `20260429000000_add_location_to_service_tickets` — ADD `location_id` a `ospos_service_tickets`, backfill a primera sucursal, crea permisos `service_tickets_{name}` para cada sucursal activa

### Archivos modificados
- `app/Models/Service_ticket.php`: `'location_id'` en allowedFields; `search()` + `get_found_rows()` aceptan `?array $location_ids` para filtro whereIn
- `app/Controllers/Service_tickets.php`: importa Stock_location; `getIndex()` pasa `stock_locations`/`show_location_filter`; `getSearch()` filtra por allowed_locations + location_id GET; `getView()` pasa `ticket_location_id`/`stock_locations`/`show_location_select`; `postSave()` guarda `location_id`
- `app/Views/service_tickets/form.php`: dropdown/hidden de sucursal (patrón expenses)
- `app/Views/service_tickets/manage.php`: filtro toolbar + queryParams con location_id (patrón expenses)
- `app/Models/Stock_location.php`: agrega `service_tickets` a `_insert_new_permission` en ambos bloques de `save_value()`; elimina backfill de `item_quantities`; `get_location_name()` null-safe

## Fase 2 — Asistencias: selector en formulario ✅ COMPLETA

- `app/Controllers/Assistances.php`: getView() pasa location vars; postSave() lee location_id del POST
- `app/Views/assistances/form.php`: dropdown/hidden de sucursal (patrón expenses)
- Sigue usando proxy `items_*` (sin nueva migración de permisos)

## Fase 3 — Stock_location: registrar nuevos módulos ✅ COMPLETA

- `Stock_location::save_value()`: `service_tickets` agregado en ambos bloques

## Fase 4 — Language strings ✅ COMPLETA

- Todas las vistas usan `lang('Common.location')` — ya existía en en/ + es-MX/
- `Module.php` (en + es-MX): agregado `admin_panel` + `admin_panel_desc`

## Fase 5 — Panel de administración ✅ COMPLETA

- Ruta: GET `/admin_panel`, POST `/admin_panel/createBranch|deleteBranch|toggleAccess`
- `app/Controllers/Admin_panel.php`: Secure_Controller('config'); CRUD sucursales + toggle grants empleados
- `app/Views/admin_panel/manage.php`: 2 tabs — Sucursales (crear/eliminar) + Empleados (matriz de acceso por sucursal)
- Módulos cubiertos al togglear acceso: items, sales, receivings, expenses, service_tickets

---

## Patrones a reutilizar

- Dropdown sucursal en form → `app/Views/expenses/form.php`
- Filtro toolbar → `app/Views/expenses/manage.php`
- Controller allowed_locations → `app/Controllers/Expenses.php`
- Migración de permisos → `app/Database/Migrations/20260415000000_add_location_to_expenses.php`
- Filtro whereIn en search() → `app/Models/Expense.php`
