<?php

namespace Renatio\DynamicPDF\Classes;

use Backend\Classes\FormField;
use Illuminate\Support\Str;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Revision;
use Renatio\DynamicPDF\Models\Template;

class RevisionHistory
{
    protected const LONG_FIELDS = ['description', 'content_html', 'content_css', 'sample_data'];

    protected const VALUE_LIMIT = 60;

    /** @var array<int|string, string> */
    protected array $layoutNames = [];

    /** @var list<string>|null */
    protected ?array $versionedFields = null;

    /**
     * @param  array<string, FormField>  $fields
     */
    public function __construct(protected Layout|Template $record, protected array $fields)
    {
    }

    /**
     * Changes are null for the oldest revision, which has nothing older to compare with.
     *
     * @return list<array{revision: Revision, changes: list<array{label: string, old: ?string, new: ?string}>|null}>
     */
    public function rows(): array
    {
        $revisions = $this->record->revisions()->getQuery()->orderByDesc('id')->get()->whereInstanceOf(Revision::class)->values()->all();

        $this->loadLayoutNames($revisions);

        $rows = [];

        foreach ($revisions as $index => $revision) {
            $older = $revisions[$index + 1] ?? null;

            $rows[] = [
                'revision' => $revision,
                'changes' => $older ? $this->changes((array) $older->data, (array) $revision->data) : null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, old: string, new: string}>|null
     */
    public function changesOf(Revision $revision): ?array
    {
        $older = $this->record->revisions()->getQuery()->where('id', '<', $revision->id)->orderByDesc('id')->first();

        if (! $older instanceof Revision) {
            return null;
        }

        $this->loadLayoutNames([$older, $revision]);

        /** @var list<array{label: string, old: string, new: string}> */
        return $this->changes((array) $older->data, (array) $revision->data, true);
    }

    /**
     * @param  array<int, Revision>  $revisions
     */
    protected function loadLayoutNames(array $revisions): void
    {
        if ($this->record instanceof Template) {
            $this->layoutNames = Layout::query()
                ->whereIn('id', array_filter(array_unique(array_map(fn (Revision $revision) => $revision->data['layout_id'] ?? null, $revisions))))
                ->pluck('name', 'id')
                ->all();
        }
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<array{label: string, old: ?string, new: ?string}>
     */
    protected function changes(array $old, array $new, bool $full = false): array
    {
        $changes = [];

        foreach (array_keys(array_intersect_key($old, $new)) as $field) {
            if (! in_array($field, $this->fields(), true)) {
                continue;
            }

            $before = $old[$field];
            $after = $new[$field];

            if ($this->record::normalizeViewValue($before) === $this->record::normalizeViewValue($after)) {
                continue;
            }

            $long = in_array($field, self::LONG_FIELDS, true);

            $changes[] = [
                'label' => $this->label($field),
                'old' => $this->value($field, $before, $long, $full),
                'new' => $this->value($field, $after, $long, $full),
            ];
        }

        return $changes;
    }

    /**
     * @return list<string>
     */
    protected function fields(): array
    {
        return $this->versionedFields ??= array_keys($this->record->revisionSnapshot());
    }

    protected function label(string $field): string
    {
        $formField = $this->formField($field);
        $label = $formField?->get('label') ?: $formField?->get('tab');

        return $label ? (string) trans($label) : Str::ucfirst(str_replace('_', ' ', $field));
    }

    protected function value(string $field, mixed $value, bool $long, bool $full): ?string
    {
        if (! $long) {
            $display = $this->display($field, $value);

            return $full ? $display : Str::limit($display, self::VALUE_LIMIT);
        }

        if (! $full) {
            return null;
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function display(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'layout_id') {
            $value = $this->layoutNames[$value] ?? '#' . $value;
        } elseif (is_scalar($value)) {
            $value = $this->optionLabel($field, (string) $value) ?? $value;
        }

        return is_scalar($value) ? (string) $value : (string) json_encode($value);
    }

    protected function optionLabel(string $field, string $value): ?string
    {
        $formField = $this->formField($field);

        if ($formField?->get('type') !== 'dropdown') {
            return null;
        }

        $option = $formField->getOptionsFromModel($this->record, $formField->get('options'), null)[$value] ?? null;

        return $option ? (string) trans((string) $option) : null;
    }

    protected function formField(string $field): ?FormField
    {
        return $this->fields[$field === 'layout_id' ? 'layout' : $field] ?? null;
    }
}
