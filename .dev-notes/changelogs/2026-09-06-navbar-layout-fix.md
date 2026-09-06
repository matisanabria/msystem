# 2026-09-06 — Navbar: agrupar módulos (P1 de la critique)

Fix del hallazgo P1 más severo de `/impeccable critique navbar`: 14 módulos en una fila plana sin agrupar, violando la regla de ≤5 ítems de nav (cognitive-load.md) por ~3x.

## Cambio (revisado: 3 dropdowns temáticos, no uno solo)

Feedback del usuario: un dropdown único de 10 ítems no es mejor que la fila plana, solo la esconde. Además el cajero real solo tiene grants de `sales/items/customers/home` — los módulos de sucursal/back-office nunca los ve igual (el filtro de permisos ya pasa por `$allowed_modules`), así que agrupar bien es principalmente para el admin, que sí ve los 14.

`app/Views/partial/header.php` — separa `$allowed_modules` en:
- **Primario** (visible inline, sin cambios de estilo): `home, sales, items, customers`.
- **Compras** (dropdown, glyphicon-shopping-cart): `receivings, suppliers, inventory_output`.
- **Reportes** (dropdown, glyphicon-stats): `reports, expenses`.
- **Administración** (dropdown, glyphicon-cog): `admin_panel, discount_approvals, logs, assistances, service_tickets`.
- Cualquier módulo futuro no mapeado explícitamente cae por defecto en "Administración" (red de seguridad, no desaparece silenciosamente).

Cada dropdown solo se renderiza si el usuario tiene al menos un módulo de ese grupo — un cajero sin grants de back-office no ve ningún dropdown, solo sus 4 ítems primarios.

Verificado contra datos reales: para el admin (person_id=1) esto pasa de 14 ítems planos a 4 primarios + 3 dropdowns (3/2/5 ítems cada uno), sin ningún módulo sin mapear.

- El `<li class="dropdown">` recibe `active` si la página actual corresponde a un módulo secundario, y el estilo pill-blanco existente (`.navbar-default .navbar-nav>.active>a`) se aplica automáticamente al toggle también (mismo selector, sin CSS nuevo necesario para eso).
- Mobile: agregado bloque CSS para que el dropdown expandido (comportamiento nativo de Bootstrap 3 en navbar colapsado: `position:static`) tenga el mismo tratamiento de fila táctil que los ítems primarios (ícono+label, padding, divisor, estado activo), con una indentación (`padding-left:30px`) para dejar claro que es un submenú.
- Desktop: tamaño del glyphicon del toggle ajustado a 20px para calzar visualmente con los íconos de 24px de los ítems primarios.

## Archivos modificados
- `app/Views/partial/header.php`
- `public/css/ospos.css` (glyphicon sizing + dropdown padding desktop)
- `app/Language/{en,es-MX}/Common.php` — nueva key `more` ("More" / "Más")

## No tocado (fuera de alcance de este fix)
- `app/Views/home/home.php` / `home/office.php` — el grid de íconos de la página de inicio es una vista separada, no el navbar; tiene el mismo problema potencial pero no era el target de la critique.
- Unificación de estilo de íconos (P1 #2 de la critique) — pendiente, se ataca en la siguiente pasada (`/impeccable audit` + `/impeccable polish`).
- Badge 9px y favoritos (P2) — pendientes.

## Verificación
- `php -l` limpio en todos los archivos.
- Grouping verificado contra el set real de módulos de un admin en la DB dev (14 → 4 primarios + 10 en dropdown).
- **No verificado visualmente en navegador** — el navbar vive detrás de login y no tengo credenciales de sesión en este entorno. Recomendado: loguearse y confirmar que el dropdown "Más" se ve y funciona bien en desktop y mobile antes de dar por cerrado el fix.
