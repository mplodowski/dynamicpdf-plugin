<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class SyncTemplates
{
    /** @var array<string, array<int, string>> */
    protected array $report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

    public function handle(bool $prune = false): void
    {
        $this->report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

        $this->sync(Layout::class, PDFManager::instance()->listRegisteredLayouts());

        if ($prune) {
            $this->deleteOrphanedTemplates();
        }

        $this->sync(Template::class, PDFManager::instance()->listRegisteredTemplates());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function report(): array
    {
        return $this->report;
    }

    /**
     * @return array<int, string>
     */
    public function orphanedTemplates(): array
    {
        return Template::followingView()
            ->whereNotIn('code', array_keys(PDFManager::instance()->listRegisteredTemplates()))
            ->orderBy('code')
            ->pluck('code')
            ->all();
    }

    protected function deleteOrphanedTemplates(): void
    {
        foreach (Template::whereIn('code', $this->orphanedTemplates())->get() as $template) {
            $this->write(Template::class, (string) $template->code, 'deleted', fn (): ?bool => $template->delete());
        }
    }

    /**
     * @param  class-string<Layout|Template>  $model
     * @param  array<string, string>  $views
     */
    protected function sync(string $model, array $views): void
    {
        if ($views === []) {
            return;
        }

        $this->refresh($model, $views);
        $this->create($model, $views);
    }

    /**
     * A view file read mid-deploy can parse to no content or a layout that does not resolve
     * yet; neither may wipe the stored row.
     *
     * @param  class-string<Layout|Template>  $model
     * @param  array<string, string>  $views
     */
    protected function refresh(string $model, array $views): void
    {
        /** @var Collection<int, Layout|Template> $records */
        $records = $model::followingView()
            ->whereIn('code', array_keys($views))
            ->get();

        foreach ($records as $record) {
            if ($record->viewReadFailed()) {
                $this->report['failed'][] = $record->code;

                continue;
            }

            $this->write($model, $record->code, 'updated', fn (): bool => $record->inDefaultLocale(function () use ($record): bool {
                if (! $record->content_html || ! $record->isDirty($record::VIEW_FIELDS)) {
                    return false;
                }

                if ($record instanceof Template && $record->layout_id === null && $record->getOriginal('layout_id') !== null) {
                    return false;
                }

                $record->forceSave();

                return true;
            }));
        }
    }

    /**
     * @param  class-string<Layout|Template>  $model
     * @param  array<string, string>  $views
     */
    protected function create(string $model, array $views): void
    {
        $stored = $model::query()->pluck('code', 'code')->all();

        foreach (array_diff_key($views, $stored) as $code) {
            $this->write($model, $code, 'created', function () use ($model, $code): void {
                $record = new $model;
                $record->markAsFollowingView();
                $record->fillFromView($code);
                $record->forceSave();
            });
        }
    }

    /**
     * @param  class-string  $model
     */
    protected function write(string $model, string $code, string $outcome, callable $write): void
    {
        try {
            if ($write() !== false) {
                $this->report[$outcome][] = $code;
            }
        } catch (UniqueConstraintViolationException) {
        } catch (Throwable $e) {
            $this->report['failed'][] = $code;

            FailureLog::instance()->logOnce($model, $code, "Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}", $e);
        }
    }
}
