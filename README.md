# Dynamic PDF plugin

Plugin adds backed-end layouts and templates pdf management features to [OctoberCMS](http://octobercms.com).

Demo: http://oc.renatio.com/dynamic-pdf

Plugin page: https://octobercms.com/plugin/renatio-dynamicpdf

Plugin was build on Laravel Package [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

> This plugin requires **Stable** version of OctoberCMS.

## Features
* Manage PDF Templates and Layouts in OctoberCMS backend
* Example Invoice.pdf template with background image, custom header and footer, custom Open Sans font embedding
* Simple function for rendering PDF documents inside OctoberCMS using DOMPDF library
* Twig support in templates
* Style templates using pure CSS

## Support

Please use [GitHub Issues Page](https://github.com/mplodowski/DynamicPDF/issues) to report any issues with plugin.

> Reviews should not be used for getting support, if you need support please use the Plugin support link.

## Like this plugin?
If you like this plugin, give this plugin a Like or Make donation with PayPal.

# Documentation
## [Installation](#installation) {#installation}

In order to install this plugin you have to click on __Add to project__ or type __Renatio.DynamicPDF__ in Backend *System > Updates > Install Plugin*

## [Using](#using)  {#using}

Plugin will register menu item called **PDF**, which allow you to manage PDF layouts and templates.

Layouts define the pdf scaffold, that is everything that repeats on a pdf, such as a header and footer. Each layout has unique code, optional background image, HTML content and CSS content. Not all CSS properties are supported, so check CSS support for [DOMPDF](https://github.com/dompdf/dompdf/wiki/CSSCompatibility).

Templates define the actual pdf content parsed from HTML. The code specified in the template is a unique identifier and cannot be changed once created.

You can use Twig in layouts and templates.

See [example codes](#examples).

## [Configuration](#configuration) {#configuration}

Use `php artisan vendor:publish` to create a config file located at `config/dompdf.php` which will allow you to define local configurations to change some settings.

## [Methods](#methods) {#methods}

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

All methods are available through Facade class `\Renatio\DynamicPDF\Classes\PDF`;

See [example codes](#examples).

## [Tip: Background image](#tip-background-image) {#tip-background-image}

When you add background image to layout, add this to your body for display:

    <body style="background: url({{ background_img }}) top left no-repeat;">

Background image should be 96 DPI size (793 x 1121 px).

## [Tip: UTF-8 support](#utf-8-support) {#utf-8-support}

In your layout, set the UTF-8 Metatag in `head` section:

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

## [Tip: Page breaks](#tip-page-breaks) {#tip-page-breaks}

You can use the CSS page-break-before/page-break-after properties to create a new page.

    <style>
    .page-break {
        page-break-after: always;
    }
    </style>
    <h1>Page 1</h1>
    <div class="page-break"></div>
    <h1>Page 2</h1>

## [Examples](#examples) {#examples}

After installation there will an example pdf invoice document, which will show, how you can structure HTML and CSS.

### [Render PDF in browser](#render-pdf-in-browser) {#render-pdf-in-browser}

    use Renatio\DynamicPDF\Classes\PDF; // import facade

    ...

    public function pdf()
    {
        $templateCode = 'renatio::invoice'; // unique code of the template
        $data = ['name' => 'John Doe']; // optional data used in template

        return PDF::loadTemplate($templateCode, $data)->stream();
    }

Where `$templateCode` is an unique code specified when creating the template, `$data` is optional array of twig fields which will be replaced in template.

### [Download PDF](#download-pdf) {#download-pdf}

    use Renatio\DynamicPDF\Classes\PDF;

    ...

    public function pdf()
    {
        return PDF::loadTemplate('renatio::invoice')->download();
    }

### [Fluent interface](#fluent-interface) {#fluent-interface}

You can chain the methods:

    return PDF::loadTemplate('renatio::invoice')->save('/path-to/my_stored_file.pdf')->stream('download.pdf');

### [PDF on CMS page](#pdf-on-cms-page) {#pdf-on-cms-page}

To display PDF on CMS page you can use PHP section of the page like so:

    use Renatio\DynamicPDF\Classes\PDF;

    function onStart()
    {
        return PDF::loadTemplate('renatio::invoice')->stream();
    }

See all available [methods](#methods).

## [Upgrade guide](#upgrade-guide) {#upgrade-guide}

**From 2.1.0 to 2.1.1.** 

Method `setOrientation` was removed. Use `setPaper` instead.

**From 2.0.1 to 2.1.0.** 

Plugin requires **Stable Release** of OctoberCMS. As a consequence it needs PHP 5.5.9 and Laravel 5.1.

**From 1.1.5 to 2.0.0.** 

Major code refactor. `PDFTemplate::render()` method was removed, switch to PDF facade. See example codes in documentation how to. Code base was significantly improved and allow for more flexibility.

**From 1.0.2 to 1.1.0.** 

Plugin requires October build 300 and above.

## [License](#license) {#license}

OctoberCMS DynamicPDF Plugin is open-sourced software licensed under the MIT license.