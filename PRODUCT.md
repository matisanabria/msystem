# Product

## Register

product

## Users

Cajeros y administradores de NT CellStore, tienda de celulares en Paraguay con 2 sucursales (Asunción, Encarnación). Los cajeros usan el POS a diario en mostrador, a menudo con cola de clientes esperando y luz fuerte de local. Los administradores usan reportes, aprobación de descuentos, gestión de inventario y sucursales desde back-office.

## Product Purpose

Sistema POS (OSPOS v3.4.2 sobre CodeIgniter 4) para vender celulares, accesorios y perfumes, con inventario separado por sucursal. Éxito = el cajero completa una venta rápido sin fricción, y el admin puede confiar en los números (reportes, stock, autorizaciones) sin ambigüedad.

## Brand Personality

Rápido y sin fricción. La UI prioriza que el cajero opere bajo presión de cola de clientes: directa, sin adornos, sin pasos de más. No es una herramienta que busca impresionar, busca no estorbar.

## Anti-references

No debe parecer una planilla de cálculo vieja tipo Excel (look legacy, tablas densas sin jerarquía, controles minúsculos). Tampoco se pidió que emule dashboards SaaS genéricos de métricas/cards — la prioridad es la tarea del cajero, no la estética de panel de control.

## Design Principles

- Velocidad de cajero por encima de todo: menos clics, menos pasos, sin fricción en la ruta de venta.
- Legibilidad bajo luz fuerte de mostrador: contraste alto, texto y controles nunca ambiguos a simple vista.
- Directo, no decorativo: cada elemento visual debe ayudar a operar rápido, no vestir la interfaz.
- Confiabilidad numérica: en reportes y totales, la jerarquía visual debe dejar clarísimo qué número es qué (evitar ambigüedad tipo planilla).

## Technical note

UI corre sobre Bootstrap 3 actualmente; migración a Bootstrap 5 planeada a futuro. Tratar el estado visual actual como transición temporal, no como sistema final a perfeccionar en detalle.

## Accessibility & Inclusion

Sin requisito formal de WCAG. Prioridad práctica: alto contraste y legibilidad en condiciones de luz de local (mostrador), tipografía y controles con tamaño suficiente para uso rápido bajo presión.
