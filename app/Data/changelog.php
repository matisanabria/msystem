<?php

/**
 * Customer-facing changelog (Oficina > Changelog). Newest first.
 *
 * Add one entry per change the client can see. Plain Spanish, no technical terms, but concrete:
 * say what changed and where to find it.
 *
 * Fields:
 *   date  YYYY-MM-DD
 *   type  'nuevo' | 'mejora' | 'arreglo'
 *   title short name of the change
 *   text  one or two lines of summary
 *   items (optional) bullet list with the specific changes
 */
return [
    [
        'date'  => '2026-11-17',
        'type'  => 'nuevo',
        'title' => 'Nueva sección Changelog en Oficina',
        'text'  => 'Desde Oficina se puede abrir el Changelog para ver la lista de cambios y mejoras que se van haciendo al sistema.',
        'items' => [
            'Se entra desde el enlace "Changelog del sistema", abajo a la derecha en Oficina.',
            'Cada cambio muestra la fecha, si es algo nuevo, una mejora o un arreglo, y una explicación de qué cambió.',
            'Solo la ven los usuarios con permiso. Se puede dar o quitar desde la ficha de cada empleado.',
        ],
    ],
    [
        'date'  => '2026-11-17',
        'type'  => 'mejora',
        'title' => 'Mejoras de seguridad y estabilidad',
        'text'  => 'Se actualizó la base del sistema a su última versión para mantenerlo seguro y funcionando bien.',
        'items' => [
            'Se actualizó el motor del sistema a su versión más reciente, que corrige fallas de seguridad conocidas.',
            'Se actualizó el generador de PDF (recibos y facturas) y se le quitó una función que no se usa y podía ser un riesgo.',
            'Se reforzó la subida de fotos de productos y del logo del negocio.',
            'Se quitó un registro interno innecesario que llenaba los archivos de registro del sistema.',
            'No hay cambios en la forma de trabajar: todo se ve y funciona igual.',
        ],
    ],
    [
        'date'  => '2026-11-17',
        'type'  => 'mejora',
        'title' => 'Consulta de stock renovada',
        'text'  => 'La pantalla de consulta de stock (Ventas > Consulta de stock) es más cómoda y completa para responder rápido a un cliente.',
        'items' => [
            'Se ve el stock de una sucursal a la vez. La sucursal se elige al lado del buscador y por defecto aparece la de la caja.',
            'Cada producto muestra su foto, y con un clic se agranda, igual que en Artículos.',
            'Al abrir la pantalla el cursor ya está en el buscador. Busca por nombre, código de barras o categoría, y con la tecla "/" se vuelve al buscador.',
            'Se puede ordenar por cualquier columna (nombre, precio, cantidad) haciendo clic en su título.',
            'Los productos agotados quedan ocultos, pero se pueden ver activando "Incluir agotados".',
            'Cada producto con stock tiene un botón "Agregar" que lo pasa directo a la venta (disponible cuando la sucursal consultada es la de la caja).',
            'Mejor paginación, con la cantidad de resultados y la opción de ver 25, 50 o 100 por página, y una tabla con el mismo estilo que Artículos.',
        ],
    ],
];
