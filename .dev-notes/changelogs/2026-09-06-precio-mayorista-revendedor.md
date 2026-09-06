# 2026-09-06 — Precio Mayorista y Revendedor

## Qué se implementó

Feature completa: 3 precios por ítem (venta/mayorista/revendedor), selección manual por línea de venta con autorización de admin (mismo mecanismo de código de 4 dígitos que descuentos), y desglose en reporte financiero mensual.

## Migraciones (corridas y probadas contra DB dev — up y down verificados)

- `20260906000000_add_wholesale_reseller_prices` — `ospos_items` + `price_wholesale`, `price_reseller` (backfill = `unit_price`)
- `20260906000001_extend_discount_approvals_for_price_type` — `ospos_discount_approvals` + `request_type` ENUM('discount','price_type'), `price_type` TINYINT
- `20260906000002_add_price_type_to_sales_items` — `ospos_sales_items` + `price_type` TINYINT DEFAULT 0, `price_approval_id`

## Archivos modificados

- `app/Models/Item.php` — `price_wholesale`/`price_reseller` en allowedFields
- `app/Models/Discount_approval.php` — generalizado: `create_price_request()`, `check_price_code()`, `verify_price()` (reutiliza tabla/flujo de descuentos vía `request_type`)
- `app/Controllers/Sales.php` — `postPriceRequest`, `postPriceVerify`; `postEditItem` valida cambio de `price_type` (auth solo al pasar a mayorista/revendedor, no al volver a venta); precio final es **autoritativo del servidor** (lookup en `Item::get_info()`, ignora precio enviado por cliente)
- `app/Libraries/Sale_lib.php` — `add_item()` guarda `price_type=0` + `price_wholesale`/`price_reseller` del ítem en la línea del carrito; `edit_item()` acepta `$price_type`
- `app/Models/Sale.php` — guarda `price_type` al insertar `sales_items`
- `app/Controllers/Items.php` + `app/Views/items/form.php` — inputs de precio mayorista/revendedor en ficha de ítem
- `app/Views/sales/register.php` — dropdown de tipo de precio por línea + modal de autorización "Price Type" (`#pa_modal`, paralelo a `#da_modal` de descuentos, mismo patrón de polling/código)
- `app/Controllers/Discount_approvals.php` + `app/Views/discount_approvals/index.php` — panel admin distingue solicitudes de descuento vs. cambio de precio
- `app/Models/Reports/Monthly_financial_summary.php` + `app/Controllers/Reports.php` + `app/Views/reports/monthly_financial_summary.php` — desglose de ingresos por mayorista/revendedor
- Language `en/` + `es-MX/`: `Items.php`, `Sales.php`, `Reports.php`

## Decisiones de diseño

- Autorización requerida **solo** al elegir mayorista/revendedor, no al volver a venta normal (fricción mínima, evita abuso de precio bajo).
- Precio aplicado es el guardado en el ítem (server-side), no lo que mande el cliente — evita manipulación del monto vía DevTools.
- Autorización por línea, no por venta completa (mismo patrón que descuentos).

## Verificación hecha

- `php -l` en todos los archivos tocados — sin errores.
- Migraciones corridas contra DB dev real (`ospos`): up + rollback (down) + up de nuevo — schema y backfill verificados por query directa.
- MySQL de XAMPP quedó corriendo (estaba apagado al iniciar la sesión) para que el usuario pueda probar el flujo en el navegador.

## Pendiente / no cubierto

- Import CSV de ítems no llena `price_wholesale`/`price_reseller` (quedan NULL → fallback a `unit_price` en runtime). Aceptable, fuera del alcance pedido.
- No se probó el flujo end-to-end en navegador (solo migraciones + lint). Recomendado antes de ir a producción: abrir una venta, elegir mayorista/revendedor en una línea, aprobar desde `/discount_approvals`, confirmar código, y chequear el reporte mensual.
