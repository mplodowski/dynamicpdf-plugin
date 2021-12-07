<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Gere PDFs dinâmicos e personalizados.',
    ],
    'templates' => [
        'label' => 'Modelos',
        'code' => 'Código',
        'title' => 'Título',
        'description' => 'Descrição',
        'layout' => 'Modelo',
        'empty_option' => '-- Sem modelo --',
        'code_comment' => 'Código exclusivo usado para se referir a este modelo',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Nome',
        'return' => 'Voltar à lista de modelos',
        'new_template' => 'Novo modelo',
        'new_layout' => 'Novo Layout',
        'templates' => 'Modelos',
        'layouts' => 'Layouts',
        'background_img' => 'Imagem de fundo',
        'preview_html' => 'Pré-visualizar HTML',
        'preview_pdf' => 'Pré-visualizar PDF',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'background_img_comment' => 'Use imagem com pelo menos 96 DPI para exibição correta. Recomendado 300 de DPI.',
        'size' => 'Tamanho do papel',
        'orientation' => 'Orientação do papel',
    ],
    'template' => [
        'menu_label' => 'Modelo',
        'create_template' => 'Criar Modelo',
        'edit_template' => 'Editar Modelo',
        'not_found' => 'Incapaz de encontrar um modelo registrado com código',
    ],
    'layouts' => [
        'label' => 'Layouts',
        'return' => 'Voltar à lista de layouts',
    ],
    'layout' => [
        'menu_label' => 'Layout',
        'create_layout' => 'Criar Layout',
        'edit_layout' => 'Editar Layout',
        'not_found' => 'Incapaz de encontrar um layout registrado com código',
    ],
    'settings' => [
        'description' => 'Gerencie modelos e layouts.',
    ],
    'permissions' => [
        'manage_templates' => 'Gerencie modelos',
        'manage_layouts' => 'Gerencie layouts',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'Modelos PDF',
        'category' => 'PDF',
        'description' => 'Modifique os modelos de PDF e gerencie layouts de PDF.',
    ],
    'orientation' => [
        'portrait' => 'Retrato',
        'landscape' => 'Paisagem',
    ],
    'options' => [
        'empty' => '-- escolha --',
    ],
    'tab' => [
        'options' => 'Opções',
    ],
    'demo' => [
        'enabled' => 'A demonstração está ativada. Atualize a lista de modelos de PDF.',
        'disabled' => 'A demonstração está desativada. Atualize a lista de modelos de PDF.',
    ],
];
