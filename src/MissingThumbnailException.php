<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Exception;
use Throwable;

class MissingThumbnailException extends Exception
{
    public function __construct($thumbnailName = "none", $code = 0, Throwable $previous = null) {
        parent::__construct("s3-thumbnail: $thumbnailName, does not exists.", $code, $previous);
    }
}