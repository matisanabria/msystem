# 2026-09-06 — Navbar: restaurar el concepto "Oficina" (reemplaza el intento de dropdowns)

## Contexto
Primer intento para el P1 de `/impeccable critique navbar` (14 módulos en fila plana) fue agrupar en dropdowns Bootstrap. El usuario lo rechazó: pidió revertir y buscar otra vía sin dropdown, y sugirió reactivar el ícono "Oficina" que el sistema ya tenía antes.

## Revert
`git checkout --` sobre los 4 archivos tocados por el intento de dropdown (`app/Views/partial/header.php`, `public/css/ospos.css`, `app/Language/{en,es-MX}/Common.php`) — confirmado que sus diffs eran exclusivamente ese trabajo, sin mezcla con otros cambios de la sesión.

## Hallazgo: el mecanismo ya existía, apagado a propósito
El sistema tiene desde hace tiempo un concepto Home/Office completo y funcional:
- `Controllers/Home.php` y `Controllers/Office.php` (cada uno setea `menu_group` de sesión distinto)
- Vistas `home/home.php` y `home/office.php` (mismo patrón de grid de íconos, uno por contexto)
- Ícono `office.svg`, lang keys `Module.office` ya traducidas
- `Secure_Controller` decide qué set de módulos mostrar (`Module::get_allowed_home_modules()` vs `get_allowed_office_modules()`) según `menu_group` de sesión

Una migración anterior, `20260430000001_remove_office_concept.php` (2026-04-30), lo apagó a propósito: movió todos los grants `menu_group='office'` a `'home'` y ocultó el ícono `office` (sort=0), porque en ese momento no había suficientes módulos exclusivamente back-office para justificar el split. Desde entonces se agregaron `admin_panel`, `discount_approvals`, `logs`, `inventory_output` — todos back-office, ninguno tocado por un cajero — así que el split vuelve a tener sentido, exactamente para esos 4.

Además, `Module::get_allowed_home_modules()` tenía `'office'` hardcodeado en su lista de exclusión (`whereNotIn`), un bloqueo a nivel código independiente del toggle de datos — por eso aunque el módulo `office` tuviera `sort=999` (habilitado) en la DB dev, nunca aparecía igual.

## Cambio implementado

**`app/Models/Module.php`** — se quita `'office'` de la lista de exclusión en `get_allowed_home_modules()`. Ahora si hay grant, aparece.

**Migración `20260906000004_restore_office_group.php`** (nueva, no toca la migración vieja):
1. Fuerza `modules.sort = 999` para `office` (habilitado, sea cual sea el estado del toggle runtime de Config).
2. `UPDATE grants SET menu_group='office' WHERE permission_id IN (admin_panel, discount_approvals, logs, inventory_output)` — saca esos 4 del home.
3. Para cada persona que tenga alguno de esos 4 grants: asegura que tenga acceso al ícono `office` (grant `office` visible en `home`) y una vuelta (grant `home` visible en `office`) — usando **upsert a `menu_group='both'`**, nunca `INSERT` ciego, porque `(permission_id, person_id)` es la PRIMARY KEY de `ospos_grants` (un intento inicial con INSERT directo rompió con `Duplicate entry 'home-1'` porque el admin ya tenía un grant `home`/`home` previo — corregido antes de dejarlo).

**Ningún cambio en `header.php`** — el navbar ya renderiza `$allowed_modules` con un `foreach` genérico; al aparecer `office` en esa lista, se ve automáticamente con el mismo ícono+label que cualquier otro módulo. Cero código nuevo de UI.

## Resultado verificado (persona 1, admin, contra DB dev real)
- **Antes**: 14 ítems planos en Home.
- **Ahora — Home**: 11 ítems (`home, customers, service_tickets, assistances, items, suppliers, reports, receivings, sales, expenses, office`).
- **Ahora — Office** (clickeando el ícono nuevo): 5 ítems (`home, logs, admin_panel, discount_approvals, inventory_output`).

No es el ≤5 ideal en Home todavía (11 sigue siendo bastante), pero es la reducción que el usuario pidió específicamente (esos 4 módulos), sin inventar UI nueva. Mover más módulos a Office (`service_tickets, assistances, suppliers, receivings, expenses`) queda como decisión futura del usuario, no asumida acá.

## Verificación
- `php -l` limpio.
- Migración: up() y down() probados contra DB dev real (rollback limpio, sin destruir el grant `office` preexistente de otras personas — el `down()` es deliberadamente "best-effort" en el toggle home/office, mismo criterio que la migración vieja que reemplaza en efecto).
- Conteos de Home/Office simulados con queries directas idénticas a las que usa `Module.php`.
- **No verificado visualmente en navegador** (sin credenciales de sesión en este entorno). Recomendado: loguearse como admin y confirmar que el ícono "Oficina" aparece, lleva a `/office`, y que desde ahí el ícono "Inicio" vuelve.
