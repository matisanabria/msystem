# Contexto del negocio — NT CellStore

## Qué es
Sistema POS (este repo, OSPOS v3.4.2 sobre CodeIgniter 4) usado en producción por
**NT CellStore**, tienda de celulares en Paraguay.

## Sucursales
- Asunción
- Encarnación

Cada ítem de inventario pertenece a UNA sola sucursal (`location_id` en `ospos_items`).
Ver `.dev-notes/context/multi-sucursal-plan.md` e `implementation.md` para el detalle técnico.

## Rubro / productos
- Celulares iPhone
- Celulares Android
- Accesorios (fundas, cargadores, etc.)
- Perfumes

## Objetivo de mantenimiento
- Mantener el sistema optimizado y actualizado (no solo agregar features).
- Priorizar estabilidad en operación real de tienda (POS en uso diario, no downtime tolerable en horario comercial).

## Convención de estas notas (`.dev-notes/`)
- `changelogs/YYYY-MM-DD.md` — un archivo por sesión/día de trabajo, resumen de qué se hizo y por qué.
- `context/` — documentos de contexto técnico de features grandes (arquitectura, decisiones, migraciones).
- Este archivo (`PROJECT_CONTEXT.md`) — contexto de negocio estable, se actualiza solo cuando cambia algo real del negocio (nueva sucursal, nuevo rubro, etc.), no por cada sesión.
