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

## Upgrading To 8.0.4

**Security release. Upgrade every installation running 8.0.x.** Plugin requires PHP 8.2 or higher and October CMS 4.0
or higher.

Calling an option setter that does not exist on the wrapper, dompdf or its options (a typo such as
`setRemoteEnabled()`, or `setAdminUsername()` removed by dompdf) now throws `UnexpectedValueException` instead of being
silently ignored.

TLS certificates are verified on every environment. On a development host with a self-signed certificate set
`DYNAMICPDF_ALLOW_SELF_SIGNED=true` in `.env` (or `allow_self_signed_certificates` in `config/renatio/dynamicpdf.php`),
or call the now public `allowSelfSignedCertificates()` on the wrapper for a single document.

The template and layout `code` columns get a unique index. Duplicate rows, which concurrent synchronisations could
leave behind, are removed by the migration: the customised (or locked) row is kept, otherwise the oldest one.

Registered PDF views are synchronised to the database when the *PDF Templates* settings page is opened or
`dynamicpdf:demo` runs, no longer on every request. Rendering a registered view that is not stored yet works without
the sync. A registered code without a view file is skipped and logged instead of silently stopping the sync.

The backend HTML and PDF preview no longer enables inline PHP. If you use the demo templates, open the
**Header and Footer** layout and click **Reset to default** to remove the page number script from the stored copy.
