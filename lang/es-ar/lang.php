<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Genera PDF dinámicos y personalizados.',
    ],
    'templates' => [
        'label' => 'Plantillas PDF',
        'code' => 'Código',
        'title' => 'Título',
        'description' => 'Descripción',
        'layout' => 'Diseño',
        'empty_option' => '-- Ningúno --',
        'code_comment' => 'Código único para hacer referencia a esta plantilla',
        'content_html' => 'HTML',
        'content_css' => 'CSS',
        'name' => 'Nombre',
        'return' => 'Volver a la lista de plantillas PDF',
        'new_template' => 'Nueva Plantilla',
        'new_layout' => 'Nuevo Diseño',
        'templates' => 'Plantillas',
        'layouts' => 'Diseños',
        'delete_alert' => 'Realmente quiere eliminar ésta plantilla?',
        'background_img' => 'Imagen de fondo',
        'preview_pdf' => 'Previsualizar',
        'created_at' => 'Creado en',
        'updated_at' => 'Modificado en',
        'background_img_comment' => 'Utilize una imagen de 96 DPI para una correcta visualización'
    ],
    'template' => [
        'menu_label' => 'Plantilla PDF',
        'create_template' => 'Crear Plantilla PDF',
        'edit_template' => 'Editar Plantilla PDF',
    ],
    'layouts' => [
        'label' => 'Diseños PDF',
        'return' => 'Volver la la lista de diseños PDF',
        'delete_alert' => 'Realmente quiere eliminar éste diseño?',
    ],
    'layout' => [
        'menu_label' => 'Diseño PDF',
        'create_layout' => 'Crear Diseño PDF',
        'edit_layout' => 'Editar Diseño PDF',
    ],
    'settings' => [
        'description' => 'Administrar plantillas y diseños PDF.',
    ],
    'permissions' => [
        'label' => 'Administrar plantillas PDF',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF'
    ]
];
