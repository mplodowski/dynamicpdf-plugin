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
- Templates and layouts registered by plugins as view files, synchronized to the database, with reset to the file.
- Twig markup with theme partials, translations, global variables and `beforeRender` / `afterRender` events.
- Per-document layout, language, page numbers, password protection, paper size and orientation.
- Templates translated per language with October multisite, plus localized view files such as `pdf.de.invoice`.
- QR codes and barcodes with the `pdf_qr()` and `pdf_barcode()` Twig functions.
- Output as a browser stream, download, file on a storage disk or a `System\Models\File` to attach to a model.
- `PDF::fake()` with render assertions for tests, plus console commands to sync, check, render, export and import.

## Requirements

PHP 8.2+ and October CMS 4.4+.

Templates render in October's Twig sandbox (see [Twig environment](#twig-environment)). Still, grant the **Create** and
**Update** permissions for templates and layouts to trusted users only.

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

- `php artisan plugin:install Renatio.DynamicPDF`, or
- `composer require renatio/dynamicpdf-plugin` followed by `php artisan october:migrate`.

## Upgrading

Read the [upgrade guide](UPGRADE.md) before updating; versions not listed there need no action.

## Quick start

Create a template under *Settings > PDF > PDF Templates* (or run `php artisan dynamicpdf:demo`), e.g. with the code
`acme.shop::pdf.invoice` and the markup `<h1>Invoice {{ number }}</h1>`, then render it:

```php
use Renatio\DynamicPDF\Classes\PDF;

return PDF::loadTemplate('acme.shop::pdf.invoice', ['number' => '2026/1'])->stream('invoice.pdf');
```

`download()` sends it as an attachment (use it in AJAX handlers), `save()` writes it to disk.

## PDF content

A PDF template is either managed in the backend (*Settings > PDF > PDF Templates*) or supplied by a plugin as a view
file in its `views` directory and registered with `registerPDFTemplates` / `registerPDFLayouts`. Both use Twig.

## PDF layout views

The code is the path to the view file: **author.plugin::pdf.layouts.default** reads
`plugins/author/plugin/views/pdf/layouts/default.htm`.

A layout view has up to 3 sections separated by `==`: **configuration**, **CSS/LESS** (optional) and **HTML markup**:

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

