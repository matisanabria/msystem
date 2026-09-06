# Plan: Módulo "Salidas de Inventario" (`inventory_output`)

**Fecha:** 2026-09-06
**Estado:** IMPLEMENTADO. Ver `.dev-notes/changelogs/2026-09-06-inventory-outputs-module.md` para el detalle final y hallazgos durante la implementación.

## Objetivo

Dar salida a un ítem del inventario sin que sea una venta (uso interno, dañado, muestra, pérdida/robo, devolución a proveedor, otro). Descuenta stock, no toca el reporte financiero, queda registrado en 3 lugares.

## Investigación previa (hallazgos clave)

- **Kardex ya existe**: tabla `ospos_inventory` (modelo `Inventory.php`), la usan ventas y recepción. No hay que crear un historial nuevo — insertar ahí con el mismo patrón: `trans_items, trans_user, trans_date, trans_comment, trans_inventory (delta, negativo para salida), trans_location`.
- **Stock se descuenta con** `Item_quantity::change_quantity($item_id, $location_id, -$qty)` (`app/Models/Item_quantity.php:91`) — **NO valida stock disponible**, permite negativo. El controller de salidas debe validar esto manualmente antes de llamar (ver Regla de negocio 7 abajo).
- **Permisos**: patrón `Secure_Controller('inventory_output')` + `ospos_modules`/`ospos_permissions`/`ospos_grants`, igual que migración `20260611000002_add_discount_approvals.php` (otorga grant a todos los que ya tengan `config`, heredando `menu_group`). Ícono SVG en `public/images/menubar/inventory_output.svg` (agregar con `git add -f`, está en `.gitignore`).
- **Log de auditoría**: `Activity_log::log($type, $desc, $employee_id, $location_id, $reference_id, $ip)` inserta en `ospos_activity_logs`. La vista `logs/manage.php` tiene un dropdown de tipos HARDCODEADO (`inventory`, `sale`, `ticket_status`) — hay que agregar `inventory_output` ahí y en `Activity_log::_build_union_parts()` si se quiere filtro dedicado (ya aparecerá igual bajo "Inventario" por el insert en `ospos_inventory`, pero se pide un tipo propio y distinguible).
- **Convención de soft-delete real de OSPOS**: NO es `deleted_at` (CI4 nativo) — es una columna plana `deleted TINYINT(1) NOT NULL DEFAULT 0`, filtrada a mano en cada query. Ningún modelo del proyecto usa `useSoftDeletes = true`. `ospos_inventory_outputs` sigue esta convención.

## Ajustes confirmados por el usuario

1. `ospos_inventory_outputs` incluye `inventory_trans_id` = el `trans_id` devuelto por el `Inventory::insert()` de esa salida, para poder rastrear el mismo evento entre `inventory`, `activity_logs` (vía `reference_id`) e `inventory_outputs` sin depender de fecha/ítem.
2. El controller **valida stock disponible** antes de descontar — si `quantity > stock_disponible_en_esa_sucursal`, bloquea con error, no llama a `change_quantity()`.
3. `log_type = 'inventory_output'` propio en `activity_logs` + opción nueva en el dropdown de `logs/manage.php`.

## Diseño técnico

### Migraciones

**A. `ospos_inventory_outputs`**
```sql
CREATE TABLE ospos_inventory_outputs (
    output_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id             INT NOT NULL,
    location_id         INT NOT NULL,
    quantity            DECIMAL(15,3) NOT NULL,
    reason              ENUM('internal_use','damaged','sample','loss_theft','supplier_return','other') NOT NULL,
    comment             TEXT NULL,              -- obligatorio solo si reason = 'other' (validado en controller)
    inventory_trans_id  INT NULL,               -- trans_id de ospos_inventory generado por esta salida
    person_id           INT NOT NULL,           -- quién la registró
    created_at          DATETIME NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_item (item_id),
    KEY idx_location (location_id),
    KEY idx_deleted (deleted)
);
```

**B. Módulo + permiso + grants** (mismo patrón que `20260611000002_add_discount_approvals.php`):
- INSERT en `ospos_modules`: `module_id = 'inventory_output'`, `name_lang_key = 'inventory_output'`, `desc_lang_key = 'inventory_output_desc'`, `sort` (próximo disponible, ej. 117).
- INSERT en `ospos_permissions`: `permission_id = 'inventory_output'`, `module_id = 'inventory_output'`, `location_id = NULL` (permiso global; si se quiere por-sucursal después, se agregan filas con location_id como en `items`/`expenses`).
- Otorgar grant a los que ya tengan `config`, heredando `menu_group`.

