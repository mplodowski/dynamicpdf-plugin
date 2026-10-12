<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Models\Revision;
use Throwable;

/**
 * Each revision holds the content saved at its time by its author; the first one of a record is preceded by an
 * anonymous baseline with the content from before. Snapshots hold the default-locale values only.
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
     * A failure is logged and swallowed, so it never fails the save that has already happened.
     *
     * @param  array<string, mixed>  $previous
     */
    public function recordRevision(array $previous, ?DateTimeInterface $previousAt = null): void
    {
        $current = $this->revisionSnapshot();

        if (self::sameSnapshot($previous, $current)) {
            return;
        }

        try {
            if (! $this->revisions()->getQuery()->exists()) {
                $this->createRevision($previous, null, $previousAt ?? now());
            }

            $user = BackendAuth::getUser();

            $this->createRevision($current, $user instanceof User ? $user : null, now());
            $this->pruneRevisions();
        } catch (Throwable $e) {
            Log::warning("Renatio.DynamicPDF could not record a revision of {$this->code}: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    public function restoreRevision(Revision $revision): void
    {
        $previous = $this->revisionSnapshot();
        $data = $this->restorableRevisionData($revision);

        if (self::sameSnapshot($previous, $data + $previous)) {
            return;
        }

        $this->inDefaultLocale(function () use ($data): void {
            foreach ($data as $field => $value) {
                $this->setAttribute($field, $value);
            }

            $this->markAsCustomised();
            $this->save();
        });

        $this->recordRevision($previous);
    }

    public function currentRevisionId(): ?int
    {
        $latest = $this->revisions()->getQuery()->orderByDesc('id')->first();

        if (! $latest instanceof Revision) {
            return null;
        }

        $current = $this->revisionSnapshot();

        return self::sameSnapshot($current, $this->revisionFieldsOf($latest) + $current) ? (int) $latest->id : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function restorableRevisionData(Revision $revision): array
    {
        return $this->revisionFieldsOf($revision);
    }

    /**
     * @return array<string, mixed>
     */
    protected function revisionFieldsOf(Revision $revision): array
    {
        return array_intersect_key((array) $revision->data, array_flip(static::REVISION_FIELDS));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createRevision(array $data, ?User $user, DateTimeInterface $at): void
    {
        $revision = $this->revisions()->make([
            'user_id' => $user?->id,
            'user_name' => $user ? (trim($user->full_name) ?: (string) $user->getLogin()) : null,
            'data' => $data,
        ]);

        $revision->setAttribute('created_at', $at);
        $revision->save();
    }

    protected function pruneRevisions(): void
    {
        if ($this->revisions()->getQuery()->count() <= Revision::LIMIT) {
            return;
        }

        $kept = $this->revisions()->getQuery()->orderByDesc('id')->limit(Revision::LIMIT)->pluck('id');
        $this->revisions()->getQuery()->whereKeyNot($kept)->delete();
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  array<string, mixed>  $second
     */
    protected static function sameSnapshot(array $first, array $second): bool
    {
        $normalize = function (array $snapshot): array {
            $snapshot = array_map(static::normalizeViewValue(...), $snapshot);
            ksort($snapshot);

            return $snapshot;
        };

        return $normalize($first) === $normalize($second);
    }
}
