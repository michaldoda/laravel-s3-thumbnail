<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Exception;
use Throwable;

class ConfigException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct("s3-thumbnail: $message.", $code, $previous);
    }
}