### Archivos nuevos

- `app/Models/Inventory_output.php` — CRUD de `ospos_inventory_outputs`, `allowedFields` incluye `deleted`; método `search()`/`get_info()` filtrando `deleted = 0`.
- `app/Controllers/Inventory_outputs.php` — extiende `Secure_Controller('inventory_output')`.
  - `getIndex()` / `getSearch()` — listado (excluye `deleted = 1`).
  - `getView($item_id)` o formulario embebido — selección de ítem + sucursal + cantidad + motivo + comentario.
  - `postSave()`:
    1. Valida `quantity > 0`.
    2. Valida `reason` esté en la lista fija; si `reason === 'other'`, exige `comment` no vacío.
    3. Resuelve sucursal del ítem (`items.location_id`, igual que otros módulos multi-sucursal).
    4. **Valida stock disponible**: `Item_quantity::get_item_quantity($item_id, $location_id)->quantity >= $quantity`; si no, error y no continúa.
    5. `Item_quantity::change_quantity($item_id, $location_id, -$quantity)`.
    6. `$trans_id = Inventory::insert([...trans_comment => motivo legible..., trans_inventory => -$quantity...])` (usar `insert()` que retorna el id, no el `update()` de conveniencia).
    7. Guarda `Inventory_output` con `inventory_trans_id = $trans_id`.
    8. `Activity_log::log('inventory_output', $desc, $person_id, $location_id, $output_id, $ip)` — `reference_id = output_id` (no `trans_id`, para que el link natural sea a la tabla propia; `trans_id` queda de todos modos guardado en `inventory_outputs.inventory_trans_id` para cruzar).
    9. Si algo falla a mitad de camino, usar transacción (`$this->db->transStart()/transComplete()`) para no dejar stock descontado sin log.
  - `postDelete()` — soft delete (`deleted = 1`), NO revierte el stock automáticamente (fuera de alcance pedido; si se necesita reversión se pide aparte).
- `app/Views/inventory_outputs/manage.php` — listado con filtro por sucursal/motivo/fecha (patrón `expenses/manage.php`).
- `app/Views/inventory_outputs/form.php` — formulario (dropdown de motivo con las 6 opciones, comentario condicional a "Otro", selector de sucursal si el usuario tiene más de una permitida — patrón `expenses/form.php`).
- `public/images/menubar/inventory_output.svg` — ícono (agregar con `git add -f`).

### Archivos a modificar

- `app/Language/en/Module.php` + `es-MX/Module.php` — `inventory_output`, `inventory_output_desc`.
- `app/Language/en/Inventory_outputs.php` + `es-MX/Inventory_outputs.php` (nuevo archivo de idioma) — strings de motivos, formulario, errores (incluye mensaje de "stock insuficiente").
- `app/Language/en/Logs.php` + `es-MX/Logs.php` — `type_inventory_output`.
- `app/Views/logs/manage.php` — nueva `<option>` en el dropdown + entrada en los mapas JS `typeLabel`/badge-color.
- `app/Models/Activity_log.php::_build_union_parts()` — agregar `'inventory_output'` a `$activity_types` (así el filtro por tipo funciona en la consulta UNION).
- `app/Config/Routes.php` — probablemente no hace falta (auto-routing de CI4 cubre `Inventory_outputs`), a confirmar al implementar si el nombre con guion bajo funciona igual que `Discount_approvals`.

## Motivos fijos (constante en el controller, no tabla)

```php
const REASONS = [
    'internal_use'    => 'Uso interno / corporativo',
    'damaged'         => 'Dañado / defectuoso',
    'sample'          => 'Muestra / exhibición',
    'loss_theft'      => 'Pérdida / robo',
    'supplier_return' => 'Devolución a proveedor',
    'other'           => 'Otro',
];
```

## Riesgos / cosas a cuidar al implementar

- `form_hidden()`/`form_input()` con valores int → castear siempre a `(string)` (bug conocido, ya mordió antes en `items/form.php`).
- Transacción DB alrededor de los 3 inserts (stock + inventory + inventory_outputs) + el log de activity — si algo falla, todo debe revertir para no dejar stock descontado sin rastro.
- El reporte financiero mensual (`Monthly_financial_summary.php`) filtra por `sales_items`/`expenses` — no toca `ospos_inventory` ni `ospos_inventory_outputs`, así que no requiere cambios para cumplir la regla "no impacta el reporte financiero". Confirmar al final que efectivamente no hay ningún JOIN implícito que lo arrastre.
