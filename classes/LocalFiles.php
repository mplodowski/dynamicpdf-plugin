<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\Local\LocalFilesystemAdapter;
use October\Rain\Database\Attach\File;

class LocalFiles
{
    /** @var array<int, string> */
    protected array $copies = [];

    /**
     * A file on a remote disk is copied inside the default dompdf chroot, so it renders
     * without enabling remote assets.
     */
    public function path(File $file): string
    {
        $disk = $this->disk($file);

        if ($disk?->getAdapter() instanceof LocalFilesystemAdapter) {
            return $disk->path($file->getDiskPath());
        }

        $stream = $disk?->readStream($file->getDiskPath());

        if (! is_resource($stream)) {
            Log::warning("Renatio.DynamicPDF could not read {$file->getDiskPath()} for the PDF.");

            return '';
        }

        $directory = $this->directory();
        $copy = $directory . '/' . Str::random(40) . '.' . $file->getExtension();
        $this->copies[] = $copy;

        try {
            Files::ensureDirectoryExists($directory);
            file_put_contents($copy, $stream);
        } finally {
            fclose($stream);
        }

        return $copy;
    }

    public function delete(): void
    {
        if ($this->copies === []) {
            return;
        }

        Files::delete($this->copies);
        $this->copies = [];

        /** rmdir() refuses a directory another render is still writing to. */
        @rmdir($this->directory());
    }

    public function directory(): string
    {
        return storage_path('temp/dynamicpdf');
    }

    /**
     * File::getDisk() documents an unqualified FilesystemAdapter that resolves to no class.
     */
    protected function disk(File $file): ?FilesystemAdapter
    {
        $disk = $file->getDisk();

        return $disk instanceof FilesystemAdapter ? $disk : null;
    }
}
