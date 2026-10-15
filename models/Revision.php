<?php

namespace Renatio\DynamicPDF\Models;

use Backend\Models\Preference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use October\Rain\Database\Model;

/**
 * @property int $id
 * @property string $revisionable_type
 * @property int $revisionable_id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property array<string, mixed> $data
 * @property Carbon|null $created_at
 */
class Revision extends Model
{
    public const LIMIT = 20;

    public const EVENT = '_event';

    public const EVENT_RESET = 'reset';

    public const EVENT_RESTORE = 'restore';

    public const UPDATED_AT = null;

    public $table = 'renatio_dynamicpdf_revisions';

    /** @var list<string> */
    protected $fillable = ['user_id', 'user_name', 'data'];

    /** @var array<int, string> */
    protected $jsonable = ['data'];

    /** @var array<string, array<int|string, mixed>> */
    public $morphTo = [
        'revisionable' => [],
    ];

    /**
     * Backend::makeCarbon() throws a TypeError for an administrator whose preferences carry no timezone.
     */
    public function createdAtLocal(): ?Carbon
    {
        $timezone = Preference::get('timezone') ?: Config::get('backend.timezone', Config::get('app.timezone'));

        return $this->created_at?->copy()->setTimezone($timezone);
    }

    public function event(): ?string
    {
        $event = $this->data[self::EVENT] ?? null;

        return is_string($event) ? $event : null;
    }

    public function createdAtLabel(): string
    {
        return $this->createdAtLocal()?->format('Y-m-d H:i') ?? '';
    }
}
