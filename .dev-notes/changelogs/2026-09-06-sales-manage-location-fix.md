# 2026-09-06 — Fix: totales de pagos en sales/manage mezclaban sucursales

## Reporte del usuario
En `/sales/manage`, el resumen de totales (Efectivo, etc.) debajo de la tabla mostraba el total de todas las sucursales aunque se filtrara por una sola (ej. Asunción incluía las ventas de Encarnación).

## Causa raíz (más grave de lo reportado)
`Sale::get_payments_summary()` (`app/Models/Sale.php`) nunca aplicaba el filtro `location_id` — bug #1, el reportado.

Pero además tenía un bug preexistente no relacionado con sucursales: hacía `LEFT JOIN sales_items` directo sobre la consulta que agrupa y suma pagos. Una venta con N ítems multiplica cada fila de `sales_payments` por N, inflando `SUM(payment_amount)`. Verificado con datos reales: Efectivo mostraba ₲138.495.000 cuando el real es ₲96.195.000 (44% de más). Este bug existía para TODAS las sucursales, no solo al filtrar.

## Fix
Reemplazado el JOIN directo con subqueries `EXISTS`/`IN` para las condiciones que necesitan `sales_items` (búsqueda por código de barra, filtro de sucursal) — no multiplican filas porque no se unen a la consulta principal de agregación.

## Verificación
Comparado con SQL directo contra la DB dev:
- Total general (sin filtro): ₲96.195.000 (antes: ₲138.495.000 — bug)
- Asunción: ₲96.190.000 + Encarnación: ₲5.000 = ₲96.195.000 (suma exacta, sin fuga)

## Archivo modificado
`app/Models/Sale.php` — `get_payments_summary()`
