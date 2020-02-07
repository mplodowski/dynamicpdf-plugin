# Dynamic PDF plugin

> Plugin requires PHP >=7.1 to work with the latest version of [dompdf](https://github.com/dompdf/dompdf) library. Please see update guide for version 4.0.0.

October HTML to PDF converter using [dompdf](https://github.com/dompdf/dompdf) library.

Plugin uses dompdf wrapper for Laravel [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

## Features

* Handles most CSS 2.1 and a few CSS3 properties, including @import, @media & @page rules
* Supports most presentational HTML 4.0 attributes
* Supports external stylesheets, either local or through http/ftp (via fopen-wrappers)
* Supports complex tables, including row & column spans, separate & collapsed border models, individual cell styling
* Image support (gif, png (8, 24 and 32 bit with alpha channel), bmp & jpeg)
* No dependencies on external PDF libraries, thanks to the R&OS PDF class
* Inline PHP support
* Basic SVG support

## Installation

There are couple ways to install this plugin.

1. Use October [Marketplace](http://octobercms.com/help/site/marketplace) and __Add to project__ button. 
2. Use October backend area *Settings > System > Updates & Plugins > Install Plugins* and type __Renatio.DynamicPDF__.
3. Use `php artisan plugin:install Renatio.DynamicPDF` command.
4. Use `composer require renatio/dynamicpdf-plugin` in project root. When you use this option you must run `php artisan october:up` after installation.

> Fourth option should be used only for advanced users.

## Using

Plugin will register menu item called **PDF**, which allow you to manage PDF layouts and templates.

Layouts define the PDF scaffold, that is everything that repeats on a PDF, such as a header and footer. Each layout has unique code, optional background image, HTML content and CSS content. Not all CSS properties are supported, so check [CSSCompatibility](https://github.com/dompdf/dompdf/wiki/CSSCompatibility).

Templates define the actual PDF content parsed from HTML. The code specified in the template is a unique identifier and cannot be changed once created.

You can use Twig in layouts and templates.

Plugin supports using [CMS partials](https://octobercms.com/docs/cms/partials) and filters inside template and layout markup.

## Configuration

The defaults configuration settings are set in `config/dompdf.php`. Copy this file to your own config directory to modify the values. You can publish the config using this command:

```
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

You can still alter the dompdf options in your code before generating the PDF using this command:

```
PDF::loadTemplate('renatio::invoice')
    ->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif'])
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

## Methods

| Method  | Description  |
|---|---|
| loadTemplate($code, array $data = [], $encoding = null)  | Load backend template |
| loadLayout($code, array $data = [], $encoding = null) | Load backend layout |
| loadHTML($string, $encoding = null) | Load HTML string |
| loadFile($file) | Load HTML string from a file |
| parseTemplate(Template $template, array $data = []) | Parse backend template using Twig |
| parseLayout(Layout $layout, array $mergeData = []) | Parse backend layout using Twig |
| getDomPDF() | Get the DomPDF instance |
| setPaper($paper, $orientation = 'portrait') | Set the paper size and orientation (default A4/portrait) |
| setWarnings($warnings) | Show or hide warnings |
| output() | Output the PDF as a string |
| save($filename) | Save the PDF to a file |
| download($filename = 'document.pdf') | Make the PDF downloadable by the user |
| stream($filename = 'document.pdf') | Return a response with the PDF to show in the browser |

All methods are available through Facade class `Renatio\DynamicPDF\Classes\PDF`.

## Tip: Background image

To display background image added in layout use following code:

```
<body style="background: url({{ background_img }}) top left no-repeat;">
```

Background image should be 96 DPI size (793 x 1121 px).

If you want to use better quality image like 300 DPI (2480 x 3508 px) than you need to change template options like so:

```
return PDF::loadTemplate($model->code)
    ->setOptions(['dpi' => 300])
    ->stream();
```

## Tip: UTF-8 support

In your layout, set the UTF-8 meta tag in `head` section:

```
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
```

If you have problems with foreign characters than try to use **DejaVu Sans** font family.

## Tip: Page breaks

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

## Tip: Open_basedir restriction error

On some hosting providers there were reports about `open_basedir` restriction problems with log file. You can change default log file destination like so:

```
return PDF::loadTemplate('renatio::invoice')
    ->setOptions(['logOutputFile' => storage_path('temp/log.htm')])
    ->stream();
```

## Tip: Embed image inside PDF template

You can use absolute path for image eg. `http://app.dev/path_to_your_image`.

For this to work you must set `isRemoteEnabled` option.

```
return PDF::loadTemplate('renatio::invoice', ['file' => $file])
    ->setOptions(['isRemoteEnabled' => true])
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

When `allow_url_fopen` is disabled on server try to use relative path. You can use October `getLocalPath` function on the file object to retrieve it.

## Tip: Download PDF via Ajax response

OctoberCMS ajax framework cannot handle this type of response.

Recommended approach is to save PDF file locally and return redirect to PDF file.

## Examples

### Render PDF in browser

```
use Renatio\DynamicPDF\Classes\PDF; // import facade

...

public function pdf()
{
    $templateCode = 'renatio::invoice'; // unique code of the template
    $data = ['name' => 'John Doe']; // optional data used in template

    return PDF::loadTemplate($templateCode, $data)->stream('download.pdf');
}
```

Where `$templateCode` is an unique code specified when creating the template, `$data` is optional array of twig fields which will be replaced in template.

In HTML template you can use `{{ name }}` to output `John Doe`.

### Download PDF

```
use Renatio\DynamicPDF\Classes\PDF;
 
 ...
 
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
    
### Change orientation and paper size

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
    
### Header and Footer on every page

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
    src: url('plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf');
}

@font-face {
    font-family: 'Open Sans';
    font-weight: bold;
    src: url('plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Bold.ttf');
}

@font-face {
    font-family: 'Open Sans';
    font-style: italic;
    src: url('plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Italic.ttf');
}

@font-face {
    font-family: 'Open Sans';
    font-style: italic;
    font-weight: bold;
    src: url('plugins/renatio/dynamicpdf/assets/fonts/OpenSans-BoldItalic.ttf');
}

body {
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
}
```

## Support

Please use [GitHub Issues Page](https://github.com/mplodowski/dynamicpdf-plugin/issues) to report any issues with plugin.

> Reviews should not be used for getting support or reporting bugs, if you need support please use the Plugin support link.

## Like this plugin?

If you like this plugin, give this plugin a Like or Make donation with [PayPal](https://www.paypal.me/mplodowski).