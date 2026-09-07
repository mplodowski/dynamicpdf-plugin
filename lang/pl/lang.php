<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Generuj dynamiczne pliki PDF.',
    ],
    'templates' => [
        'label' => 'Szablony',
        'code' => 'Kod',
        'title' => 'Tytuł',
        'description' => 'Opis',
        'layout' => 'Układ',
        'empty_option' => '-- Brak układu --',
        'code_comment' => 'Unikalny kod powiązany z tym szablonem',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Nazwa',
        'return' => 'Powrót do listy szablonów',
        'new_template' => 'Nowy szablon',
        'new_layout' => 'Nowy układ',
        'templates' => 'Szablony',
        'layouts' => 'Układy',
        'background_img' => 'Obraz w tle',
        'preview_html' => 'Podgląd HTML',
        'preview_pdf' => 'Podgląd PDF',
        'created_at' => 'Data utworzenia',
        'updated_at' => 'Data edycji',
        'background_img_comment' => 'Użyj obrazu o rozdzielczości przynajmniej 96 DPI do poprawnego wyświetlenia. Zalecany obraz 300 DPI.',
        'size' => 'Rozmiar papieru',
        'orientation' => 'Orientacja papieru',
        'sample_data' => 'Dane przykładowe',
        'sample_data_comment' => 'Obiekt JSON ze zmiennymi, z którymi renderowany jest podgląd w panelu, np. {"name": "Jan Kowalski"}.',
        'is_custom' => 'Dostosowany',
        'is_locked' => 'Zablokowany',
        'preview' => 'Podgląd',
        'duplicate' => 'Duplikuj',
        'duplicating' => 'Duplikowanie...',
        'duplicate_success' => 'Kopia została utworzona.',
    ],
    'template' => [
        'menu_label' => 'Szablon',
        'create_template' => 'Utwórz szablon',
        'edit_template' => 'Edycja szablonu',
        'not_found' => 'Nie znaleziono zarejestrowanego szablonu o kodzie',
    ],
    'layouts' => [
        'label' => 'Układy',
        'return' => 'Powrót do listy układów',
    ],
    'layout' => [
        'menu_label' => 'Układ',
        'create_layout' => 'Utwórz układ',
        'edit_layout' => 'Edycja układu',
        'not_found' => 'Nie znaleziono zarejestrowanego układu o kodzie',
    ],
    'settings' => [
        'description' => 'Zarządzaj szablonami i układami.',
    ],
    'permissions' => [
        'manage_templates' => 'Zarządzaj szablonami',
        'manage_layouts' => 'Zarządzaj układami',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'Szablony PDF',
        'category' => 'PDF',
        'description' => 'Modyfikuj szablony i układy PDF.',
    ],
    'orientation' => [
        'portrait' => 'Pionowa',
        'landscape' => 'Pozioma',
    ],
    'options' => [
        'empty' => '-- wybierz --',
    ],
    'tab' => [
        'options' => 'Opcje',
    ],
    'demo' => [
        'enabled' => 'Demo włączone. Odśwież listę szablonów PDF.',
        'disabled' => 'Demo wyłączone. Odśwież listę szablonów PDF.',
    ],
];
