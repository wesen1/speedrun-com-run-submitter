<?php

namespace SpeedrunComRunSubmitter;

use Psr\Cache\CacheItemPoolInterface;

class Cache
{
    private $cacheItemPool;

    public function __construct(CacheItemPoolInterface $_cacheItemPool)
    {
        $this->cacheItemPool = $_cacheItemPool;
    }

    public function get(string $_key)
    {
        return $this->cacheItemPool->getItem($_key)->get();
    }

    public function set(string $_key, $_value, int $_timeToLiveInSeconds)
    {
        $cacheItem = $this->cacheItemPool->getItem($_key);
        if (!$cacheItem->isHit())
        {
            $cacheItem->expiresAfter($_timeToLiveInSeconds);
        }

        $cacheItem->set($_value);
        $this->cacheItemPool->save($cacheItem);
    }
}
