<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Genereer dynamische en aangepaste PDF-bestanden.',
    ],
    'templates' => [
        'label' => 'Sjablonen',
        'code' => 'Code',
        'title' => 'Titel',
        'description' => 'Omschrijving',
        'layout' => 'Lay-out',
        'empty_option' => '-- Geen lay-out --',
        'code_comment' => 'Unieke code waarmee naar dit sjabloon wordt verwezen',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Naam',
        'return' => 'Terug naar de sjablonenlijst',
        'new_template' => 'Nieuw sjabloon',
        'new_layout' => 'Nieuwe lay-out',
        'templates' => 'Sjablonen',
        'layouts' => 'Lay-outs',
        'background_img' => 'Achtergrondafbeelding',
        'preview_html' => 'HTML-voorbeeld',
        'preview_pdf' => 'PDF-voorbeeld',
        'created_at' => 'Aangemaakt op',
        'updated_at' => 'Bijgewerkt op',
        'background_img_comment' => 'Gebruik een afbeelding van minimaal 96 DPI voor een correcte weergave. 300 DPI aanbevolen.',
        'size' => 'Papierformaat',
        'orientation' => 'Papieroriëntatie',
    ],
    'template' => [
        'menu_label' => 'Sjabloon',
        'create_template' => 'Sjabloon aanmaken',
        'edit_template' => 'Sjabloon bewerken',
        'not_found' => 'Geen geregistreerd sjabloon gevonden met de code',
    ],
    'layouts' => [
        'label' => 'Lay-outs',
        'return' => 'Terug naar de lay-outlijst',
    ],
    'layout' => [
        'menu_label' => 'Lay-out',
        'create_layout' => 'Lay-out aanmaken',
        'edit_layout' => 'Lay-out bewerken',
        'not_found' => 'Geen geregistreerde lay-out gevonden met de code',
    ],
    'settings' => [
        'description' => 'Beheer sjablonen en lay-outs.',
    ],
    'permissions' => [
        'manage_templates' => 'Sjablonen beheren',
        'manage_layouts' => 'Lay-outs beheren',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF-sjablonen',
        'category' => 'PDF',
        'description' => 'PDF-sjablonen aanpassen en PDF-lay-outs beheren.',
    ],
    'orientation' => [
        'portrait' => 'Staand',
        'landscape' => 'Liggend',
    ],
    'options' => [
        'empty' => '-- kies --',
    ],
    'tab' => [
        'options' => 'Opties',
    ],
    'demo' => [
        'enabled' => 'Demo is ingeschakeld. Vernieuw de lijst met PDF-sjablonen.',
        'disabled' => 'Demo is uitgeschakeld. Vernieuw de lijst met PDF-sjablonen.',
    ],
];
