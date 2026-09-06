# Contexto: Implementación Multi-Sucursal (commit 791c267)

Este documento describe el estado final de la implementación multi-sucursal completada en el commit `791c267`. Sirve de referencia para continuar el trabajo en otro equipo.

---

## Resumen general

Se agregó soporte para filtrar datos por sucursal (`location_id`) en tres módulos: **Egresos**, **Asistencias** y **Reportes financieros**. El sistema ya tenía sucursales (`ospos_stock_locations`) y permisos por ubicación (`ospos_permissions` + `ospos_grants`). Lo que se hizo fue propagar ese concepto a los módulos que no lo tenían.

---

## Migracion ejecutada

**Archivo:** `app/Database/Migrations/20260415000000_add_location_to_expenses.php`

**Que hace el `up()`:**
1. Agrega columna `location_id INT(10) NULL` a `ospos_expenses`.
2. Backfill: asigna todos los egresos existentes a la primera ubicacion activa (`deleted = 0`).
3. Para cada ubicacion activa, inserta un registro en `ospos_permissions` con `module_id = 'expenses'` y `location_id` correspondiente (permission_id con formato `expenses_{nombre_location}`).
4. Otorga ese permiso (`ospos_grants`) a todos los empleados activos.

**Que hace el `down()`:**
- Elimina la columna `location_id` de `ospos_expenses`.
- Elimina los grants cuyo `permission_id` empiece con `expenses_`.
- Elimina los permissions con `module_id = 'expenses'` y `location_id NOT NULL`.

**Para correr la migracion en el nuevo equipo:**
```bash
php spark migrate
```

---

## Archivos modificados por modulo

### Fase 1 — Egresos

| Archivo | Cambio |
|---|---|
| `app/Models/Expense.php` | `location_id` en `allowedFields`; JOIN con `ospos_stock_locations`; filtro por `location_ids` en `search()` y `get_payments_summary()` |
| `app/Controllers/Expenses.php` | Obtiene ubicaciones permitidas con `get_allowed_locations('expenses')`; pasa `show_location_filter` y `show_location_select` a las vistas |
| `app/Views/expenses/form.php` | Dropdown de sucursal cuando hay mas de una |
| `app/Views/expenses/manage.php` | Filtro de sucursal en toolbar |
| `app/Helpers/tabular_helper.php` | Columna `location_name` en `expense_headers()` cuando `multiple_locations()` es true |
| `app/Language/en/Expenses.php` | Clave `"location"` agregada |
| `app/Language/es-ES/Expenses.php` | Clave `"location"` agregada |

### Fase 2 — Asistencias

La columna `location_id` **ya existia** en `ospos_assistances`. Solo faltaba filtrar.

| Archivo | Cambio |
|---|---|
| `app/Models/Assistance.php` | `search()` y `get_found_rows()` aceptan `?array $location_ids` y filtran por `assistances.location_id` |
| `app/Controllers/Assistances.php` | Obtiene `allowed_location_ids` via `get_allowed_locations('items')` y los pasa al modelo |

### Fase 3 — Reportes

| Archivo | Cambio |
|---|---|
| `app/Models/Monthly_financial_summary.php` | `getData()` acepta `location_id`; filtra ventas por `sales_items.item_location` y gastos por `expenses.location_id` |
| `app/Models/Summary_expenses_categories.php` | `getData()` y `getSummaryData()` aceptan `location_id` |
| `app/Controllers/Reports.php` | Funciones `date_input_expenses()`, `date_input_monthly()`, `summary_expenses_categories()`, `monthly_summary_sales()`, `graphical_summary_expenses_categories()` actualizadas para recibir y propagar `location_id` |
| `app/Config/Routes.php` | Rutas especificas de 5 segmentos para los reportes con selector de sucursal (ver detalle abajo) |

---

## Patron de rutas de reportes

La vista `date_input.php` siempre genera URLs con 5 segmentos al hacer submit:
```
{base_url}/{start_date}/{end_date}/{input_type}/{location_id}/{discount_type_id}
```
Defaults: `input_type=0`, `location_id='all'`, `discount_type_id=0`.

Por eso cada reporte con filtro de sucursal tiene **dos rutas**: una base que apunta a `date_input_xxx()` y una de 5 segmentos que apunta a la funcion de datos:

```php
// Egresos por categoria
$routes->add('reports/summary_expenses_categories/(:any)/(:any)/(:any)/(:any)/(:any)', 'Reports::summary_expenses_categories/$1/$2/$3/$4');
$routes->add('reports/summary_expenses_categories', 'Reports::date_input_expenses');

// Egresos por categoria (grafico)
$routes->add('reports/graphical_summary_expenses_categories/(:any)/(:any)/(:any)/(:any)/(:any)', 'Reports::graphical_summary_expenses_categories/$1/$2/$3/$4');
$routes->add('reports/graphical_summary_expenses_categories', 'Reports::date_input_expenses');

// Resumen mensual
$routes->add('reports/summary_monthly_sales/(:any)/(:any)/(:any)/(:any)/(:any)', 'Reports::monthly_summary_sales/$1/$2/$3/$4');
$routes->add('reports/summary_monthly_sales', 'Reports::date_input_monthly');
```

El selector de sucursal en la vista solo aparece cuando `count($stock_locations) > 2` (al menos 2 sucursales reales + la opcion "Todas").

---

## Patron de permisos por ubicacion

```php
// En un controller, para obtener las ubicaciones permitidas para el usuario logueado:
$stock_locations = Stock_location::get_allowed_locations('expenses'); // retorna [location_id => location_name]
$allowed_location_ids = array_keys($stock_locations);
```

El modulo `'items'` se usa como proxy para asistencias (las asistencias no tienen su propio modulo de permisos, usan el de items).

---

## Estado al momento de cambiar de equipo

- Todas las fases (1, 2 y 3) estan **completas y commiteadas** en `master`.
- La migracion `20260415000000_add_location_to_expenses.php` **debe correrse** en la BD del nuevo equipo si es una copia fresca.
- El archivo `plan_multi_sucursal.html` en el root del proyecto tiene el plan original detallado (no commiteado, solo local).
