<?php

use Dompdf\Dompdf;
use Renatio\DynamicPDF\Classes\RemoteAssetPolicy;

describe('RemoteAssetPolicy', function () {
    beforeEach(function () {
        $this->basePath = base_path();
        $this->root = sys_get_temp_dir() . '/dynamicpdf-root-' . uniqid();
        mkdir($this->root);
    });

    afterEach(function () {
        app()->setBasePath($this->basePath);
        rmdir($this->root);
    });

    it('keeps the project root out of the preview chroot when there is no public folder', function () {
        app()->setBasePath($this->root);
        $dompdf = new Dompdf;
        $dompdf->getOptions()->setChroot([$this->root]);

        (new RemoteAssetPolicy)->allowApplicationAssets($dompdf);

        expect(public_path())->toBe($this->root)
            ->and($dompdf->getOptions()->getChroot())->toContain(plugins_path())
            ->not->toContain($this->root);
    });
});
