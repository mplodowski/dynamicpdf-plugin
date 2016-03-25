# Dynamic PDF plugin

Plugin adds backed-end layouts and templates pdf management features to [OctoberCMS](http://octobercms.com).

Plugin was build on Laravel Package [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

## Features
* Manage PDF Templates and Layouts in OctoberCMS backend
* Example Invoice.pdf template with background image, custom header and footer, custom Open Sans font embedding
* Simple function for rendering PDF documents inside OctoberCMS using DOMPDF library
* Twig support in templates
* Style templates using pure CSS

## Like this plugin?
If you like this plugin, give this plugin a Like or Make donation with PayPal.

# Documentation
## Installation
In order to install this plugin you have to click on __Add to project__ or type __Renatio.DynamicPDF__ in Backend *System > Updates > Install Plugin*

## Using PDF templates

PDF templates reside in the database and can be created in the back-end area via Settings > PDF > PDF Templates. The code specified in the template is a unique identifier and cannot be changed once created.

You can use Twig in pdf templates.

In order to render PDF template use this example method in your plugin controller:

    public function pdf()
    {
        try
        {
            $templateCode = 'renatio::invoice'; // unique code of the template

            $data = ['name' => 'John Doe']; // optional data used in template

            // download PDF as 'attachment', or show in browser as 'inline'            
            $params = [
                'filename' => 'Invoice No. 42',
                'content_disposition' => 'attachment',
            ];

            return PDFTemplate::render($templateCode, $data, $params);

        } catch (Exception $e)
        {
            // render method may throw exception
            // handle any thrown error here
            // e.g. Flash::error($e->getMessage());
        }
    }

Where `$templateCode` is an unique code specified when creating the template, `$data` is optional array of twig fields which will be replaced in template.

## DOMPDF Config

You can change DOMPDF configuration dynamically.

    Config::set('dompdf.orientation', 'landscape');

## Background image

After adding background image to layout, please add this to your body tag:

    <body style="background: url({{ background_img }}) top left no-repeat;">
    
Background image should be 96 DPI size (793 x 1121 px).

## UTF-8 support

In your layout, set the UTF-8 Metatag:
    
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

## Styling PDF

You can use the CSS page-break-before/page-break-after properties to create a new page.

    <style>
    .page-break {
        page-break-after: always;
    }
    </style>
    <h1>Page 1</h1>
    <div class="page-break"></div>
    <h1>Page 2</h1>

You can style pdf document in CSS layout section. Here you can check CSS support for [DOMPDF](https://code.google.com/p/dompdf/wiki/CSSCompatibility).

## Examples
After installation there will an example pdf invoice document.