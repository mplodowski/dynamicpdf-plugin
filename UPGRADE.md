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

## Upgrading To 8.1.0

**Security release. Upgrade every installation running 8.0.x.** Requires PHP 8.2 and October CMS 4.0. Run
`php artisan october:migrate`.

The backend preview is sandboxed: no inline PHP, remote resources only from the application host and
`allowed_remote_hosts`, local files only from the asset directories. Reset the demo **Header and Footer** layout to
drop its page number script. TLS certificates are verified everywhere; set `DYNAMICPDF_ALLOW_SELF_SIGNED=true` on a
development host with a self-signed certificate. An unknown option setter now throws instead of being ignored.

Permissions are granular: creating, updating, deleting and previewing templates and layouts each have their own
permission. A role keeps only **Manage templates** and **Manage layouts**, so grant the rest under
**Settings → Administrators → Roles**.

The template and layout `code` columns get a unique index; the migration deletes duplicate rows permanently, keeping
the customised or locked one. Registered views are synchronised when a backend page opens or `dynamicpdf:sync` runs,
and a changed view file is written back to templates that were never customised. Use **Reset to default** on a
template an earlier version flagged *Customized* by mistake.

Templates and layouts can be translated per language, off by default and changing nothing until enabled. Set
`'renatio_dynamicpdf_template' => true` under `features` in `config/multisite.php` and clear the application cache.
