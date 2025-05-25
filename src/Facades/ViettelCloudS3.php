<?php

namespace Kaibatech\ViettelCloudS3\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

/**
 * @method static bool put(string $path, string $contents, array $options = [])
 * @method static string get(string $path)
 * @method static bool exists(string $path)
 * @method static bool delete(string $path)
 * @method static string url(string $path)
 * @method static int size(string $path)
 * @method static int lastModified(string $path)
 * @method static string mimeType(string $path)
 * @method static mixed putFile(string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file, array $options = [])
 * @method static mixed putFileAs(string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file, string $name, array $options = [])
 * @method static bool putStream(string $path, resource $resource, array $options = [])
 * @method static resource readStream(string $path)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 *
 * @see \Illuminate\Filesystem\FilesystemAdapter
 */
class ViettelCloudS3 extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Storage::disk('viettel-s3');
    }
} 