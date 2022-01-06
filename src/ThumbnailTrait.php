<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Intervention\Image\Facades\Image;

/**
 * @method string getS3ImagePath
 * @method string getImageAltDescription
 */
trait ThumbnailTrait
{
    private function getThumbnailDirectory(string $thumbnailName): string
    {
        $hash = Thumbnail::getThumbnailHash($thumbnailName);
        $fileNameWithoutExtension = Thumbnail::getFileNameWithoutExtension($this->getFileName());
        $publicPath = Thumbnail::getPublicPath();
        $storageAppPublic = storage_path('app/public');

        if (!Thumbnail::getPublicDisk()->exists("$publicPath/$fileNameWithoutExtension/$hash")) {
            Thumbnail::getPublicDisk()->makeDirectory("$publicPath/$fileNameWithoutExtension/$hash");
        }

        return "$storageAppPublic/$publicPath/$fileNameWithoutExtension/$hash";
    }

    private function isThumbnailAlreadyCreated(string $thumbnailName): bool
    {
        $publicPath = Thumbnail::getPublicPath();
        $fileNameWithoutExtension = Thumbnail::getFileNameWithoutExtension($this->getFileName());
        $hash = Thumbnail::getThumbnailHash($thumbnailName);
        return Thumbnail::getPublicDisk()->exists("$publicPath/$fileNameWithoutExtension/$hash");
    }

    /**
     * @param string $thumbnailName
     * @throws MissingThumbnailException|ConfigException|FileNotFoundException
     */
    public function generateThumbnail(string $thumbnailName): void
    {
        if ($this->isThumbnailAlreadyCreated($thumbnailName)) {
            return;
        }

        $config = Thumbnail::getThumbnailsConfig($thumbnailName);
        if ($config === null) {
            throw new MissingThumbnailException("$thumbnailName");
        }

        $directoryToSave = $this->getThumbnailDirectory($thumbnailName);
        if (empty($config)) {
            throw new ConfigException("Thumbnail must have at least the default configuration.");
        }

        foreach ($config as $settings) {
            $img = Image::make($this->getFileStream());

            if (data_get($settings, 'filters.resize')) {
                $resizeWidth = data_get($settings, 'filters.resize.width');
                if ($resizeWidth > $img->getWidth()) {
                    $resizeWidth = $img->getWidth();
                }
                $resizeHeight = data_get($settings, 'filters.resize.height');
                if ($resizeHeight) {
                    $img->resize($resizeWidth, $resizeHeight);
                } else {
                    $img->resize($resizeWidth, $resizeHeight, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
            }

            if (data_get($settings, 'filters.crop')) {
                $cropHeight = data_get($settings, 'filters.crop.height');
                if ($img->getHeight() < $cropHeight) {
                    $cropHeight = $img->getHeight();
                }
                $cropWidth = data_get($settings, 'filters.crop.width');
                if ($img->getWidth() < $cropWidth) {
                    $cropWidth = $img->getWidth();
                }
                $x = data_get($settings, 'filters.crop.x');
                $y = data_get($settings, 'filters.crop.y');

                $img->crop($cropWidth,$cropHeight, $x, $y);
            }
            $fileNameToSave = $settings['max_width'] === 'default' ? 'default' : "w".$settings['max_width'];
            $img->save("$directoryToSave/$fileNameToSave.jpeg", data_get($settings, "quality", Thumbnail::getDefaultQuality()));
        }
    }

    private function getThumbnailPath(string $thumbnailName, $option = 'default'): string
    {
        $hash = Thumbnail::getThumbnailHash($thumbnailName);
        $fileNameWithoutExtension = Thumbnail::getFileNameWithoutExtension($this->getFileName());
        if ($option !== 'default') {
            $option = "w$option";
        }
        $publicPath = Thumbnail::getPublicPath();
        return "/storage/$publicPath/$fileNameWithoutExtension/$hash/$option.jpeg";
    }

    /**
     * @param string $thumbnailName
     * @param array $classNames
     * @return Application|Factory|View
     * @throws ConfigException|MissingThumbnailException|FileNotFoundException
     */
    public function getThumbnailHtml(string $thumbnailName, array $classNames = [])
    {
        $this->generateThumbnail($thumbnailName);
        $config = Thumbnail::getThumbnailsConfig($thumbnailName);
        if (count($config) === 1) {
            return view('s3-thumbnail::default', [
                'alt' => $this->getImageAltDescription(),
                'path' => $this->getThumbnailPath($thumbnailName),
                'classNames' => $classNames,
            ]);
        } else {
            $queries = [];
            foreach (Arr::pluck($config, 'max_width') as $maxWidth) {
                if ($maxWidth === 'default') {
                    continue;
                }
                $queries[] = [
                    'width' => (string)$maxWidth,
                    'path' => $this->getThumbnailPath($thumbnailName, (string)$maxWidth),
                ];
            }
            return view('s3-thumbnail::queries', [
                'queries' => $queries,
                'alt' => $this->getImageAltDescription(),
                'image' => $this,
                'defaultPath' => $this->getThumbnailPath($thumbnailName),
                'classNames' => $classNames,
            ]);
        }
    }

    public function getFileName(): string
    {
        return basename($this->getS3ImagePath());
    }

    /**
     * @return array|string|string[]
     */
    public function getFileExtension()
    {
        return pathinfo($this->getS3ImagePath(), PATHINFO_EXTENSION);
    }

    /**
     * @throws FileNotFoundException
     */
    public function getFileStream(): string
    {
        Thumbnail::saveToLocalDisk($this->getS3ImagePath());
        return Thumbnail::getLocalDisk()->get(Thumbnail::getOriginalPath()."/".$this->getS3ImagePath());
    }
}
