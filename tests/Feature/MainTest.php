<?php

namespace MichalDoda\LaravelS3Thumbnail\Tests\Feature;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MichalDoda\LaravelS3Thumbnail\ConfigException;
use MichalDoda\LaravelS3Thumbnail\MissingThumbnailException;
use MichalDoda\LaravelS3Thumbnail\Tests\FakeImage;
use MichalDoda\LaravelS3Thumbnail\Tests\TestCase;
use MichalDoda\LaravelS3Thumbnail\Thumbnail;

class MainTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Thumbnail::getLocalDisk()->deleteDirectory(Thumbnail::getOriginalPath());
        Thumbnail::getPublicDisk()->deleteDirectory(Thumbnail::getPublicPath());
    }

    /**
     * @throws MissingThumbnailException|FileNotFoundException|ConfigException
     */
    public function test_thumbnail_generation()
    {
        $fakeImage = FakeImage::generate(env('AWS_S3_FILEPATH'), 'Awesome alt description');
        $this->assertFalse(Thumbnail::getLocalDisk()->exists(Thumbnail::getOriginalPath()."/".$fakeImage->getS3ImagePath()));
        $fakeImage->getThumbnailHtml('article_main');
        $this->assertTrue(Thumbnail::getLocalDisk()->exists(Thumbnail::getOriginalPath()."/".$fakeImage->getS3ImagePath()));
    }

    /**
     * @throws MissingThumbnailException|FileNotFoundException|ConfigException
     */
    public function test_no_thumbnail()
    {
        $fakeImage = FakeImage::generate(env('AWS_S3_FILEPATH'), 'Awesome alt description');
        $this->expectException(MissingThumbnailException::class);
        $fakeImage->getThumbnailHtml('no_thumbnail');
    }
}