<?php

namespace OiLab\OiLaravelSign\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * The write-once storage of the signature files.
 */
class SignatureFiles
{
    public static function disk(): Filesystem
    {
        return Storage::disk(OiLaravelSign::disk());
    }

    public static function path(string $uuid, string $file): string
    {
        return OiLaravelSign::directory().'/'.$uuid.'/'.$file;
    }

    public static function put(string $path, string $contents): string
    {
        $disk = static::disk();

        if ($disk->exists($path)) {
            throw SignatureException::fileExists($path);
        }

        $disk->put($path, $contents, ['visibility' => 'private']);

        return $path;
    }

    public static function get(string $path): ?string
    {
        return static::disk()->get($path);
    }

    public static function deleteDirectory(string $uuid): void
    {
        static::disk()->deleteDirectory(OiLaravelSign::directory().'/'.$uuid);
    }
}
