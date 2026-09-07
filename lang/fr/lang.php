<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Générez des PDF dynamiques et personnalisés.',
    ],
    'templates' => [
        'label' => 'Modèles',
        'code' => 'Code',
        'title' => 'Titre',
        'description' => 'Description',
        'layout' => 'Mise en page',
        'empty_option' => '-- Aucune mise en page --',
        'code_comment' => 'Code unique utilisé pour désigner ce modèle',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Nom',
        'return' => 'Retour à la liste des modèles',
        'new_template' => 'Nouveau modèle',
        'new_layout' => 'Nouvelle mise en page',
        'templates' => 'Modèles',
        'layouts' => 'Mises en page',
        'background_img' => 'Image de fond',
        'preview_html' => 'Aperçu HTML',
        'preview_pdf' => 'Aperçu PDF',
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'background_img_comment' => 'Utilisez une image d\'au moins 96 DPI pour un affichage correct. 300 DPI recommandé.',
        'size' => 'Format du papier',
        'orientation' => 'Orientation du papier',
        'sample_data' => 'Données d\'exemple',
        'sample_data_comment' => 'Objet JSON avec les variables utilisées par l\'aperçu du panneau, par exemple {"name": "Jean Dupont"}.',
        'is_custom' => 'Personnalisé',
        'is_locked' => 'Verrouillé',
        'preview' => 'Aperçu',
        'duplicate' => 'Dupliquer',
        'duplicating' => 'Duplication...',
        'duplicate_success' => 'Copie créée.',
    ],
    'template' => [
        'menu_label' => 'Modèle',
        'create_template' => 'Créer un modèle',
        'edit_template' => 'Modifier le modèle',
        'not_found' => 'Impossible de trouver un modèle enregistré avec le code',
    ],
    'layouts' => [
        'label' => 'Mises en page',
        'return' => 'Retour à la liste des mises en page',
    ],
    'layout' => [
        'menu_label' => 'Mise en page',
        'create_layout' => 'Créer une mise en page',
        'edit_layout' => 'Modifier la mise en page',
        'not_found' => 'Impossible de trouver une mise en page enregistrée avec le code',
    ],
    'settings' => [
        'description' => 'Gérer les modèles et les mises en page.',
    ],
    'permissions' => [
        'manage_templates' => 'Gérer les modèles',
        'manage_layouts' => 'Gérer les mises en page',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'Modèles PDF',
        'category' => 'PDF',
        'description' => 'Modifier les modèles PDF et gérer les mises en page PDF.',
    ],
    'orientation' => [
        'portrait' => 'Portrait',
        'landscape' => 'Paysage',
    ],
    'options' => [
        'empty' => '-- choisir --',
    ],
    'tab' => [
        'options' => 'Options',
    ],
    'demo' => [
        'enabled' => 'La démo est activée. Actualisez la liste des modèles PDF.',
        'disabled' => 'La démo est désactivée. Actualisez la liste des modèles PDF.',
    ],
];
