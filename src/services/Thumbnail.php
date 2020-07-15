<?php
namespace paw\services;

use Yii;
use yii\base\Component;
// use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\imagine\Image as Imagine;
use Imagine\Image\ManipulatorInterface;
use paw\helpers\StringHelper;

class Thumbnail extends Component
{
    public $defaultWidth = 300;
    public $defaultHeight = null;
    public $defaultQuality = 60;
    public $thumbnailsPath = '@webroot/assets/thumbnails';
    public $thumbnailsBaseUrl = '@web/assets/thumbnails';
    public $cache = 'cache';
    public $cachingDuration = 0;
    public $enableCaching = false;

    protected $_defaultOptions = [];

    public function init()
    {
        parent::init();
        if ($this->enableCaching) {
            $this->cache = Instance::ensure($this->cache, 'yii\caching\CacheInterface');
        }
    }

    public function setDefaultOptions($defaulOptions)
    {
        $this->_defaultOptions = $defaulOptions;
    }

    public function getDefaultOptions()
    {
        $buildInOptions = [
            'width' => 300,
            'height' => null,
            'quality' => 60,
            'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND,
        ];
        return ArrayHelper::merge($buildInOptions, $this->_defaultOptions);
    }

    public function origet(
        $url,
        $width = null,
        $height = null,
        $quality = null,
        $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND
    ) {
        if ($url == null) {
            return null;
        }
        if (Url::isRelative($url)) {
            $host = Yii::$app->request->hostInfo;
            $url = Yii::getAlias("{$host}{$url}");
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidParamException(Yii::t('app', '$url expects a valid URL'));
        }
        $width = $width ?: $this->defaultWidth;
        $this->defaultHeight = $this->defaultHeight ?: $width;
        $height = $height ?: $this->defaultHeight;
        $quality = $quality ?: $this->defaultQuality;
        $thumbnailUrl = null;
        if ($this->enableCaching) {
            $key = [$url, $width, $height, $quality];
            $thumbnailUrl = $this->cache->get($key);
            if (!$thumbnailUrl) {
                $thumbnailUrl = $this->generateThumbnail($url, $width, $height, $quality, $mode);
                if ($thumbnailUrl) {
                    $this->cache->set($key, $thumbnailUrl, $this->cachingDuration);
                } else {
                }
            }
        } else {
            $thumbnailUrl = $this->generateThumbnail($url, $width, $height, $quality, $mode);
        }
        if ($thumbnailUrl && Url::isRelative($thumbnailUrl)) {
            $host = Yii::$app->request->hostInfo;
            $thumbnailUrl = Yii::getAlias("{$host}{$thumbnailUrl}");
        }
        return $thumbnailUrl ?: $url;
    }

    public function get(
        $url,
        $options = []
    ) {
        if ($url == null) {
            return null;
        }

        $options = ArrayHelper::merge($this->getDefaultOptions(), $options);
        $width = $options['width'] ?: null;
        $height = $options['height'] ?: null;
        $quality = $options['quality'] ?: 60;
        $mode = $options['mode'] ?: ManipulatorInterface::THUMBNAIL_OUTBOUND;

        $url = Yii::getAlias($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) && !file_exists($url)) {
            return null;
        }

        // if (!filter_var($url, FILTER_VALIDATE_URL) && !file_exists($url)) {
        //     throw new InvalidParamException(Yii::t('app', '$url expects a valid URL'));
        // }
        // if (!filter_var($url, FILTER_VALIDATE_URL)) {
        //     throw new InvalidParamException(Yii::t('app', '$url expects a valid URL'));
        // }
        // throw new \Exception($url);
        // die;
        // $width = $width ?: $this->defaultWidth;
        // $this->defaultHeight = $this->defaultHeight ?: $width;
        // $height = $height ?: $this->defaultHeight;
        // $quality = $quality ?: $this->defaultQuality;
        $thumbnailUrl = null;
        if ($this->enableCaching) {
            $key = [$url, $width, $height, $quality];
            $thumbnailUrl = $this->cache->get($key);
            if (!$thumbnailUrl) {
                $thumbnailUrl = $this->generateThumbnail($url, $width, $height, $quality, $mode);
                if ($thumbnailUrl) {
                    $this->cache->set($key, $thumbnailUrl, $this->cachingDuration);
                } else {
                }
            }
        } else {
            $thumbnailUrl = $this->generateThumbnail($url, $width, $height, $quality, $mode);
        }
        if ($thumbnailUrl && Url::isRelative($thumbnailUrl)) {
            $host = Yii::$app->request->hostInfo;
            $thumbnailUrl = Yii::getAlias("{$host}{$thumbnailUrl}");
        }
        return $thumbnailUrl ?: $url;
    }

    protected function generateThumbnail(
        $url,
        $width = null,
        $height = null,
        $quality = null,
        $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND
    ) {
        $filename = basename($url);
        try {
            $arrContextOptions = [
                "ssl" => [
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ],
            ];
            $imageData = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            // $imageData = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            
            list($sourceWidth, $sourceHeight) = $this->getImageSize($imageData);
            list($width, $height) = $this->getImageNewSize($sourceWidth, $sourceHeight, $width, $height);
            $dirWidthLabel = str_replace('.', '_', $width);
            $dirHeightLabel = str_replace('.', '_', $height);

            $thumbnailDir = StringHelper::strtr($this->thumbnailsPath, ['filename' => $filename]);
            $thumbnailPath = Yii::getAlias("$thumbnailDir/{$dirWidthLabel}x{$dirHeightLabel}/{$filename}");

            if ($imageData) {
                FileHelper::createDirectory(dirname($thumbnailPath));
                file_put_contents($thumbnailPath, $imageData, true);
                Imagine::thumbnail($thumbnailPath, $width, $height, $mode)
                    ->save($thumbnailPath, ['quality' => $quality]);
            } else {
                return null;
            }
        } catch (\Exception $ex) {
            if (YII_DEBUG) {
                throw new \Exception($ex);
            }
            return null;
        }
        $thumbnailsBaseUrl = StringHelper::strtr($this->thumbnailsBaseUrl, ['filename' => $filename]);
        return Yii::getAlias(str_replace(Yii::getAlias($thumbnailDir), $thumbnailsBaseUrl,
            $thumbnailPath));
    }

    protected function getImageSize($imageData)
    {
        $im = imagecreatefromstring($imageData);
        $width = imagesx($im);
        $height = imagesy($im);
        return [$width, $height];
    }

    protected function getImageNewSize($sourceWidth, $sourceHeight, $width = null, $height = null)
    {
        if ($width && $height) {
            return [$width, $height];
        }

        if ($width === null && $height === null) {
            $width = 300;
        }

        if ($width) {
            $ratio = $sourceWidth / $width;
            $height = $sourceHeight / $ratio;
        } else if ($height) {
            $ratio = $sourceHeight / $height;
            $width = $sourceWidth / $ratio;
        }

        return [$width, $height];
    }
}
