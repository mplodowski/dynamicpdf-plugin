<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Log;
use October\Rain\Support\Traits\Singleton;
use Throwable;

class FailureLog
{
    use Singleton;

    /** @var array<string, true> */
    protected array $logged = [];

    /**
     * Every backend list display and every fetch of the record hit the same broken view again.
     */
    public function logOnce(string $model, string $code, string $message, Throwable $e): void
    {
        $key = "{$model}|{$code}|{$message}";

        if (isset($this->logged[$key])) {
            return;
        }

        $this->logged[$key] = true;

        Log::error($message, ['exception' => $e]);
    }
}
