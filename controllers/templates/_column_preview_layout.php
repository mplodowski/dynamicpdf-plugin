<a href="<?= Backend::url('renatio/dynamicpdf/layouts/preview/'.$record->id) ?>" class="nolink"><?= e(trans('renatio.dynamicpdf::lang.templates.preview_html')) ?></a>
&middot;
<a href="<?= Backend::url('renatio/dynamicpdf/layouts/previewpdf/'.$record->id) ?>" target="_blank" class="nolink"><?= e(trans('renatio.dynamicpdf::lang.templates.preview_pdf')) ?></a>
