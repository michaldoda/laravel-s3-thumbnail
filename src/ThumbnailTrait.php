<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * @method string getS3ImagePath
 * @method string getImageAltDescription
 */
trait ThumbnailTrait
{
    private function getThumbnailHash(string $name): string
    {
        $config = $this->getThumbnailConfig();
        return md5(json_encode($config[$name]));
    }

    private function getThumbnailDirectory(string $name): string
    {
        $hash = $this->getThumbnailHash($name);
        $fileNameWithoutExtension = explode(".", $this->getFileName())[0];
        $publicDisk = config('s3-thumbnail.public_disk');
        $publicPath = config('s3-thumbnail.public_path');
        if (!Storage::disk($publicDisk)->exists("$publicPath/$fileNameWithoutExtension/$hash")) {
            Storage::disk($publicDisk)->makeDirectory("$publicPath/$fileNameWithoutExtension/$hash");
        }
        $storageAppPublic = storage_path('app/public');
        return "$storageAppPublic/$publicPath/$fileNameWithoutExtension/$hash";
    }

    private function isThumbnailAlreadyCreated(string $name): bool
    {
        $publicDisk = config('s3-thumbnail.public_disk');
        $publicPath = config('s3-thumbnail.public_path');
        $hash = $this->getThumbnailHash($name);
        $fileNameWithoutExtension = explode(".", $this->getFileName())[0];
        return Storage::disk($publicDisk)->exists("$publicPath/$fileNameWithoutExtension/$hash");
    }

    /**
     * @param string $name
     * @throws MissingThumbnailException
     * @throws ConfigException
     */
    public function generateThumbnail(string $name): void
    {
        if ($this->isThumbnailAlreadyCreated($name)) {
            return;
        }

        $config = $this->getThumbnailConfig();
        if (!Arr::has($config, $name)) {
            throw new MissingThumbnailException("$name");
        }

        $directoryToSave = $this->getThumbnailDirectory($name);
        if (empty($config[$name])) {
            throw new ConfigException("Thumbnail must have at least a default configuration.");
        }

        foreach ($config[$name] as $settings) {
            $img = \Intervention\Image\Facades\Image::make($this->getFileStream());

            if (data_get($settings, 'filters.resize')) {
                $resizeWidth = data_get($settings, 'filters.resize.width');
                if ($resizeWidth > $img->getWidth()) {
                    $resizeWidth = $img->getWidth();
                }
                $resizeHeight = data_get($settings, 'filters.resize.height', null);
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
                $x = data_get($settings, 'filters.crop.x', null);
                $y = data_get($settings, 'filters.crop.y', null);

                $img->crop($cropWidth,$cropHeight, $x, $y);
            }
            $fileNameToSave = $settings['max_width'] === 'default' ? 'default' : "w".$settings['max_width'];
            $img->save("$directoryToSave/$fileNameToSave.jpeg", $this->getThumbnailQuality($settings));
        }
    }

    private function getThumbnailQuality($settings)
    {
        return data_get($settings, "quality", config('s3-thumbnail.default_quality'));
    }

    public function getThumbnailDefaultPath($name): string
    {
        return $this->getThumbnailPath($name, 'default');
    }

    private function getThumbnailPath(string $name, $option = 'default'): string
    {
        $hash = $this->getThumbnailHash($name);
        $fileNameWithoutExtension = explode(".", $this->getFileName())[0];
        if ($option !== 'default') {
            $option = "w$option";
        }
        $publicPath = config('s3-thumbnail.public_path');
        return "/storage/$publicPath/$fileNameWithoutExtension/$hash/$option.jpeg";
    }

    /**
     * @param string $name
     * @param array $classNames
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws ConfigException
     * @throws MissingThumbnailException
     */
    public function getThumbnailHtml(string $name, array $classNames = [])
    {
        $this->generateThumbnail($name);
        $config = $this->getThumbnailConfig();
        if (count($config[$name]) === 1) {
            return view('s3-thumbnail::default', [
                'alt' => $this->getImageAltDescription(),
                'path' => $this->getThumbnailPath($name, 'default'),
                'classNames' => $classNames,
            ]);
        } else {
            $queries = [];
            foreach (Arr::pluck($config[$name], 'max_width') as $maxWidth) {
                if ($maxWidth === 'default') {
                    continue;
                }
                $queries[] = [
                    'width' => (string)$maxWidth,
                    'path' => $this->getThumbnailPath($name, (string)$maxWidth),
                ];
            }
            return view('s3-thumbnail::queries', [
                'queries' => $queries,
                'alt' => $this->getImageAltDescription(),
                'image' => $this,
                'defaultPath' => $this->getThumbnailPath($name, 'default'),
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

    public function getFileStream(): string
    {
        $this->saveLocally();
        $originalsPath = config('s3-thumbnail.originals_path');
        return Storage::disk(config('s3-thumbnail.local_disk'))->get($originalsPath."/".$this->getS3ImagePath());
    }

    public function saveLocally(): void
    {
        $s3ImagePath = $this->getS3ImagePath();
        $originalsPath = config('s3-thumbnail.originals_path');
        if (!Storage::disk(config('s3-thumbnail.local_disk'))->exists("$originalsPath/".$s3ImagePath)) {
            Storage::disk(config('s3-thumbnail.local_disk'))->put("$originalsPath/".$s3ImagePath, Storage::disk(config('s3-thumbnail.s3_disk'))->get($s3ImagePath));
        }
    }

    public function getThumbnailConfig(): array
    {
        return config('s3-thumbnail.thumbnails');
    }
}
