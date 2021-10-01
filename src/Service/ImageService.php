<?php

namespace App\Service;

use \Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageService {
    public function __construct(CacheManager $imagineCacheManager)
    {
        $this->imagineCacheManager = $imagineCacheManager;
    }

    public function getCachedImage(string $webPath, string $filter = 'thumb_1920_1080'): ?string
    {
      if ($webPath) {
        return $this->imagineCacheManager->getBrowserPath($webPath, $filter);
      }
      return null;
    }

    public function buildManyImageWithCache(array &$serialized, string $filter = 'thumb_1920_1080')
    {
      foreach($serialized as &$serializedItem) {
        if (isset($serializedItem->image)) {
          $serializedItem->image->cachedImage = $this->getCachedImage(
            str_replace('./', '', $serializedItem->image->path.'/'.$serializedItem->image->filename),
            $filter
          );
        }
      }
    }

    public function buildOneImageWithCache(object &$serializedItem, string $filter = 'thumb_1920_1080')
    {
      if ($serializedItem->image) {
        $serializedItem->image->cachedImage = $this->getCachedImage(
          str_replace('./', '', $serializedItem->image->path.'/'.$serializedItem->image->filename),
          $filter
        );
      }
    }
}