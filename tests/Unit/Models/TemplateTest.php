<?php

use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Models\Template;

describe('Template', function () {
    it('survives a fetch without the code column', function () {
        $this->createTemplate(['code' => 'partial::pdf.select', 'is_custom' => false]);

        expect(Template::whereCode('partial::pdf.select')->value('is_custom'))->toBeFalsy();
    });

    it('rejects a title or code longer than its column', function (string $field) {
        expect(fn () => $this->createTemplate([$field => str_repeat('a', 256)]))
            ->toThrow(ValidationException::class, 'greater than 255');
    })->with(['title', 'code']);
});
