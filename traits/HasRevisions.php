<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Renatio\DynamicPDF\Models\Revision;

/**
 * Snapshots hold the default-locale values; translations are not versioned.
 *
 * @method \October\Rain\Database\Relations\MorphMany revisions()
 *
 * @mixin \October\Rain\Database\Model
 */
trait HasRevisions
{
    /**
     * @return array<string, mixed>
     */
    public function revisionSnapshot(): array
    {
        $snapshot = [];

        foreach (static::REVISION_FIELDS as $field) {
            $snapshot[$field] = $this->getTranslatableBaseValue($field);
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    public function recordRevision(array $previous): void
    {
        if (self::normalizeSnapshot($previous) === self::normalizeSnapshot($this->revisionSnapshot())) {
            return;
        }

        $user = BackendAuth::getUser();
        $user = $user instanceof User ? $user : null;

        $this->revisions()->create([
            'user_id' => $user?->id,
            'user_name' => $user ? (trim($user->full_name) ?: (string) $user->getLogin()) : null,
            'data' => $previous,
        ]);

        $kept = $this->revisions()->getQuery()->orderByDesc('id')->limit(Revision::LIMIT)->pluck('id');
        $this->revisions()->getQuery()->whereKeyNot($kept)->delete();
    }

    public function restoreRevision(Revision $revision): void
    {
        $previous = $this->revisionSnapshot();

        $this->inDefaultLocale(function () use ($revision): void {
            foreach ($this->restorableRevisionData($revision) as $field => $value) {
                $this->setAttribute($field, $value);
            }

            $this->markAsCustomised();
            $this->save();
        });

        $this->recordRevision($previous);
    }

    /**
     * @return array<string, mixed>
     */
    protected function restorableRevisionData(Revision $revision): array
    {
        return array_intersect_key((array) $revision->data, array_flip(static::REVISION_FIELDS));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string>
     */
    protected static function normalizeSnapshot(array $snapshot): array
    {
        return array_map(fn (mixed $value): string => str_replace("\r\n", "\n", trim((string) $value)), $snapshot);
    }
}
