# Upgrade guide

- [Upgrading to 1.1.0 from 1.0.2](#upgrade-1.1.0)
- [Upgrading to 2.0.0 from 1.1.5](#upgrade-2.0.0)
- [Upgrading to 2.1.0 from 2.0.1](#upgrade-2.1.0)
- [Upgrading to 2.1.1 from 2.1.0](#upgrade-2.1.1)
- [Upgrading to 3.0.0 from 2.1.6](#upgrade-3.0.0)

<a name="upgrade-1.1.0"></a>
## Upgrading To 1.1.0

Plugin requires October build 300+.

<a name="upgrade-2.0.0"></a>
## Upgrading To 2.0.0

`PDFTemplate::render()` method was removed. Please switch to `Renatio\DynamicPDF\Classes\PDF` facade.

<a name="upgrade-2.1.0"></a>
## Upgrading To 2.1.0

Method `setOrientation` was removed. Use `setPaper` instead.

<a name="upgrade-2.1.1"></a>
## Upgrading To 2.1.1

Plugin requires **Stable** version of October and PHP >=5.5.9.

<a name="upgrade-3.0.0"></a>
## Upgrading To 3.0.0

Plugin requires OctoberCMS build 420+ with Laravel 5.5 and PHP >=7.0.