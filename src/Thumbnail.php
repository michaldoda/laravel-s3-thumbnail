<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class Thumbnail
{
    public static function getThumbnailsConfig(string $name = null): ?array
    {
        if (!$name) {
            return config("s3-thumbnail.thumbnails", []);
        }
        return data_get(config("s3-thumbnail.thumbnails"), $name);
    }

    public static function getLocalDisk(): Filesystem
    {
        return Storage::disk(config('s3-thumbnail.local_disk'));
    }

    public static function getS3Disk(): Filesystem
    {
        return Storage::disk(config('s3-thumbnail.s3_disk'));
    }

    public static function getPublicDisk(): Filesystem
    {
        return Storage::disk(config('s3-thumbnail.public_disk'));
    }

    public static function getOriginalPath(): string
    {
        return config('s3-thumbnail.originals_path');
    }

    public static function getPublicPath(): string
    {
        return config('s3-thumbnail.public_path');
    }

    public static function getThumbnailHash(string $name): string
    {
        $thumbnailConfig = Thumbnail::getThumbnailsConfig($name);
        return md5(json_encode($thumbnailConfig));
    }

    public static function getFileNameWithoutExtension(string $filename): string
    {
        return explode(".", $filename)[0];
    }

    /**
     * @param $s3ImagePath
     * @return void
     * @throws FileNotFoundException
     */
    public static function saveToLocalDisk($s3ImagePath): void
    {
        if (!Thumbnail::getLocalDisk()->exists(Thumbnail::getOriginalPath()."/".$s3ImagePath)) {
            Thumbnail::getLocalDisk()->put(Thumbnail::getOriginalPath()."/".$s3ImagePath, Thumbnail::getS3Disk()->get($s3ImagePath));
        }
    }

    public static function getDefaultQuality()
    {
        return config('s3-thumbnail.default_quality');
    }
}