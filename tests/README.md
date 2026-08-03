# Dynamic PDF Plugin Tests

## Running Tests

```bash
php vendor/bin/pest plugins/renatio/dynamicpdf/tests -c plugins/renatio/dynamicpdf/phpunit.xml -p
```

## Test Structure

### Unit Tests

- **PluginTest** - Tests plugin registration and boot functionality
- **Models/LayoutTest** - Tests PDF layout model
- **Models/TemplateTest** - Tests PDF template model
- **Classes/PDFTest** - Tests PDF facade functionality
- **Classes/PDFWrapperTest** - Tests PDF wrapper class
- **Classes/PDFParserTest** - Tests PDF template parsing
- **Classes/PDFManagerTest** - Tests PDF templates and layouts management
- **Classes/SyncTemplatesTest** - Tests synchronization of PDF templates from views
- **Console/DemoTest** - Tests demo artisan command

### Integration Tests

- **PluginRegistrationTest** - Tests plugin registration within October CMS