| Parameter             | Description                                                                     |
|-----------------------|---------------------------------------------------------------------------------|
| **name**              | the layout name, optional; the code is used without it.                         |
| **pageNumbers**       | page numbers position, e.g. `bottom-center`, see [Page numbers](#page-numbers). |
| **pageNumbersText**   | page numbers text, `Page {PAGE_NUM} of {PAGE_COUNT}` translated by default.     |
| **pageNumbersSize**   | font size in points, 9 by default.                                              |
| **pageNumbersFont**   | font family used by the document, the default font otherwise.                   |
| **pageNumbersMargin** | distance from the page edge in points, 20 by default.                           |
| **pageNumbersColor**  | color as `#rrggbb`, black by default.                                           |

### Using PDF layouts

Database layouts are managed on the *Layouts* tab of *Settings > PDF > PDF Templates*. They hold the scaffold of the
PDF, like CMS layouts. The **code** is unique and cannot be changed once created.

## PDF template views

**author.plugin::pdf.invoice** reads `plugins/author/plugin/views/pdf/invoice.htm`. A template view has up to 2
sections, **configuration** and **HTML markup**:

```twig
title = "Invoice"
layout = "renatio.demo::pdf.layouts.default"
description = "Invoice template"
size = "a4"
orientation = "portrait"
==
<h1>Invoice</h1>
```

### Configuration section

| Parameter       | Description                                                                  |
|-----------------|------------------------------------------------------------------------------|
| **title**       | optional; the code is used without it.                                       |
| **layout**      | the layout code, optional.                                                   |
| **description** | optional.                                                                    |
| **size**        | paper size, optional; defaults to dompdf `default_paper_size`.               |
| **orientation** | paper orientation, optional; defaults to dompdf `default_paper_orientation`. |

**size** and **orientation** are case-insensitive (`A4` is stored as `a4`).

### Using PDF templates

Database templates are managed in *Settings > PDF > PDF Templates*. The **code** is unique and cannot be changed once
created.

If no database template has the code yet, a view registered under it with `registerPDFTemplates` is rendered instead.
An unregistered view is not looked up and `loadTemplate()` throws `ModelNotFoundException`. Layouts follow the same rule
with `registerPDFLayouts`.

## Registering PDF templates and layouts

Return the view names from `Plugin.php`:

```php
public function registerPDFTemplates()
{
    return [
        'renatio.demo::pdf.invoice',
        'renatio.demo::pdf.resume',
    ];
}

public function registerPDFLayouts()
{
    return [
        'renatio.demo::pdf.layouts.invoice',
        'renatio.demo::pdf.layouts.resume',
    ];
}
```

Registered views are synchronized to the database when a *PDF Templates* list or form page is displayed and when
`dynamicpdf:sync` or `dynamicpdf:demo` runs — not on every request, AJAX request or preview. Synchronization:

- creates missing templates and layouts;
- updates non-customized records from changed view files; customized records are left alone;
- keeps templates that are no longer registered (only `dynamicpdf:sync --prune` deletes them);
- skips a code whose view file is missing or unreadable and logs it once per process; a stored record whose view file
  is missing or empty, or whose layout code resolves to nothing, keeps its stored content.

Until a registered view is synchronized, `PDF::loadTemplate()` renders it straight from the file.

## Twig environment

Templates use the CMS Twig environment when the Cms module is installed and a theme is active (theme partials and
content blocks available), otherwise the system Twig environment. Plugin filters and functions from
`registerMarkupTags` work in both; Cms-module tags such as `|theme`, `|page` and `{% partial %}` need the Cms module.

Both run in October's Twig sandbox regardless of `cms.safe_mode`. Templates receive plain data:

- models become arrays of their visible attributes, `$appends` accessors and already loaded relations; collections
  become arrays; dates stay dates; attachments keep `path` and `url`;
- model methods and lazy-loaded relations are unavailable — eager-load with `with()`, add accessors to `$appends`, or
  pass arrays; models, queries and relations returned by other plugins' functions allow no method calls either;
- `config()`, `env()`, `response()`, `redirect()`, `abort()`, `ajaxHandler()`, `flash()`, `dump()`, `form_*` and macro
  functions and the `{% flash %}` and `{% cache %}` tags are rejected; filters such as `map` take only arrow functions;
  object methods cannot be static or receive PHP function names.

A model method, lazy relation or accessor outside `$appends` renders as empty, and the log gets a warning naming the
template, line and model.

Set `DYNAMICPDF_SANDBOX=false` (`sandbox` in `config/renatio/dynamicpdf.php`) only when everyone who can edit templates
may run code on the server.

### Plugin assets

dompdf treats every `http(s)://` address (including `|app`) as remote, and remote files are not loaded while
`enable_remote` is off (the default). Use `|pdfasset` for plugin files: it takes a path relative to the project root
and returns the absolute path on disk.

```twig
<link href="{{ 'plugins/acme/shop/assets/css/pdf.css'|pdfasset }}" rel="stylesheet">
<img src="{{ 'plugins/acme/shop/assets/img/logo.png'|pdfasset }}">
```

Only existing files inside `plugins` and the dompdf `chroot` resolve; any other path (`..`, absolute, URL) returns an
empty string and logs a warning. In the backend HTML preview and outside a DynamicPDF render it returns the same URL as
`|app`. Quote the value inside CSS `url('...')`.

## Global variables

Variables every template and layout receives are registered in `Plugin.php`. Closures are resolved on every render,
so keep them cheap:

```php
public function registerPDFVariables()
{
    return [
        'company' => 'Acme Ltd',
        'vat_rate' => fn () => Settings::get('vat_rate'),
    ];
}
```

Data passed to `loadTemplate()`, `loadLayout()` or `parseTemplate()` wins over a registered variable. The names
`content_html`, `css`, `background_img` and `locale` are reserved and ignored when registered.

The template HTML editor autocompletes sample data keys, global variables, `locale`, nested keys, filters and tags
inside `{{ }}` / `{% %}`; Ctrl+Space lists all of them.

## Events

| Event                             | Payload                                                      | Return value                                                  |
|-----------------------------------|--------------------------------------------------------------|---------------------------------------------------------------|
| `renatio.dynamicpdf.beforeRender` | `PDFWrapper $pdf`, `Template\|Layout $model`, `array $data`  | an array merged on top of the render data                     |
| `renatio.dynamicpdf.afterRender`  | `PDFWrapper $pdf`, `Template\|Layout $model`, `string $html` | a string replacing the HTML for the next listener and the PDF |

```php
Event::listen('renatio.dynamicpdf.beforeRender', function ($pdf, $model, array $data) {
    if ($model->code !== 'acme.shop::pdf.invoice' || ! isset($data['order'])) {
        return;
    }

    return ['watermark' => $data['order']->isDraft() ? 'DRAFT' : null];
});
```

- Both fire once per document — a template with its layout (`loadTemplate()`, `parseTemplate()`) or a layout on its
  own (`loadLayout()`, `parseLayout()`) — including backend previews, so avoid side effects in listeners.
- Listeners run for every document: check `$model->code` and the keys you read. Returning nothing changes nothing.
- `beforeRender` results are merged in turn; reserved names (`content_html`, `css`, `background_img`, `locale`) are
  stripped.
- `afterRender` listeners run in registration order, each receiving the previous one's HTML; returning `false` stops
  the rest.

## Usage

Templates and layouts are managed in *Settings > PDF > PDF Templates*.

- A record edited in the backend is marked *Customized* and no longer follows its view file. Editing only sample data
  or translations (another site selected) does not customize it. A customized layout ignores its localized view files.
- *Reset to Default* restores a registered record from its view file; registered records offer it instead of *Delete*.
- A layout used by a template cannot be deleted. The layout list shows the number of templates using it (*Used by*).
- *Duplicate* creates a *Customized* copy with a `_copy` code (`_copy2`, ... when taken).
- *Sample data* (a JSON object) is passed to the HTML and PDF previews, which show the current unsaved form values.
- The *History* tab keeps the last 20 saved versions with date and author, a line diff per version, and *Restore*
  (**Update** permission), which adds a new version and marks the record *Customized*. Versions hold default-language
  values only. Background image changes are recorded, but *Restore* does not bring back a replaced image.
  Synchronization and console commands add no versions.

Layouts hold what repeats on every page (header, footer, background image, CSS/LESS); templates hold the content. Check
dompdf's [CSSCompatibility](https://github.com/dompdf/dompdf/wiki/CSSCompatibility).

## Permissions

Granted under **Settings → Administrators**, on the **PDF** tab. Super users bypass all of them; every action is also
checked on the server.

| Permission            | What it unlocks                                                     |
|-----------------------|---------------------------------------------------------------------|
| **Access templates**  | The *PDF Templates* page with the template list.                    |
| **Create templates**  | The *New template* form and duplicating a template.                 |
| **Update templates**  | Opening and saving the template form, and resetting it to its view. |
| **Delete templates**  | Deleting a template.                                                |
| **Preview templates** | The HTML and PDF preview of a template.                             |
| **Access layouts**    | The *Layouts* tab (also needs **Access templates**).                |
| **Create layouts**    | The *New layout* form and duplicating a layout.                     |
| **Update layouts**    | Opening and saving the layout form, and resetting it to its view.   |
| **Delete layouts**    | Deleting a layout that no template uses.                            |
| **Preview layouts**   | The HTML and PDF preview of a layout.                               |

## Configuration

Publish `config/dompdf.php` to change the defaults:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Per document, use the dynamic setters or `setOption()`:

```php
PDF::loadTemplate('renatio::invoice')
    ->setDpi(300)
    ->setOption(['defaultFont' => 'sans-serif'])
    ->stream();
```

Common options: `dpi`, `default_font`, `default_paper_size`, `enable_remote` (off by default; needed for resources
loaded by URL), `allowed_remote_hosts`, `chroot` and `font_dir`. See the published config and
[Dompdf\Options](https://github.com/dompdf/dompdf/blob/master/src/Options.php). Each option has a camel-cased `set*()`
method, except `enable_*`: `setIsRemoteEnabled()`, `setIsPhpEnabled()`, `setIsJavascriptEnabled()`,
`setIsFontSubsettingEnabled()` and `setIsPdfAEnabled()`. `enable_html5_parser` has no effect since dompdf 3. An unknown
setter throws `UnexpectedValueException`.

### Self-signed certificates

Remote resources are fetched with full TLS verification. On a development host set `DYNAMICPDF_ALLOW_SELF_SIGNED=true`
(`allow_self_signed_certificates` in `config/renatio/dynamicpdf.php`) for every wrapper, including `loadHTML()` and
after `setOptions()`, or call `allowSelfSignedCertificates()` for one document. Both are ignored in production.

## Methods

All methods are available through the facade `Renatio\DynamicPDF\Classes\PDF`.

| Method                                                                                                                                         | Description                                                                                                                                                      |
|------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| loadTemplate($code, array $data = [], $encoding = null, $layout = null, $locale = null)                                                        | Load backend template, optionally with another layout and locale                                                                                                 |
| loadLayout($code, array $data = [], $encoding = null, $locale = null)                                                                          | Load backend layout, optionally in another locale                                                                                                                |
| loadTemplateModel(Template $template, array $data = [], $encoding = null, $locale = null)                                                      | Load a template model as it is, unsaved changes included                                                                                                         |
| loadLayoutModel(Layout $layout, array $data = [], $encoding = null, $locale = null)                                                            | Load a layout model as it is, unsaved changes included                                                                                                           |
| pageNumbers($text = 'Page {PAGE_NUM} of {PAGE_COUNT}', $position = 'bottom-center', $size = 9, $font = null, $margin = 20, $color = [0, 0, 0]) | Stamp page numbers on every page of the loaded document                                                                                                          |
| forBrowser($forBrowser = true)                                                                                                                 | Render HTML for a browser, with URLs from `\|pdfasset` and `background_img` instead of local paths                                                               |
| allowSelfSignedCertificates()                                                                                                                  | Accept self-signed TLS certificates for remote resources                                                                                                         |
| allowRemoteApplicationAssets()                                                                                                                 | Enable remote resources only from the configured and application hosts (none when there are none), and narrow a default `chroot` to the public asset directories |
| loadHTML($string, $encoding = null)                                                                                                            | Load HTML string                                                                                                                                                 |
| loadFile($file)                                                                                                                                | Load HTML string from a file                                                                                                                                     |
| loadView($view, array $data = [], array $mergeData = [], $encoding = null)                                                                     | Load a Laravel view                                                                                                                                              |
| parseTemplate(Template $template, array $data = [])                                                                                            | Parse backend template using Twig                                                                                                                                |
| parseLayout(Layout $layout, array $data = [])                                                                                                  | Parse backend layout using Twig                                                                                                                                  |
| setOption($attribute, $value = null)                                                                                                           | Change one dompdf option, or an array of them                                                                                                                    |
| setOptions(array $options, $mergeWithDefaults = false)                                                                                         | Replace the whole dompdf options object                                                                                                                          |
| getDomPDF()                                                                                                                                    | Get the DomPDF instance                                                                                                                                          |
| setPaper($paper, $orientation = 'portrait')                                                                                                    | Set the paper size and orientation (default A4/portrait)                                                                                                         |
| setWarnings($warnings)                                                                                                                         | Show or hide warnings                                                                                                                                            |
| output()                                                                                                                                       | Output the PDF as a string                                                                                                                                       |
| toFile($filename = 'document.pdf', $public = true)                                                                                             | Return the PDF as a System\Models\File to attach to a model                                                                                                      |
| attachment($filename = 'document.pdf')                                                                                                         | Return the PDF as an e-mail attachment                                                                                                                           |
| encrypt($password, $ownerPassword = '', $permissions = [])                                                                                     | Password-protect the PDF (CPDF backend)                                                                                                                          |
| fake()                                                                                                                                         | Static: replace the wrapper with a recorder for tests                                                                                                            |
| addInfo(array $info)                                                                                                                           | Set PDF metadata such as Title or Author                                                                                                                         |
| save($filename, $disk = null)                                                                                                                  | Save the PDF to a file, optionally on a storage disk                                                                                                             |
| download($filename = 'document.pdf')                                                                                                           | Make the PDF downloadable by the user                                                                                                                            |
| stream($filename = 'document.pdf')                                                                                                             | Return a response with the PDF to show in the browser                                                                                                            |

Methods return the wrapper, so they can be chained:

```php
return PDF::loadTemplate('renatio::invoice')
    ->save('/path-to/my_stored_file.pdf')
    ->stream();
```

## Tips

### Background image

The layout background shows only where the layout HTML uses `{{ background_img }}` (the default HTML of a new backend
layout does it on `<body>`). For a full-page background:

```html
<style>@page { margin: 0; }</style>
<body style="background: url('{{ background_img }}') top left no-repeat; background-size: 100% 100%;">
```

Without `@page { margin: 0; }` it covers only the area inside dompdf's default 1.2 cm margins.

- In a PDF `{{ background_img }}` is a local path, so `enable_remote` is not needed. A file on a remote disk (S3) is
  copied to `storage/temp/dynamicpdf` and deleted with the PDF object. In the backend HTML preview it is the URL.
- A file outside the dompdf `chroot` keeps its URL and loads only with `enable_remote` and its host in
  `allowed_remote_hosts`.
- dompdf redraws the background at page size × DPI (794 × 1123 px for A4 at 96 DPI). For a sharper background use a
  larger image and raise the DPI (e.g. 300 DPI, 2480 × 3508 px) with `setDpi()` or `dpi` in the config, which the
  backend preview also uses. This makes the PDF larger.

### UTF-8 support

Set `<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>` in the layout `head`. For foreign characters
use the **DejaVu Sans** font.

### Page breaks

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

### Render log

dompdf writes no log by default. To get frame count, memory, time and debug output, set `log_output_file` in the config
or per document; the file is overwritten on every render:

```php
return PDF::loadTemplate('renatio::invoice')
    ->setLogOutputFile(storage_path('temp/log.htm'))
    ->stream();
```

The backend PDF preview writes `storage/temp/log.htm` only when `app.debug` is on.

### Embed image inside PDF template

Prefer local files, which need no `enable_remote`: an attachment's local path, `|pdfasset`, or a data URI. `$file` is
an `October\Rain\Database\Attach\File`:

```php
return PDF::loadTemplate('renatio::invoice', ['file' => $file])->stream();
```

```twig
<img src="{{ file.getLocalPath }}">
<img src="{{ 'plugins/acme/shop/assets/img/logo.png'|pdfasset }}">
<img src="data:image/png;base64,{{ logo_base64 }}">
```

`getLocalPath` copies a remote-disk file to `storage/temp/uploads`, which October does not delete. Local paths must lie
inside the dompdf `chroot` (the project root by default).

A URL (`file.getPath`, `file.getThumb(200, 200, {'mode': 'crop'})`) is remote. Always enable remote files together with
allowed hosts: with `allowed_remote_hosts` `null` or empty, dompdf fetches any address.

```php
return PDF::loadTemplate('renatio::invoice', ['file' => $file])
    ->setIsRemoteEnabled(true)
    ->setAllowedRemoteHosts(['cdn.example.com'])
    ->stream();
```

dompdf uses cURL when available and needs `allow_url_fopen` only without it.

> The backend PDF preview fetches remote resources only from `allowed_remote_hosts` plus the application hosts
> (`app.url` and custom site URLs; schemeless `APP_URL` is understood), and nothing remote when none resolve. Unless
> `chroot` is configured, it reads local files only from public directories (web root, modules, plugins, themes, app
> assets, public uploads, media, resize cache), so a protected upload renders from code but not in the preview.

### QR codes and barcodes

`pdf_qr(text, size = 150, margin = 0, options = {})` and `pdf_barcode(text, type = 'C128', widthFactor = 2, height = 50)`
return a PNG data URI (an SVG data URI without the GD extension), so `enable_remote` is not needed. They require:

```bash
composer require endroid/qr-code picqer/php-barcode-generator
```

```twig
<img src="{{ pdf_qr('https://example.com/invoices/' ~ invoice.number ~ '/pay') }}" width="120">
<img src="{{ pdf_barcode(invoice.ean, 'EAN13') }}">
```

`size` and `margin` are pixels of the image; the printed size comes from the `<img>` `width`, so generate it larger
than displayed (the demo invoice makes 192 px and shows 96 px).

`pdf_qr()` also takes a hash of options, in place of the size or after the margin:

| Option             | Value                                   | Default   |
|--------------------|-----------------------------------------|-----------|
| `size`             | image width and height in pixels        | `150`     |
| `margin`           | white space around the code in pixels   | `0`       |
| `color`            | colour of the code, `#rgb` or `#rrggbb` | `#000000` |
| `background`       | `#rgb`, `#rrggbb` or `transparent`      | `#ffffff` |
| `error_correction` | `low`, `medium`, `quartile` or `high`   | `low`     |

```twig
<img src="{{ pdf_qr(invoice.url, {size: 300, color: '#1f2937', error_correction: 'medium'}) }}" width="100">
```

Barcode types: `EAN13`, `EAN8`, `UPCA`, `C128`, `C39` (constants of `Renatio\DynamicPDF\Classes\Codes`). Arguments are
positional. A missing package, unsupported type, non-positive or non-integer size, or text the code cannot hold (wrong
EAN-13 check digit, non-ASCII, too long for QR) stops the render with an explanatory message.

`pdf_qr_available()` and `pdf_barcode_available()` tell whether a supported version of the package is installed:

```twig
{% if pdf_qr_available() %}
    <img src="{{ pdf_qr('https://example.com/invoices/123') }}" width="80">
{% endif %}
```

### Attach the PDF to a model

`toFile()` returns a `System\Models\File` for `attachOne` / `attachMany` relations:

```php
$order->invoice = PDF::loadTemplate('renatio::invoice', ['order' => $order])->toFile('invoice.pdf', public: false);
$order->save();
```

Pass `public: false` for a relation declared with `'public' => false`. The file is written to the uploads disk
immediately, so attach and save it, or call `$file->delete()`.

### Attach the PDF to an e-mail

`attachment()` returns an `application/pdf` `Illuminate\Mail\Attachment`, rendered when added to the message:

```php
Mail::send('acme.shop::mail.invoice', $data, function ($message) use ($order) {
    $message->to($order->email);
    $message->attach(PDF::loadTemplate('acme.shop::pdf.invoice', ['order' => $order])->attachment('invoice.pdf'));
});
```

In a mailable, return it from `attachments()` (not the constructor — the attachment holds a closure and cannot be
serialized for the queue); a queued mailable then renders the PDF in the worker:

```php
public function attachments(): array
{
    return [PDF::loadTemplate('acme.shop::pdf.invoice', ['order' => $this->order])->attachment('invoice.pdf')];
}
```

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

`encrypt()` renders the document, so call it right before the output method. Permissions are opt-in: without
`['print', 'copy', ...]` the reader cannot print or copy. Requires the CPDF backend.

### Download PDF via Ajax response

The AJAX framework saves a `Content-Disposition: attachment` response as a file, so a handler can return `download()`:

```php
public function onDownload()
{
    return PDF::loadTemplate('renatio::invoice')->download('invoice.pdf');
}
```

### Download a record's PDF in the backend

In your plugin's controller:

```php
use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Classes\PDF;

public function update_onDownloadInvoice($recordId)
{
    if (! $this->user->hasAccess('acme.shop.orders.invoice')) {
        throw new ForbiddenException;
    }

    $order = $this->formFindModelObject($recordId);

    return PDF::loadTemplate('acme.shop::pdf.invoice', ['order' => $order], locale: $order->locale ?: null)
        ->download('invoice-' . str_slug(str_replace('/', '-', $order->number)) . '.pdf');
}
```

And in the update form toolbar (`controllers/orders/update.php`):

```php
<?= Ui::ajaxButton(label: 'Download invoice', handler: 'onDownloadInvoice', class: 'btn-default') ?>
```

The PDF shows the saved record, not unsaved form changes. Register the permission in `Plugin::registerPermissions()`.

### Other rendering engines

dompdf supports CSS 2.1 and parts of CSS 3, without flexbox or grid. For those, pass the HTML from
`PDF::parseTemplate()` to another tool such as Gotenberg or Browsershot:

```php
use Renatio\DynamicPDF\Models\Template;

$html = PDF::parseTemplate(Template::byCode('acme.shop::pdf.invoice'), ['order' => $order]);
```

The HTML uses the current application locale and public URLs for assets. Paper size, orientation and layout page
numbers are not in the HTML; set them in the other tool.

### Page numbers

Set a position under *Options > Page numbers* in the layout form (or `pageNumbers = "bottom-center"` in a layout view)
and every template using the layout gets page numbers. An empty text falls back to "Page {PAGE_NUM} of {PAGE_COUNT}" in
the render language.

From code (applies to that document only and overrides the layout setting):

```php
return PDF::loadTemplate('renatio::invoice')
    ->pageNumbers('Page {PAGE_NUM} of {PAGE_COUNT}', position: 'bottom-center', size: 9)
    ->stream();
```

Positions: `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`. Optional `font` (a
family available in the document, e.g. from `@font-face`), `margin` (points) and `color` (RGB 0–1). Requires the CPDF or
PDFLib backend.

> **Security warning:** page numbers do not need inline PHP. Enable `setIsPhpEnabled(true)` or `enable_php` only for
> fully trusted content: any `<script type="text/php">` block runs on the server. The backend previews never run
> inline PHP.

## Testing

`PDF::fake()` replaces the wrapper for the rest of the test: no lookup, Twig, dompdf, database or filesystem work.
`stream()` / `download()` return an empty `application/pdf` response, `output()` an empty string, `save()` writes
nothing, `toFile()` returns an attachable record and `attachment()` an empty attachment. Every `loadTemplate()`,
`loadLayout()`, `loadView()`, `loadFile()`, `parseTemplate()` and `parseLayout()` call is recorded with its data,
layout and locale:

```php
$fake = PDF::fake();

$this->get('/backend/acme/orders/pdf/1');

$fake->assertRendered('acme::pdf.invoice', fn (array $data) => $data['order']->id === 1);
$fake->assertRenderedTimes('acme::pdf.invoice', 1);
$fake->assertNotRendered('acme::pdf.reminder');
```

Also `assertNothingRendered()` and `rendered()` (the raw records). `pageNumbers()` still validates its arguments.

To test a failed render, use `$fake->throwOnRender(new RuntimeException('...'))`: every later render is recorded and
then throws. Do not combine `PDF::fake()` with `PDF::shouldReceive()`, which replaces the fake with a mock.

## Console commands

Run `dynamicpdf:sync` and `dynamicpdf:check` after `october:migrate` on every deployment.

`php artisan dynamicpdf:sync` synchronizes registered views and lists what was created, updated or failed and which
non-customized templates are no longer registered. `--prune` deletes those (with translations) after confirmation;
`--force` skips it. Exits with code 1 when any code fails to sync.

`php artisan dynamicpdf:check` reports silent dompdf misconfiguration: font, cache and temp directories (existence and
write access), `chroot`, inline PHP, remote resources, registered codes without a view file, and the `pdf_qr()` /
`pdf_barcode()` packages. It also lists customized templates and layouts that match their view apart from whitespace,
candidates for **Reset to Default**. Exits with code 1 on a failure.

### Render

`php artisan dynamicpdf:render <code>` renders a template like your application does.

| Option      | Effect                                                                        |
|-------------|-------------------------------------------------------------------------------|
| `--output=` | output path, `storage/temp/<code>.pdf` by default                             |
| `--data=`   | variables as a JSON object or `@path/to/file.json`; overrides `--sample` keys |
| `--sample`  | use the template's *Sample data*                                              |
| `--layout=` | as in `PDF::loadTemplate()`                                                   |
| `--locale=` | as in `PDF::loadTemplate()`                                                   |
| `--html`    | write the HTML (with browser asset URLs) instead of the PDF                   |
| `-v`        | show the full exception on failure                                            |

It prints the written path and dompdf warnings (none with `--html`), and exits with code 1 on an unknown code, invalid
data, Twig or dompdf error, or unwritable output path.

```bash
php artisan dynamicpdf:render acme.shop::pdf.invoice --sample --output=invoice.pdf
php artisan dynamicpdf:render acme.shop::pdf.invoice --data=@order.json --locale=de --html
```

### Export and import

`php artisan dynamicpdf:export [codes...]` writes templates and layouts to one JSON file: content, paper and page number
settings, sample data, the *Customized* flag, translations and the layout background image (base64). A template brings
its layout along. Default path `storage/app/dynamicpdf-export-<date>.json`; `--path=-` prints to stdout.

```bash
php artisan dynamicpdf:export
php artisan dynamicpdf:export acme.shop::pdf.invoice acme.shop::pdf.quotation --path=pdf-templates.json
```

`php artisan dynamicpdf:import <file>` imports layouts first and prints what was created, updated or skipped. Existing
codes are skipped unless `--force` overwrites them. Records are validated like a backend save (code, Twig, LESS) in one
transaction: the first invalid record aborts the whole import.

```bash
php artisan dynamicpdf:import pdf-templates.json
php artisan dynamicpdf:import pdf-templates.json --force
```

- A non-customized record stays so only if the target registers the same view; otherwise it is imported as customized.
- Background images must be JPG, PNG, GIF or WebP; a layout whose background file is missing is exported without it,
  with a warning.
- Translations for the target's default language are not imported.

## Examples

### Demo examples

```bash
php artisan dynamicpdf:demo
php artisan dynamicpdf:demo --disable
```

The demo adds an invoice (custom font, embedded image) and a document with header and footer, page break and full-page
background. `--disable` deletes them, except a customized demo template or a demo layout that is customized or still
used, and lists what it kept.

### Render PDF in browser

```php
use Renatio\DynamicPDF\Classes\PDF;

public function pdf()
{
    return PDF::loadTemplate('renatio::invoice', ['name' => 'John Doe'])->stream('download.pdf');
}
```

In the template, `{{ name }}` outputs `John Doe`. Use `->download('download.pdf')` to download instead.

### Render with another layout

Swaps the layout markup, CSS and background image without changing the stored template; paper size and orientation
still come from the template (use `setPaper()` to change them):

```php
return PDF::loadTemplate('renatio::invoice', $data, layout: 'renatio::layouts.company_b')->stream();
```

### Render in another language

Translations (`trans`, `__`), Carbon dates and the `locale` variable follow the `locale` argument while parsing; the
application locale is restored afterwards, also on failure:

```php
return PDF::loadTemplate('renatio::invoice', $data, locale: 'de')->download('rechnung.pdf');
```

`loadTemplate()` and `loadLayout()` always add `locale` (the application locale by default); a `locale` key in your
data wins. Inline PHP run by dompdf during `output()` runs after the locale is restored.

#### Translate the templates themselves

Template title and markup, and layout markup and CSS, can be stored per language. Enable it in `config/multisite.php`
and clear the application cache:

```php
'features' => [
    'renatio_dynamicpdf_template' => true,
],
```

Editing with another site selected in the backend then saves a translation, previews follow the selected site, and the
`locale` argument picks the translation:

```php
PDF::loadTemplate('acme.shop::pdf.invoice', $data, locale: 'pl')->download('faktura.pdf');
```

A language without a translation falls back to the stored content. **Reset to Default** and synchronization never touch
translations. Per-language background images are not supported.

With the feature on and a `locale` argument passed, a registered view can ship localized siblings: `pdf.invoice`
renders from `pdf.de.invoice`, `pdf.layouts.default` from `pdf.layouts.de.default`. `de-AT` tries `pdf.de-AT.invoice`,
then `pdf.de.invoice`. A customized record or a stored translation wins over the sibling, which never changes the stored
row.

```text
plugins/acme/shop/views/pdf/
    invoice.htm
    de/invoice.htm
    de-AT/invoice.htm
    layouts/default.htm
    layouts/de/default.htm
```

RainLab.Translate `|_` messages follow the site locale, not the `locale` argument.

### Change paper size and orientation

```php
return PDF::loadTemplate('renatio::invoice')
    ->setPaper('a4', 'landscape')
    ->stream();
```

Available [paper sizes](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php) (the `$PAPER_SIZES` array).

### PDF on CMS page

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

The plugin ships Open Sans. Declare fonts in a `<style>` element of the layout HTML — the *CSS* tab is LESS without
Twig, so `|pdfasset` works only in the HTML. Repeat the rule for `OpenSans-Bold.ttf` (`font-weight: bold`),
`OpenSans-Italic.ttf` (`font-style: italic`) and `OpenSans-BoldItalic.ttf` (both):

```html
<style>
@font-face {
    font-family: 'Open Sans';
    src: url('{{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf'|pdfasset }}');
}

body {
    font-family: 'Open Sans', sans-serif;
}
</style>
```

The backend HTML preview embeds `ttf`, `otf`, `woff` and `woff2` fonts from `@font-face` rules in `<style>` elements
when they are public application files (up to 5 MB each); fonts from `<link>`ed stylesheets show a fallback there.

dompdf embeds full font files unless `enable_font_subsetting` is on, which keeps only the used glyphs (a one-page
document in four Open Sans styles shrinks from about 525 KB to 30 KB). Set it in the config or per document:

```php
return PDF::loadTemplate('renatio::invoice')
    ->setIsFontSubsettingEnabled(true)
    ->stream();
```
