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
    /** @var array<string, mixed>|null */
    protected ?array $snapshotBeforeAttachment = null;

    protected bool $savingWithRevisions = false;

    /**
     * Attachments uploaded with attachOnUpload are saved before the form is, so their versions are recorded right
     * away; deferred ones are committed inside the save and belong to its version.
     */
    public function initializeHasRevisions(): void
    {
        if (static::REVISION_ATTACHMENTS === []) {
            return;
        }

        $this->bindEvent('model.relation.beforeAdd', fn (string $relation) => $this->rememberSnapshotBeforeAttachment($relation));
        $this->bindEvent('model.relation.beforeRemove', fn (string $relation) => $this->rememberSnapshotBeforeAttachment($relation));
        $this->bindEvent('model.relation.add', fn (string $relation) => $this->recordAttachmentRevision($relation));
        $this->bindEvent('model.relation.remove', fn (string $relation) => $this->recordAttachmentRevision($relation));
    }

    /**
     * @return array<string, mixed>
     */
    public function revisionSnapshot(): array
    {
        $snapshot = [];

        foreach (static::REVISION_FIELDS as $field) {
            $snapshot[$field] = $this->getTranslatableBaseValue($field);
        }

        return $snapshot + $this->revisionAttachments();
    }

    /**
     * File names of the versioned attachments; listed in the history but never restored, the files are gone.
     *
     * @return array<string, string|null>
     */
    protected function revisionAttachments(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>|null  $options
     */
    protected function saveInternal($options = [])
    {
        $this->savingWithRevisions = true;

        try {
            return parent::saveInternal($options);
        } finally {
            $this->savingWithRevisions = false;
        }
    }

    protected function rememberSnapshotBeforeAttachment(string $relation): void
    {
        if ($this->exists && ! $this->savingWithRevisions && in_array($relation, static::REVISION_ATTACHMENTS, true)) {
            $this->snapshotBeforeAttachment = $this->revisionSnapshot();
        }
    }

    protected function recordAttachmentRevision(string $relation): void
    {
        if ($this->snapshotBeforeAttachment !== null && in_array($relation, static::REVISION_ATTACHMENTS, true)) {
            $this->recordRevision($this->snapshotBeforeAttachment);
            $this->snapshotBeforeAttachment = null;
        }
    }

    /**
     * A failure is logged and swallowed, so it never fails the save that has already happened.
     *
     * @param  array<string, mixed>  $previous
     */
    public function recordRevision(array $previous, ?DateTimeInterface $previousAt = null, ?string $event = null): void
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
            $data = $event === null ? $current : $current + [Revision::EVENT => $event];

            $this->createRevision($data, $user instanceof User ? $user : null, now());
            $this->pruneRevisions();
        } catch (Throwable $e) {
            Log::warning("Renatio.DynamicPDF could not record a revision of {$this->code}: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $current
     */
    public function restoresChanges(Revision $revision, ?array $current = null): bool
    {
        $current ??= $this->revisionSnapshot();

        return ! self::sameSnapshot($current, $this->restorableRevisionData($revision) + $current);
    }

    public function restoreRevision(Revision $revision): bool
    {
        $previous = $this->revisionSnapshot();

        if (! $this->restoresChanges($revision, $previous)) {
            return false;
        }

        $data = $this->restorableRevisionData($revision);

        $this->inDefaultLocale(function () use ($data): void {
            foreach ($data as $field => $value) {
                $this->setAttribute($field, $value);
            }

            $this->markAsCustomised();
            $this->save();
        });

        $this->recordRevision($previous, null, Revision::EVENT_RESTORE);

        return true;
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
