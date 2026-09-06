---
target: navbar (app/Views/partial/header.php)
total_score: 21
p0_count: 0
p1_count: 2
timestamp: 2026-09-06T20-38-38Z
slug: app-views-partial-header-php
---
## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Discount-approval badge exists but no aria-live |
| 2 | Match System / Real World | 3 | Labels traducidos, iconos + texto |
| 3 | User Control and Freedom | 3 | Toggle hamburger estandar |
| 4 | Consistency and Standards | 1 | Dos lenguajes de icono distintos en la misma fila |
| 5 | Error Prevention | 3 | n/a en nav |
| 6 | Recognition Rather Than Recall | 2 | 14 items sin agrupar exige escaneo completo |
| 7 | Flexibility and Efficiency | 1 | Sin favoritos/pin, sin shortcuts |
| 8 | Aesthetic and Minimalist Design | 1 | Wall of options: 14 items planos |
| 9 | Error Recovery | 3 | n/a en nav |
| 10 | Help and Documentation | 2 | Solo title nativo |
| Total | | 21/40 | Acceptable |

## Anti-Patterns Verdict
No parece AI-generado; es Bootstrap 3 heredado sin curaduria de IA (acumulacion de modulos sin replantear taxonomia). Detector encontro flat-type-hierarchy (9/12/14/16px) - el 9px es el badge de pendientes, choca con el principio de legibilidad de PRODUCT.md.

## Priority Issues

[P1] 14 modulos en una fila plana sin agrupar (verificado contra DB real, admin ve 14 items de nivel superior). Viola regla de <=5 items de nav. Fix: agrupar por dominio de tarea (cajero vs back-office). Suggested: /impeccable layout

[P1] Dos lenguajes de icono en la misma fila: items/sales/service_tickets/backup usan estilo circular-relleno 64x64; discount_approvals/inventory_output usan line-icon 24x24 stroke. Fix: unificar. Suggested: /impeccable audit, luego /impeccable polish

[P2] Badge de notificacion a 9px sin aria-live, contradice principio de legibilidad del proyecto. Suggested: /impeccable harden

[P2] Sin mecanismo de eficiencia para uso repetido (sin favoritos/orden reciente/shortcuts). Suggested: /impeccable optimize

## Persona Red Flags
Alex: re-escanea 14 iconos sin agrupar cada vez, sin shortcuts.
Sam: badge 9px casi ilegible, sin aria-live.
Cajero (PRODUCT.md): ve los mismos 14 modulos que el admin aunque solo usa 2-3 a diario.

## Minor Observations
<br> entre icono y texto es maquetacion no semantica. navbar-toggle sin aria-expanded.

## Questions to Consider
Realmente todos los 14 modulos necesitan ser nivel superior? Navbar deberia variar por rol?
