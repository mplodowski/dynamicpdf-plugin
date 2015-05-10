#Dynamic PDF plugin

This plugin adds backed-end layouts and templates pdf management features to [OctoberCMS](http://octobercms.com).

This plugin use Laravel Package [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf).

####Features
* Manage PDF Templates and Layouts in OctoberCMS backend
* Example Invoice.pdf template with background image, custom header and footer, custom Open Sans font embedding
* Simple function for rendering PDF documents inside OctoberCMS using DOMPDF library
* Twig support in templates and layouts
* Style your templates using pure CSS

####Like this plugin?
If you like this plugin, give this plugin a Like or Make donation with PayPal.

###Documentation
###Installation
To install this plugin you have to click on __add to project__ or need to type __Renatio.DynamicPDF__ in Backend *System > Updates > Install Plugin*

###Using PDF templates

PDF templates reside in the database and can be created in the back-end area via Settings > PDF > PDF Templates. The code specified in the template is a unique identifier and cannot be changed once created.

You can use Twig in Your Layouts and Templates.

You can render PDF template using this PHP code:

    return PDFTemplate::render($template_code, $data);

Where `$template_code` is an unique code You specify when creating the template, `$data` is optional array of twig fields values which will be replaced in template.

### Background image

After adding background image to Your Layout, please add this to your body tag:

    <body style="background: url({{ background_img }}) top left no-repeat;">
    
Background image should be 96 DPI size.

###Styling PDF

You can style Your PDF document in CSS layout section. Here You can check CSS support for [DOMPDF](https://code.google.com/p/dompdf/wiki/CSSCompatibility).

###Examples
After installation there will an example PDF invoice document.