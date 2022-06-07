# Upgrade guide

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

Using `setOptions` method to change dompdf options is no longer recommended. This will cause to override all laravel
dompdf configuration and use only specified by method argument and dompdf defaults for rest options not set by a
developer.

Instead of using this method, please use dynamic method call for option you would like to change. Please read more
in [documentation](https://github.com/mplodowski/dynamicpdf-plugin/blob/master/README.md#configuration-configuration).

## Upgrading To 5.0.1

Plugin requires OctoberCMS v2.1.x with Laravel 6 and PHP >=7.2.

## Upgrading To 6.0.0

Plugin requires October CMS version 3.0 or higher, Laravel 9.0 or higher and PHP >=8.0.

Drop support for October CMS version 2.x.
