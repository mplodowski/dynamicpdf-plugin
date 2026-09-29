<?php

use Renatio\DynamicPDF\Models\Template;

describe('Template', function () {
    it('survives a fetch without the code column', function () {
        $this->createTemplate(['code' => 'partial::pdf.select', 'is_custom' => false]);

        expect(Template::whereCode('partial::pdf.select')->value('is_custom'))->toBeFalsy();
    });
});
