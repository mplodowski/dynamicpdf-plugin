# Dynamic PDF Plugin

Create and edit PDF templates for [October CMS](https://octobercms.com) in the backend — HTML and Twig in, a
document out.

**Demo URL:** https://october-demo.renatio.com/backend/backend/auth/signin  
**Login:** dynamicpdf  
**Password:** dynamicpdf

Templates and layouts live in the database or in view files shipped by your plugins, and are rendered to PDF with
[dompdf](https://github.com/dompdf/dompdf) through [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

## Features

- PDF templates and layouts edited in the backend, with HTML and PDF preview rendered from sample data.
- Templates and layouts registered by plugins as view files, synchronized to the database and customizable, with
  reset to the file version.
- Twig markup with theme partials, translations, global variables and `beforeRender` / `afterRender` events.
- Render per document with another layout, in another language, with page numbers, password protection and
  paper size and orientation.
- Templates translated per language with the native October multisite translation, or shipped as localized view
  files such as `pdf.de.invoice`.
- Output as a browser stream, download, file on a storage disk or a `System\Models\File` ready to attach to a model.
- `PDF::fake()` with render assertions for project tests, `dynamicpdf:sync` and `dynamicpdf:check` console commands.

## Requirements

This plugin requires PHP 8.2 or higher and October CMS 4.4 or higher.

Templates are rendered with Twig without a sandbox, so the **Create** and **Update** permissions for templates and
layouts should only be granted to trusted users.

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

There are two ways to install this plugin.

1. Use `php artisan plugin:install Renatio.DynamicPDF` command.
2. Use `composer require renatio/dynamicpdf-plugin` in project root. When you use this option you must
   run `php artisan october:migrate` after installation.

## PDF content

PDF can be created in October using either PDF views or PDF templates. A PDF view is supplied by a plugin in its
**/views** directory, whereas a PDF template is managed using the back-end interface via *Settings > PDF >
PDF Templates*. All PDF templates support using Twig for markup.

PDF views must be registered in the Plugin registration file with the `registerPDFTemplates` and `registerPDFLayouts`
methods. This will automatically generate a PDF template and layout and allow them to be customized using the back-end
interface.

## PDF layout views

PDF layout views reside in the file system and the code used represents the path to the view file. For example PDF
layout with the code **author.plugin::pdf.layouts.default** would use the content in the following file:

```text
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

```twig
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

```twig
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

PDF layouts reside in the database and can be created by selecting *Settings > PDF > PDF Templates* and clicking the
*Layouts* tab. These behave just like CMS layouts, they contain the scaffold for the PDF. PDF views and templates support
the use of PDF layouts. The **code** specified in the layout is a unique identifier and cannot be changed once created.

## PDF template views

PDF templates reside in the file system and the code used represents the path to the view file. For example PDF template
with the code **author.plugin::pdf.invoice** would use the content in the following file:

```text
plugins/                 <=== Plugins directory
  author/                <=== "author" segment
    plugin/              <=== "plugin" segment
      views/             <=== View directory
        pdf/             <=== "pdf" segment
          invoice.htm    <=== "invoice" segment
```

The content inside a PDF view file can include up to 2 sections: **configuration** and **HTML markup**. Sections are
separated with the `==` sequence. For example:

```twig
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

| Parameter       | Description                                                                                                  |
|-----------------|--------------------------------------------------------------------------------------------------------------|
| **title**       | the template title, required.                                                                                |
| **layout**      | the layout code, optional.                                                                                   |
| **description** | the template description, optional.                                                                          |
| **size**        | the template paper size, optional; without it `default_paper_size` of the dompdf configuration applies.      |
| **orientation** | the template paper orientation, optional; applies without **size** too, default `portrait`.                  |

> **Note:** **size** and **orientation** are read case-insensitively; `A4` is stored as `a4`.

### Using PDF templates

PDF templates reside in the database and can be created in the back-end area via *Settings > PDF > PDF Templates*.
The **code** specified in the template is a unique identifier and cannot be changed once created.

> **Note:** If the PDF template does not exist in the system, this code will attempt to find a PDF view with the same
> code.

## Registering PDF templates and layouts

PDF views can be registered as templates that are automatically generated in the back-end ready for customization. PDF
templates can be customized via the *Settings > PDF > PDF Templates* menu. The templates can be registered by adding
the `registerPDFTemplates` method of the Plugin registration class (`Plugin.php`).

```php
public function registerPDFTemplates()
{
    return [
        'renatio.demo::pdf.invoice',
        'renatio.demo::pdf.resume',
    ];
}
```

The method should return an array of PDF view names.

Registered views are synchronized to the database when a *PDF Templates* page (the lists or a template or layout form)
is displayed and when `php artisan dynamicpdf:sync` or `php artisan dynamicpdf:demo` runs, not on every request.
Synchronization creates the missing templates and layouts, deletes the templates that are no longer registered and were
never customized, and writes changed view files back to the rows of templates that are not customized and of layouts
that are *From view*, so list search and sort work on the current values. Customized templates and layouts that are no
longer *From view* are left alone. A code whose view file is missing is skipped and written to the application log once
per process; a stored template or layout whose view file went missing or parses to no content, and a template whose
layout code resolves to nothing, keep their stored content. Until a registered view is synchronized, `PDF::loadTemplate()`
renders it straight from the file.

Like templates, PDF layouts can be registered by adding the `registerPDFLayouts` method of the Plugin registration
class (`Plugin.php`).

```php
public function registerPDFLayouts()
{
    return [
        'renatio.demo::pdf.layouts.invoice',
        'renatio.demo::pdf.layouts.resume',
    ];
}
```

The method should return an array of PDF view names.

## Twig environment

Templates and layouts are rendered with the CMS Twig environment when the Cms module is installed and a theme is
active, so theme partials and content blocks are available. Otherwise, for example in a backend-only installation
that loads only the System and Backend modules, the system Twig environment is used. Filters and functions registered
by plugins through `registerMarkupTags` work in both. Filters and tags provided by the Cms module itself (`|theme`,
`|page`, `{% partial %}`) are unavailable without it.

## Global variables

Variables every template and layout should receive, such as company details or a logo URL, are registered in the
plugin registration class. A closure value is resolved when the document is rendered:

```php
public function registerPDFVariables()
{
    return [
        'company' => 'Acme Ltd',
        'vat_rate' => fn () => Settings::get('vat_rate'),
    ];
}
```

Data passed to `loadTemplate()`, `loadLayout()` or `parseTemplate()` takes precedence over a registered variable. The
names `content_html`, `css`, `background_img` and `locale` are reserved for the wrapper and ignored when registered.
Every closure is resolved on every render, whether or not the template uses it, so keep them cheap.

## Events

| Event                              | Payload                                       | Return value                              |
|------------------------------------|-----------------------------------------------|-------------------------------------------|
| `renatio.dynamicpdf.beforeRender`  | `PDFWrapper $pdf`, `Template\|Layout $model`, `array $data` | an array merged on top of the render data |
| `renatio.dynamicpdf.afterRender`   | `PDFWrapper $pdf`, `Template\|Layout $model`, `string $html` | a string replacing the rendered HTML      |

```php
Event::listen('renatio.dynamicpdf.beforeRender', function ($pdf, $model, array $data) {
    return ['watermark' => $data['order']->isDraft() ? 'DRAFT' : null];
});
```

Both events fire once per document: for a template together with its layout (`loadTemplate()`, `parseTemplate()`),
or for a layout rendered on its own (`loadLayout()`, `parseLayout()`). They also fire for the backend HTML and PDF
preview, so keep side effects such as counters or audit entries out of the listeners. Every listener runs; the arrays
they return are merged in turn, and the reserved names `content_html`, `css`, `background_img` and `locale` are
stripped from what a listener returns.

## Usage

PDF templates and layouts can be accessed in the back-end area via *Settings > PDF > PDF Templates*.

The list marks templates edited in the back-end as *Customized* (they no longer follow their view file) and layouts
that still follow their registered view file as *From view*; the layout list also counts the templates using each layout
(*Used by*). The HTML preview opens from the form, the PDF preview from the form and the list, both with the **Preview**
permission. A template's *Sample data* (a JSON object on the *Options* tab, nested objects and lists included) is passed
to both previews, so `{{ variables }}` render with realistic values. A template becomes *Customized* only when a value
the view file provides is changed; editing the sample data alone keeps it view-driven. Likewise a layout stops being
*From view* once its name, markup or CSS is changed, and its localized view files are then no longer used. Translated
fields saved with another site selected change neither flag. *Reset to Default* restores the record from its view file
and sets the flag back. The *Actions* column of both lists opens the PDF preview and duplicates, resets or deletes the
record. A template or layout registered by a plugin view offers *Reset to Default* instead of *Delete*, and a layout
used by a template cannot be deleted. *Duplicate* creates an editable copy with a `_copy` code (`_copy2` and so on when
that code is taken); the copy of a template is *Customized* and the copy of a layout is not *From view*.

Layouts define the PDF scaffold, that is everything that repeats on a PDF, such as a header and footer. Each layout has
unique code, optional background image, HTML content and CSS/LESS content. Not all CSS properties are supported, so
check [CSSCompatibility](https://github.com/dompdf/dompdf/wiki/CSSCompatibility).

Templates define the actual PDF content parsed from HTML.

## Permissions

Access is granted under **Settings → Administrators**, on the **PDF** tab. Super users bypass all of them.

| Permission | What it unlocks |
| --- | --- |
| **Access templates** | The *PDF Templates* page with the template list. |
| **Create templates** | The *New template* form and duplicating a template. |
| **Update templates** | Opening and saving the template form, and resetting a template to its view file. |
| **Delete templates** | Deleting a template from the list or the form. |
| **Preview templates** | The HTML and PDF preview of a template. |
| **Access layouts** | The *Layouts* tab on the *PDF Templates* page (which also needs **Access templates**). |
| **Create layouts** | The *New layout* form and duplicating a layout. |
| **Update layouts** | Opening and saving the layout form, and resetting a layout to its view file. |
| **Delete layouts** | Deleting a layout that no template uses. |
| **Preview layouts** | The HTML and PDF preview of a layout. |

Without **Access layouts** the *Layouts* tab is hidden on the *PDF Templates* page. Buttons a user cannot use are
hidden, and every action is checked again on the server.

## Configuration

The default configuration settings are set in `config/dompdf.php`. Copy this file to your own config directory to modify
the values. You can publish the config using this command:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

You can still alter the dompdf options in your code before generating the PDF using dynamic methods for all options like
so:

```php
PDF::loadTemplate('renatio::invoice')
    ->setDpi(300)
    ->setDefaultFont('sans-serif')
    ->stream();
```

or use the `setOption()` method before generating the PDF, like this:

```php
PDF::loadTemplate('renatio::invoice')
    ->setOption(['dpi' => 300, 'defaultFont' => 'sans-serif'])
    ->stream();
```

The options most often changed are `dpi`, `default_font`, `default_paper_size`, `enable_remote` (off by default;
required for images, stylesheets and fonts loaded by URL), `allowed_remote_hosts`, `chroot` and `font_dir`. The full
list with the current defaults is in the published `config/dompdf.php` and in
[Dompdf\Options](https://github.com/dompdf/dompdf/blob/master/src/Options.php); every option has a matching
`set*()` method on the wrapper named after the camel-cased key, except the `enable_*` options, which are
`setIsRemoteEnabled()`, `setIsPhpEnabled()`, `setIsJavascriptEnabled()`, `setIsFontSubsettingEnabled()` and
`setIsPdfAEnabled()`; `enable_html5_parser` has no effect since dompdf 3. A setter that exists on neither dompdf nor
its options throws `UnexpectedValueException`.

### Self-signed certificates

Remote resources (requires `enable_remote` in the dompdf configuration) are fetched with full TLS verification. On a
development host with a self-signed certificate set `DYNAMICPDF_ALLOW_SELF_SIGNED=true` in `.env` (or
`allow_self_signed_certificates` in `config/renatio/dynamicpdf.php`), or call `allowSelfSignedCertificates()` on the
wrapper for a single document. The setting applies to every wrapper instance, including `loadHTML()` and after
`setOptions()`.

## Methods

| Method                                                  | Description                                              |
|---------------------------------------------------------|----------------------------------------------------------|
| loadTemplate($code, array $data = [], $encoding = null, $layout = null, $locale = null) | Load backend template, optionally with another layout and locale |
| loadLayout($code, array $data = [], $encoding = null, $locale = null) | Load backend layout, optionally in another locale |
| pageNumbers($text = 'Page {PAGE_NUM} of {PAGE_COUNT}', $position = 'bottom-center', $size = 9, $font = null, $margin = 20, $color = [0, 0, 0]) | Stamp page numbers on every page of the loaded document |
| allowSelfSignedCertificates()                           | Accept self-signed TLS certificates for remote resources |
| allowRemoteApplicationAssets()                          | Limit remote resources to the configured and application hosts and local files to the asset directories |
| loadHTML($string, $encoding = null)                     | Load HTML string                                         |
| loadFile($file)                                         | Load HTML string from a file                             |
| loadView($view, array $data = [], array $mergeData = [], $encoding = null) | Load a Laravel view                   |
| parseTemplate(Template $template, array $data = [])     | Parse backend template using Twig                        |
| parseLayout(Layout $layout, array $data = [])           | Parse backend layout using Twig                          |
| setOption($attribute, $value = null)                    | Change one dompdf option, or an array of them            |
| setOptions(array $options, $mergeWithDefaults = false)  | Replace the whole dompdf options object                  |
| getDomPDF()                                             | Get the DomPDF instance                                  |
| setPaper($paper, $orientation = 'portrait')             | Set the paper size and orientation (default A4/portrait) |
| setWarnings($warnings)                                  | Show or hide warnings                                    |
| output()                                                | Output the PDF as a string                               |
| toFile($filename = 'document.pdf', $public = true)      | Return the PDF as a System\Models\File to attach to a model |
| encrypt($password, $ownerPassword = '', $permissions = []) | Password-protect the PDF (CPDF backend)               |
| fake()                                                  | Static: replace the wrapper with a recorder for tests    |
| addInfo(array $info)                                    | Set PDF metadata such as Title or Author                 |
| save($filename, $disk = null)                           | Save the PDF to a file, optionally on a storage disk     |
| download($filename = 'document.pdf')                    | Make the PDF downloadable by the user                    |
| stream($filename = 'document.pdf')                      | Return a response with the PDF to show in the browser    |

All methods are available through Facade class `Renatio\DynamicPDF\Classes\PDF`.

## Tips

### Background image

The background image is shown only where the layout HTML uses `{{ background_img }}`. The default HTML of a layout
created in the backend does it on `<body>`; in other layouts, such as ones from view files, use the following code to
display it over the whole page:

```html
<style>@page { margin: 0; }</style>
<body style="background: url('{{ background_img }}') top left no-repeat; background-size: 100% 100%;">
```

Without `@page { margin: 0; }` the background covers only the area inside dompdf's default 1.2 cm page margins.

dompdf redraws the background at the page size in its DPI, 794 x 1123 px for A4 at the default 96 DPI, so a larger
image adds no sharpness on its own. For a higher-quality background, such as 300 DPI (2480 x 3508 px), use an image
that large and set the DPI in code or with `dpi` in the dompdf configuration, which the backend preview also uses.
This also makes the PDF larger:

```php
return PDF::loadTemplate($model->code)
    ->setDpi(300)
    ->stream();
```

### UTF-8 support

In your layout, set the UTF-8 meta tag in `head` section:

```html
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
```

If you have problems with foreign characters, then use the **DejaVu Sans** font family.

### Page breaks

You can use the CSS page-break-before/page-break-after properties to create a new page.

```html
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

Some hosting providers report `open_basedir` restriction errors for the log file. You can change the default log file
destination like so:

```php
return PDF::loadTemplate('renatio::invoice')
    ->setLogOutputFile(storage_path('temp/log.htm'))
    ->stream();
```

### Embed image inside PDF template

Use an absolute URL for the image, e.g. `https://app.dev/path_to_your_image`.

For this to work you must set `isRemoteEnabled` option.

```php
return PDF::loadTemplate('renatio::invoice', ['file' => $file])
    ->setIsRemoteEnabled(true)
    ->stream();
```

`$file` is an instance of `October\Rain\Database\Attach\File`.

Then in the template you can use the following example code:

```twig
{{ file.getPath }}

{{ file.getLocalPath }}

{{ file.getThumb(200, 200, {'mode': 'crop'}) }}
```

> Fetching stylesheets or images over HTTP requires the `allow_url_fopen` PHP setting.

> The backend PDF preview fetches remote resources only from the hosts listed in `allowed_remote_hosts` of the dompdf
> configuration plus the application host (`app.url` and the custom site URLs); when no host can be resolved at all it
> fetches nothing remote. It reads local files only from the directories October publishes (web root, modules,
> plugins, themes, app assets, public uploads, media and the resize cache) unless `chroot` is set in the
> configuration, and writes the dompdf log file only when `app.debug` is on. A schemeless `APP_URL` such as
> `myapp.test` is understood.

When `allow_url_fopen` is disabled, use a local path. You can use October `getLocalPath` function on the file object to
retrieve it.

### Attach the PDF to a model

`toFile()` returns a `System\Models\File` built from the rendered document, so the PDF can be attached with October's
`attachOne` / `attachMany` relations instead of being written to disk by hand:

```php
$order->invoice = PDF::loadTemplate('renatio::invoice', ['order' => $order])->toFile('invoice.pdf', public: false);
$order->save();
```

Pass `public: false` for a relation declared with `'public' => false`, otherwise the record points at the wrong
directory. The file is written to the uploads disk as soon as `toFile()` returns, so attach and save it, or call
`$file->delete()` when you abandon it.

### Save to a storage disk and set metadata

```php
PDF::loadTemplate('renatio::invoice')
    ->addInfo(['Title' => 'Invoice 2026/1', 'Author' => 'Acme'])
    ->save('invoices/2026-1.pdf', 's3');
```

### Password protection

```php
return PDF::loadTemplate('renatio::invoice')->encrypt('reader-password', 'owner-password', ['print'])->stream();
```

`encrypt()` renders the document, so call it last, right before the output method. Permissions are opt-in: without
`['print', 'copy', ...]` the reader cannot print or copy. Requires the CPDF backend.

### Download PDF via Ajax response

The October CMS AJAX framework saves a response with a `Content-Disposition: attachment` header as a file, so an AJAX
handler can return `download()`:

```php
public function onDownload()
{
    return PDF::loadTemplate('renatio::invoice')->download('invoice.pdf');
}
```

### Page numbers

Page numbers are stamped on every page after rendering, without enabling inline PHP:

```php
return PDF::loadTemplate('renatio::invoice')
    ->pageNumbers('Page {PAGE_NUM} of {PAGE_COUNT}', position: 'bottom-center', size: 9)
    ->stream();
```

`{PAGE_NUM}` and `{PAGE_COUNT}` are replaced on each page. Positions: `top-left`, `top-center`, `top-right`,
`bottom-left`, `bottom-center`, `bottom-right`; `font` (a family available in the document, for example one declared
with `@font-face` in the layout; the dompdf default font otherwise), `margin` (points) and `color` (RGB between 0
and 1) are optional. Requires the CPDF or PDFLib backend; the GD backend cannot draw page text.

Call `pageNumbers()` after loading the document; it applies to that document only. Inline PHP
(`setIsPhpEnabled(true)`) is no longer needed for page numbers and should stay off.

> **Security warning:** only enable `setIsPhpEnabled(true)` when the template content is fully trusted. Any
> `<script type="text/php">` block in the HTML is executed on the server, so enabling it for templates that can be
> edited by backend users allows remote code execution. The backend HTML and PDF preview never enables it.

## Testing

`PDF::fake()` replaces the wrapper for the rest of the test: no template is looked up, no Twig, dompdf, database or
filesystem work happens, `stream()` and `download()` return an empty `application/pdf` response, `output()` returns an
empty string, `save()` writes nothing and `toFile()` returns a record that can be attached and saved. The fake records
every `loadTemplate()`, `loadLayout()`, `loadView()`, `loadFile()`, `parseTemplate()` and `parseLayout()` call with its
data, layout and locale:

```php
$fake = PDF::fake();

$this->get('/backend/acme/orders/pdf/1');

$fake->assertRendered('acme::pdf.invoice', fn (array $data) => $data['order']->id === 1);
$fake->assertRenderedTimes('acme::pdf.invoice', 1);
$fake->assertNotRendered('acme::pdf.reminder');
```

`assertNothingRendered()` covers the negative case and `rendered()` returns the raw records. `pageNumbers()` keeps
validating its arguments under the fake.

## Console commands

`php artisan dynamicpdf:sync` synchronizes the registered PDF views with the database and lists what was created,
updated, deleted or failed; it exits with code 1 when any code fails to sync (a missing view file, a parse error or a
failed save). Run it after a deployment so the templates exist before the first backend visit.

`php artisan dynamicpdf:check` reports the dompdf configuration that fails silently: font, cache and temporary
directories (existence and write access, without creating anything), `chroot`, inline PHP and remote resources, and
every registered code without a view file. It exits with code 1 on a failure, so it can guard a deployment. Run both
commands after `october:migrate`.

## Examples

### Demo examples

There is a console command that will enable demo templates and layouts.

```bash
php artisan dynamicpdf:demo
```

To disable the demo, run the following command:

```bash
php artisan dynamicpdf:demo --disable
```

The first example shows invoice with custom font and image embed.

The second example shows usage of header & footer, page break and full background image.

### Render PDF in browser

```php
use Renatio\DynamicPDF\Classes\PDF; // import facade

public function pdf()
{
    $templateCode = 'renatio::invoice'; // unique code of the template
    $data = ['name' => 'John Doe']; // optional data used in template

    return PDF::loadTemplate($templateCode, $data)->stream('download.pdf');
}
```

Where `$templateCode` is a unique code specified when creating the template and `$data` is an optional array of
variables passed to the template.

In HTML template you can use `{{ name }}` to output `John Doe`.

### Download PDF

```php
use Renatio\DynamicPDF\Classes\PDF;

public function pdf()
{
    return PDF::loadTemplate('renatio::invoice')->download('download.pdf');
}
```

### Fluent interface

You can chain the methods:

```php
return PDF::loadTemplate('renatio::invoice')
    ->save('/path-to/my_stored_file.pdf')
    ->stream();
```

### Render with another layout

A template can be rendered with a different layout than the one stored with it, for example one letterhead per
company, without changing the template in the database:

```php
return PDF::loadTemplate('renatio::invoice', $data, layout: 'renatio::layouts.company_b')->stream();
```

Only the layout markup, CSS and background image are swapped; paper size and orientation still come from the
template, so call `setPaper()` when the other layout needs them changed.

### Render in another language

The document language usually should not depend on the language of the backend user who generated it. Pass the
locale to render in; language file translations (`trans`, `__`), Carbon dates and the `locale` template variable
follow it while the template is parsed, and the application locale is restored afterwards, also when parsing fails:

```php
return PDF::loadTemplate('renatio::invoice', $data, locale: 'de')->download('rechnung.pdf');
```

`loadTemplate()` and `loadLayout()` always add the `locale` variable, holding the application locale when no argument
is given; a `locale` key in your own data takes precedence. Inline PHP executed by dompdf during `output()` runs after
the locale has been restored.

#### Translate the templates themselves

Template title and markup, and layout markup and CSS, can be stored per language. Off by default; turn it on in
`config/multisite.php` and clear the application cache:

```php
'features' => [
    'renatio_dynamicpdf_template' => true,
],
```

Editing a template or layout with another site selected in the backend then writes a translation instead of
overwriting the stored content, the backend previews follow the selected site, and the `locale` argument above picks
the translation to render with:

```php
PDF::loadTemplate('acme.shop::pdf.invoice', $data, locale: 'pl')->download('faktura.pdf');
```

A language with no translation falls back to the stored content, and templates and layouts backed by a view file
keep following that file in the default language, so **Reset to Default** and the synchronization never touch a
translation. Per-language background images are not supported.

A registered view can ship localized siblings: `pdf.invoice` renders from `pdf.de.invoice`, `pdf.layouts.default`
from `pdf.layouts.de.default`. The locale chain is followed, so `de-AT` takes `pdf.de-AT.invoice` and falls back to
`pdf.de.invoice`. The sibling is read for that render only and never changes the stored row; a customized template,
a layout that is no longer *From view* or a stored translation wins over it.

```text
plugins/acme/shop/views/pdf/
    invoice.htm
    de/invoice.htm
    de-AT/invoice.htm
    layouts/default.htm
    layouts/de/default.htm
```

This is independent of RainLab.Translate, whose `|_` messages follow the site locale, not the `locale` argument.

### Change paper size and orientation

```php
return PDF::loadTemplate('renatio::invoice')
    ->setPaper('a4', 'landscape')
    ->stream();
```

Available [paper sizes](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php) (the `$PAPER_SIZES` array).

### PDF on CMS page

To display PDF on CMS page you can use PHP section of the page like so:

```php
use Renatio\DynamicPDF\Classes\PDF;

function onStart()
{
    return PDF::loadTemplate('renatio::invoice')->stream();
}
```

### Header and footer on every page

```html
<html>
<head>
  <style>
    @page { margin: 100px 25px; }
    header { position: fixed; top: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    footer { position: fixed; bottom: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: auto; }
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

The plugin ships the Open Sans font, which can be imported in the layout CSS section.

```css
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
