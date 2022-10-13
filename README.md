# Dynamic PDF Plugin

**Demo URL:** https://october-demo.renatio.com/backend/backend/auth/signin

**Login:** dynamicpdf

**Password:** dynamicpdf

This plugin allows developers to create and edit PDF templates with a simple user interface.

HTML to PDF converter uses [dompdf](https://github.com/dompdf/dompdf) library.

Plugin uses dompdf wrapper for Laravel [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

## Like this plugin?

If you like this plugin, give this plugin a Like or Make donation with [PayPal](https://www.paypal.me/mplodowski).

## My other plugins

Please check my other [plugins](https://octobercms.com/author/Renatio).

## Support

Please use [GitHub Issues Page](https://github.com/mplodowski/dynamicpdf-plugin/issues) to report any issues with
plugin.

> Reviews should not be used for getting support or reporting bugs, if you need support please use the Plugin support
> link.

Icon made by [Darius Dan](https://www.flaticon.com/authors/darius-dan)
from [www.flaticon.com](https://www.flaticon.com/).

# Documentation

## Installation

There are couple ways to install this plugin.

1. Use `php artisan plugin:install Renatio.DynamicPDF` command.
2. Use `composer require renatio/dynamicpdf-plugin` in project root. When you use this option you must
   run `php artisan october:migrate` after installation.

## PDF content

PDF can be created in October using either PDF views or PDF templates. A PDF view is supplied by plugin in the file
system in the **/views** directory. Whereas a PDF template is managed using the back-end interface via *Settings > PDF >
PDF Templates*. All PDFs templates support using Twig for markup.

PDF views must be registered in the Plugin registration file with the `registerPDFTemplates` and `registerPDFLayouts`
method. This will automatically generate a PDF template and layout and allows them to be customized using the back-end
interface.

## PDF layouts views

PDF layouts views reside in the file system and the code used represents the path to the view file. For example PDF
layout with the code **author.plugin::pdf.layouts.default** would use the content in following file:

```
plugins/                 <=== Plugins directory
  author/                <=== "author" segment
    plugin/              <=== "plugin" segment
      views/             <=== View directory
        pdf/             <=== "pdf" segment
          layouts/       <=== "layouts" segment
            default.htm  <=== "default" segment
```

The content inside a PDF view file can include up to 3 sections: **configuration**, **CSS/LESS**, and **HTML markup**.
Sections are separated with the `==` sequence. For example:

```
name = "Default PDF layout"
==
body {
    font-size: 16px;
}
==
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Document</title>
        <style type="text/css" media="screen">
            {{ css|raw }}
        </style>
    </head>
    <body>
        {{ content_html|raw }}
    </body>
</html>
```

> **Note:** Basic Twig tags and expressions are supported in PDF views.

The **CSS/LESS** section is optional and a view can contain only the configuration and HTML markup sections.

```
name = "Default PDF layout"
==
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Document</title>
        <style type="text/css" media="screen">
            {{ css|raw }}
        </style>
    </head>
    <body>
        {{ content_html|raw }}
    </body>
</html>
```

### Configuration section

The configuration section sets the PDF view parameters. The following configuration parameters are supported:

| Parameter | Description                |
|-----------|----------------------------|
| **name**  | the layout name, required. |

### Using PDF layouts

PDF layouts reside in the database and can be created by selecting *Settings > PDF > PDF Templates* and clicking the *
Layouts* tab. These behave just like CMS layouts, they contain the scaffold for the PDF. PDF views and templates support
the use of PDF layouts. The **code** specified in the layout is a unique identifier and cannot be changed once created.

## PDF templates views

PDF templates reside in the file system and the code used represents the path to the view file. For example PDF template
with the code **author.plugin::pdf.invoice** would use the content in following file:

```
plugins/                 <=== Plugins directory
  author/                <=== "author" segment
    plugin/              <=== "plugin" segment
      views/             <=== View directory
        pdf/             <=== "pdf" segment
          invoice.htm    <=== "invoice" segment
```

The content inside a PDF view file can include up to 2 sections: **configuration** and **HTML markup**. Sections are
separated with the `==` sequence. For example:

```
title = "Invoice"
layout = "renatio.demo::pdf.layouts.default"
description = "Invoice template"
size = "a4"
orientation = "portrait"
==
<h1>Invoice</h1>
```

> **Note:** Basic Twig tags and expressions are supported in PDF views.

### Configuration section

The configuration section sets the PDF view parameters. The following configuration parameters are supported:

| Parameter       | Description                                                   |
|-----------------|---------------------------------------------------------------|
| **title**       | the template title, required.                                 |
| **layout**      | the layout code, optional.                                    |
| **description** | the template description, optional.                           |
| **size**        | the template paper size, optional, default `a4`.              |
| **orientation** | the template paper orientation, optional, default `portrait`. |

### Using PDF templates

PDF templates reside in the database and can be created in the back-end area via *Settings > PDF > PDF Templates*.
The **code** specified in the template is a unique identifier and cannot be changed once created.

> **Note:** If the PDF template does not exist in the system, this code will attempt to find a PDF view with the same
> code.

## Registering PDF templates and layouts

PDF views can be registered as templates that are automatically generated in the back-end ready for customization. PDF
templates can be customized via the *Settings > PDF Templates* menu. The templates can be registered by adding
the `registerPDFTemplates` method of the Plugin registration class (`Plugin.php`).

```
public function registerPDFTemplates()
{
    return [
        'renatio.demo::pdf.invoice',
        'renatio.demo::pdf.resume',
    ];
}
```

The method should return an array of pdf view names.

Like templates, PDF layouts can be registered by adding the `registerPDFLayouts` method of the Plugin registration
class (`Plugin.php`).

```
public function registerPDFLayouts()
{
    return [
        'renatio.demo::pdf.layouts.invoice',
        'renatio.demo::pdf.layouts.resume',
    ];
}
```

The method should return an array of pdf view names.

## Usage

PDF templates and layouts can be accessed in the back-end area via *Settings > PDF > PDF Templates*.

Layouts define the PDF scaffold, that is everything that repeats on a PDF, such as a header and footer. Each layout has
unique code, optional background image, HTML content and CSS/LESS content. Not all CSS properties are supported, so
check [CSSCompatibility](https://github.com/dompdf/dompdf/wiki/CSSCompatibility).

Templates define the actual PDF content parsed from HTML.

## Configuration

The default configuration settings are set in `config/dompdf.php`. Copy this file to your own config directory to modify
the values. You can publish the config using this command:

```
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

You can still alter the dompdf options in your code before generating the PDF using dynamic methods for all options like
so:

```
PDF::loadTemplate('renatio::invoice')
    ->setDpi(300)
    ->setDefaultFont('sans-serif')
    ->stream();
```

or you can use `setOption` method before generating the pdf using this command:

```
PDF::loadTemplate('renatio::invoice')
    ->setOption(['dpi' => 300, 'defaultFont' => 'sans-serif'])
    ->stream();
```

Available options and their defaults:

* __rootDir__: "{app_directory}/vendor/dompdf/dompdf"
* __tempDir__: "/tmp" _(available in config/dompdf.php)_
* __fontDir__: "{app_directory}/storage/fonts/" _(available in config/dompdf.php)_
* __fontCache__: "{app_directory}/storage/fonts/" _(available in config/dompdf.php)_
* __chroot__: "{app_directory}" _(available in config/dompdf.php)_
* __logOutputFile__: "/tmp/log.htm"
* __defaultMediaType__: "screen" _(available in config/dompdf.php)_
* __defaultPaperSize__: "a4" _(available in config/dompdf.php)_
* __defaultFont__: "serif" _(available in config/dompdf.php)_
* __dpi__: 96 _(available in config/dompdf.php)_
* __fontHeightRatio__: 1.1 _(available in config/dompdf.php)_
* __isPhpEnabled__: false _(available in config/dompdf.php)_
* __isRemoteEnabled__: true _(available in config/dompdf.php)_
* __isJavascriptEnabled__: true _(available in config/dompdf.php)_
* __isHtml5ParserEnabled__: false _(available in config/dompdf.php)_
* __isFontSubsettingEnabled__: false _(available in config/dompdf.php)_
* __debugPng__: false
* __debugKeepTemp__: false
* __debugCss__: false
* __debugLayout__: false
* __debugLayoutLines__: true
* __debugLayoutBlocks__: true
* __debugLayoutInline__: true
* __debugLayoutPaddingBox__: true
* __pdfBackend__: "CPDF" _(available in config/dompdf.php)_
* __pdflibLicense__: ""
* __adminUsername__: "user"
* __adminPassword__: "password"

See [Dompdf\Options](https://github.com/dompdf/dompdf/blob/master/src/Options.php) for a list of available options.

## Methods

| Method                                                  | Description                                              |
|---------------------------------------------------------|----------------------------------------------------------|
| loadTemplate($code, array $data = [], $encoding = null) | Load backend template                                    |
| loadLayout($code, array $data = [], $encoding = null)   | Load backend layout                                      |
| loadHTML($string, $encoding = null)                     | Load HTML string                                         |
| loadFile($file)                                         | Load HTML string from a file                             |
| parseTemplate(Template $template, array $data = [])     | Parse backend template using Twig                        |
| parseLayout(Layout $layout, array $mergeData = [])      | Parse backend layout using Twig                          |
| getDomPDF()                                             | Get the DomPDF instance                                  |
| setPaper($paper, $orientation = 'portrait')             | Set the paper size and orientation (default A4/portrait) |
| setWarnings($warnings)                                  | Show or hide warnings                                    |
| output()                                                | Output the PDF as a string                               |
| save($filename)                                         | Save the PDF to a file                                   |
| download($filename = 'document.pdf')                    | Make the PDF downloadable by the user                    |
| stream($filename = 'document.pdf')                      | Return a response with the PDF to show in the browser    |

All methods are available through Facade class `Renatio\DynamicPDF\Classes\PDF`.

## Tips

### Background image

To display background image added in layout use following code:

```
<body style="background: url({{ background_img }}) top left no-repeat;">
```

Background image should be at least 96 DPI size (793 x 1121 px).

If you want to use better quality image like 300 DPI (2480 x 3508 px) than you need to change template options like so:

```
return PDF::loadTemplate($model->code)
    ->setDpi(300)
    ->stream();
```

### UTF-8 support

In your layout, set the UTF-8 meta tag in `head` section:

```
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
```

If you have problems with foreign characters than try to use **DejaVu Sans** font family.

### Page breaks

You can use the CSS page-break-before/page-break-after properties to create a new page.

```
<style>
.page-break {
    page-break-after: always;
}
</style>
<h1>Page 1</h1>
<div class="page-break"></div>
<h1>Page 2</h1>
```

### Open basedir restriction error

On some hosting providers there were reports about `open_basedir` restriction problems with log file. You can change
default log file destination like so:

```
return PDF::loadTemplate('renatio::invoice')
    ->setLogOutputFile(storage_path('temp/log.htm'))
    ->stream();
```

### Embed image inside PDF template

You can use absolute path for image eg. `https://app.dev/path_to_your_image`.

For this to work you must set `isRemoteEnabled` option.

```
return PDF::loadTemplate('renatio::invoice', ['file' => $file])
    ->setIsRemoteEnabled(true)
    ->stream();
```

I assume that `$file` is instance of `October\Rain\Database\Attach\File`.

Then in the template you can use following example code:

```
{{ file.getPath }}

{{ file.getLocalPath }}

{{ file.getThumb(200, 200, {'crop' => true}) }}
```

> For retrieving stylesheets or images via http following PHP setting must be enabled `allow_url_fopen`.

When `allow_url_fopen` is disabled on server try to use relative path. You can use October `getLocalPath` function on
the file object to retrieve it.

### Download PDF via Ajax response

OctoberCMS ajax framework cannot handle this type of response.

Recommended approach is to save PDF file locally and return redirect to PDF file.

### Page numbers

Page numbers can be generated using PHP. Inline PHP is disabled by default, because it can be a security risk. You can
enable inline PHP using `setIsPhpEnabled` method.

```
return PDF::loadTemplate('renatio::invoice')
    ->setIsRemoteEnabled(true)
    ->setIsPhpEnabled(true)
    ->stream();
```

After that you must place following code before closing `</body>` tag of the layout file.

```
<script type="text/php">
    if (isset($pdf)) {
        $size = 9;
        $color = [0,0,0];

        $font = $fontMetrics->getFont('Open Sans');
        $textHeight = $fontMetrics->getFontHeight($font, $size);
        $width = $fontMetrics->getTextWidth('Page 1 of 2', $font, $size);

        $foot = $pdf->open_object();

        $w = $pdf->get_width();
        $h = $pdf->get_height();

        $y = $h - $textHeight - 13;

        $pdf->close_object();
        $pdf->add_object($foot, 'all');

        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";

        // Center the text
        $pdf->page_text($w / 2 - $width / 2, $y, $text, $font, $size, $color);
    }
</script>
```

## Examples

### Demo examples

There is a console command that will enable demo templates and layouts.

```
php artisan dynamicpdf:demo
```

To disable demo run following command:

```
php artisan dynamicpdf:demo --disable
```

The first example shows invoice with custom font and image embed.

The second example shows usage of header & footer, page break, page numbers and full background image.

### Render PDF in browser

```
use Renatio\DynamicPDF\Classes\PDF; // import facade

public function pdf()
{
    $templateCode = 'renatio::invoice'; // unique code of the template
    $data = ['name' => 'John Doe']; // optional data used in template

    return PDF::loadTemplate($templateCode, $data)->stream('download.pdf');
}
```

Where `$templateCode` is an unique code specified when creating the template, `$data` is optional array of twig fields
which will be replaced in template.

In HTML template you can use `{{ name }}` to output `John Doe`.

### Download PDF

```
use Renatio\DynamicPDF\Classes\PDF;

public function pdf()
{
    return PDF::loadTemplate('renatio::invoice')->download('download.pdf');
}
```

### Fluent interface

You can chain the methods:

```
return PDF::loadTemplate('renatio::invoice')
    ->save('/path-to/my_stored_file.pdf')
    ->stream();
```

### Change paper size and orientation

```
return PDF::loadTemplate('renatio::invoice')
    ->setPaper('a4', 'landscape')
    ->stream();
```

Available [paper sizes](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php#L40).

### PDF on CMS page

To display PDF on CMS page you can use PHP section of the page like so:

```
use Renatio\DynamicPDF\Classes\PDF;

function onStart()
{
    return PDF::loadTemplate('renatio::invoice')->stream();
}
```

### Header and footer on every page

```
<html>
<head>
  <style>
    @page { margin: 100px 25px; }
    header { position: fixed; top: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    footer { position: fixed; bottom: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: never; }
  </style>
</head>
<body>
  <header>header on each page</header>
  <footer>footer on each page</footer>
  <main>
    <p>page1</p>
    <p>page2</p>
  </main>
</body>
</html>
```

### Using custom fonts

Plugin provides "Open Sans" font, which can be imported in Layout CSS section.

```
@font-face {
    font-family: 'Open Sans';
    src: url({{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf'|app }});
}

@font-face {
    font-family: 'Open Sans';
    font-weight: bold;
    src: url({{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Bold.ttf'|app }});
}

@font-face {
    font-family: 'Open Sans';
    font-style: italic;
    src: url({{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Italic.ttf'|app }});
}

@font-face {
    font-family: 'Open Sans';
    font-style: italic;
    font-weight: bold;
    src: url({{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-BoldItalic.ttf'|app }});
}

body {
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
}
```
