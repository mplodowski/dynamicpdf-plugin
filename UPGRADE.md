# Upgrade guide

Versions not listed here need no action. Back up the database before upgrading.

## Upgrading To 1.1.0

Plugin requires October build 300+.

## Upgrading To 2.0.0

`PDFTemplate::render()` method was removed. Please switch to `Renatio\DynamicPDF\Classes\PDF` facade.

## Upgrading To 2.1.0

Method `setOrientation` was removed. Use `setPaper` instead.

## Upgrading To 2.1.1

Plugin requires **Stable** version of October and PHP >=5.5.9.

## Upgrading To 3.0.0

Plugin requires OctoberCMS build 420+ with Laravel 5.5 and PHP >=7.0.

## Upgrading To 4.0.0

Plugin requires PHP >=7.1 to work with the latest version of [dompdf](https://github.com/dompdf/dompdf) library.

For October composer based installation you must manually update project composer.json file to use at least PHP 7.1:

```
"config": {
    "preferred-install": "dist",
    "platform": {
        "php": "7.1"
    }
},
```

After this change run `composer update` command.

## Upgrading To 4.0.8

This is the latest version with support for October 1.x.

Using `setOptions` method to change dompdf options is no longer recommended. This will override all Laravel
dompdf configuration and use only options specified by method argument and dompdf defaults for options not set by the
developer.

Instead of using this method, please use dynamic method call for option you would like to change. Please read more
in [documentation](https://github.com/mplodowski/dynamicpdf-plugin/blob/master/README.md#configuration).

## Upgrading To 5.0.1

Plugin requires OctoberCMS v2.1.x with Laravel 6 and PHP >=7.2.

## Upgrading To 6.0.0

Plugin requires October CMS version 3.0 or higher, Laravel 9.0 or higher and PHP >=8.0.

Drop support for October CMS version 2.x.

## Upgrading To 7.0.0

Plugin requires Laravel Dompdf v2 and dompdf 2. `setOptions()` is deprecated and still replaces the whole options
object, as noted for 4.0.8; the new `setOption()` changes a single option and the dynamic `set*()` methods keep
working.

## Upgrading To 8.0.1

Plugin adds support for October CMS 4.0 while keeping 3.x, and upgrades to Laravel Dompdf v3 and dompdf 3. Options
removed by dompdf 3 (for example `enable_css_float` and `setAdminUsername()`) no longer apply, and the HTML5 parser is
always on.

## Upgrading To 9.0.0

**Security release.** Requires PHP 8.2 and October CMS 4.4; older sites stay on 8.0.3. Run `php artisan october:migrate`.

- Templates render in a Twig sandbox and receive plain arrays instead of models: eager-load relations or pass arrays.
  `config()`, `env()`, `{% cache %}` and similar calls are rejected. `DYNAMICPDF_SANDBOX=false` turns the sandbox off.
- Replace `<script type="text/php">` page numbers with the layout's *Page numbers* option or `->pageNumbers()`.
- The PDF preview loads remote files only from the application host and `allowed_remote_hosts`; use `|pdfasset` for
  local files.
- Layout CSS can no longer read server files: remove LESS `(inline)` imports and `data-uri()`.
- The migration deletes duplicate template and layout codes, keeping the customized one, and marks layouts it cannot
  prove unedited as *Customized*; use **Reset to Default** on those you never changed. Code reading a layout's
  `is_locked` must read `is_custom`, which holds the opposite value.
- Permissions are granular. Existing roles keep their access; narrow it under *Settings → Administrators*.
- `dynamicpdf:sync` no longer deletes templates whose view is gone; use `--prune`.
- An unknown `set*()` dompdf option now throws `UnexpectedValueException`.
- Set `DYNAMICPDF_ALLOW_SELF_SIGNED=true` on a development host with a self-signed certificate.
