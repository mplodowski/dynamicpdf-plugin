<?php

use Backend\Facades\Backend;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Models\Revision;
use Renatio\DynamicPDF\Tests\TestCase;

/**
 * @return TestResponse<Response>
 */
function revisionChanges(TestCase $test, int $templateId, mixed $revision): TestResponse
{
    return $test->post(Backend::url('renatio/dynamicpdf/templates/update/' . $templateId), ['revision' => $revision], [
        'X-AJAX-HANDLER' => 'onRevisionChanges',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);
}

describe('Revision changes', function () {
    it('shows a line diff of the long fields and the old and new value of the short ones, escaped', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['title' => 'New title', 'content_html' => "<h1>Invoice</h1>\n<p>Total: {{ total }}</p>\n<footer>Thanks</footer>"]);
        $template->recordRevision(['title' => 'Old title', 'content_html' => "<h1>Invoice</h1>\n<p>Sum</p>\n<footer>Thanks</footer>"] + $template->revisionSnapshot());

        $html = (string) revisionChanges($this, $template->id, Revision::query()->orderByDesc('id')->value('id'))->assertOk()->json('result');

        expect($html)->toContain('Changes from')
            ->and($html)->toContain('Permission Tests')
            ->and($html)->toMatch('~diff-removed.*?&lt;p&gt;Sum&lt;/p&gt;~s')
            ->and($html)->toMatch('~diff-added.*?&lt;p&gt;Total: {{ total }}&lt;/p&gt;~s')
            ->and($html)->toContain('&lt;footer&gt;Thanks&lt;/footer&gt;')
            ->and($html)->not->toContain('<p>Sum</p>')
            ->and($html)->toMatch('~diff-removed.*?Old title.*?diff-added.*?New title~s');
    });

    it('explains that the oldest version has nothing to compare with', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['content_html' => '<p>New</p>']);
        $template->recordRevision(['content_html' => '<p>Old</p>'] + $template->revisionSnapshot());

        $html = (string) revisionChanges($this, $template->id, Revision::query()->orderBy('id')->value('id'))->assertOk()->json('result');

        expect($html)->toContain('there is nothing older to compare it with')
            ->and($html)->not->toContain('diff-removed');
    });

    it('refuses a version of another record', function () {
        actingAsPdfManager();
        $other = $this->createTemplate(['code' => 'acme::pdf.other', 'content_html' => '<p>New</p>']);
        $other->recordRevision(['content_html' => '<p>Secret</p>'] + $other->revisionSnapshot());
        $template = $this->createTemplate();

        $response = revisionChanges($this, $template->id, Revision::query()->orderByDesc('id')->value('id'));

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and((string) $response->getContent())->toContain('This version no longer exists.')
            ->and((string) $response->getContent())->not->toContain('Secret');
    });

    it('refuses an administrator without access to templates', function () {
        actingAsPdfUserWith(['manage_layouts']);
        $template = $this->createTemplate(['content_html' => '<p>New</p>']);
        $template->recordRevision(['content_html' => '<p>Secret</p>'] + $template->revisionSnapshot());

        $this->withoutExceptionHandling();

        expect(fn () => revisionChanges($this, $template->id, Revision::query()->orderByDesc('id')->value('id')))->toThrow(ForbiddenException::class);
    });
});
