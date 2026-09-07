<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Создание динамических и настраиваемых PDF-документов.',
    ],
    'templates' => [
        'label' => 'Шаблоны',
        'code' => 'Код',
        'title' => 'Заголовок',
        'description' => 'Описание',
        'layout' => 'Макет',
        'empty_option' => '-- Без макета --',
        'code_comment' => 'Уникальный код для обращения к этому шаблону',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Название',
        'return' => 'Вернуться к списку шаблонов',
        'new_template' => 'Новый шаблон',
        'new_layout' => 'Новый макет',
        'templates' => 'Шаблоны',
        'layouts' => 'Макеты',
        'background_img' => 'Фоновое изображение',
        'preview_html' => 'Предпросмотр HTML',
        'preview_pdf' => 'Предпросмотр PDF',
        'created_at' => 'Создано',
        'updated_at' => 'Обновлено',
        'background_img_comment' => 'Для корректного отображения используйте изображение не менее 96 DPI. Рекомендуется 300 DPI.',
        'size' => 'Формат бумаги',
        'orientation' => 'Ориентация бумаги',
        'sample_data' => 'Пример данных',
        'sample_data_comment' => 'Объект JSON с переменными для предпросмотра в панели, например {"name": "Иван Иванов"}.',
        'is_custom' => 'Изменён',
        'is_locked' => 'Заблокирован',
        'preview' => 'Предпросмотр',
        'duplicate' => 'Дублировать',
        'duplicating' => 'Дублирование...',
        'duplicate_success' => 'Копия создана.',
    ],
    'template' => [
        'menu_label' => 'Шаблон',
        'create_template' => 'Создать шаблон',
        'edit_template' => 'Редактировать шаблон',
        'not_found' => 'Не найден зарегистрированный шаблон с кодом',
    ],
    'layouts' => [
        'label' => 'Макеты',
        'return' => 'Вернуться к списку макетов',
    ],
    'layout' => [
        'menu_label' => 'Макет',
        'create_layout' => 'Создать макет',
        'edit_layout' => 'Редактировать макет',
        'not_found' => 'Не найден зарегистрированный макет с кодом',
    ],
    'settings' => [
        'description' => 'Управление шаблонами и макетами.',
    ],
    'permissions' => [
        'manage_templates' => 'Управление шаблонами',
        'manage_layouts' => 'Управление макетами',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'Шаблоны PDF',
        'category' => 'PDF',
        'description' => 'Изменение шаблонов PDF и управление макетами PDF.',
    ],
    'orientation' => [
        'portrait' => 'Книжная',
        'landscape' => 'Альбомная',
    ],
    'options' => [
        'empty' => '-- выберите --',
    ],
    'tab' => [
        'options' => 'Параметры',
    ],
    'demo' => [
        'enabled' => 'Демо включено. Обновите список шаблонов PDF.',
        'disabled' => 'Демо отключено. Обновите список шаблонов PDF.',
    ],
];
