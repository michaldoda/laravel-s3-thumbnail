<?php

namespace MichalDoda\LaravelS3Thumbnail;

interface ThumbnailInterface
{
    public function getS3ImagePath(): string;
    public function getImageAltDescription(): ?string;
}