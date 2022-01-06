<?php

namespace MichalDoda\LaravelS3Thumbnail\Tests;

use MichalDoda\LaravelS3Thumbnail\ThumbnailInterface;
use MichalDoda\LaravelS3Thumbnail\ThumbnailTrait;

class FakeImage implements ThumbnailInterface
{
    use ThumbnailTrait;

    private string $s3Path;
    private string $altDescription;

    public function __construct(string $s3Path, string $altDescription = "")
    {
        $this->s3Path = $s3Path;
        $this->altDescription = $altDescription;
    }

    public function getS3ImagePath(): string
    {
        return $this->s3Path;
    }

    public function getImageAltDescription(): string
    {
        return $this->altDescription;
    }

    public static function generate(string $s3Path, string $altDescription = ""): self
    {
        return new self($s3Path, $altDescription);
    }
}