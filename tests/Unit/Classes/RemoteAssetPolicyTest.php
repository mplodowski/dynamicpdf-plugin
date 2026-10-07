<?php

use Dompdf\Dompdf;
use Renatio\DynamicPDF\Classes\RemoteAssetPolicy;

describe('RemoteAssetPolicy', function () {
    beforeEach(function () {
        $this->basePath = base_path();
        $this->root = sys_get_temp_dir() . '/dynamicpdf-root-' . uniqid();
        mkdir($this->root);
    });

    afterEach(fn () => rmdir($this->root));

    it('keeps the project root out of the preview chroot when there is no public folder', function () {
        app()->setBasePath($this->root);

        try {
            $dompdf = new Dompdf;
            $dompdf->getOptions()->setChroot([$this->root]);

            (new RemoteAssetPolicy)->allowApplicationAssets($dompdf);

            [$publicPath, $pluginsPath] = [public_path(), plugins_path()];
        } finally {
            app()->setBasePath($this->basePath);
        }

        expect($publicPath)->toBe($this->root)
            ->and($dompdf->getOptions()->getChroot())->toContain($pluginsPath)
            ->not->toContain($this->root);
    });
});
