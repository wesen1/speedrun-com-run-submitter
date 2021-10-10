<?php
/**
 * @author wesen
 * @copyright 2021 wesen <wesen-ac@web.de>
 * @license MIT
 */

namespace SpeedrunComRunSubmitter\EntityFetcher;

use GuzzleHttp\Client;
use SpeedrunComRunSubmitter\Cache;

abstract class Base
{
    protected $cache;
    protected $client;

    public function __construct(Cache $_cache, Client $_client)
    {
        $this->cache = $_cache;
        $this->client = $_client;
    }

    protected function getOrFetch(string $_cacheKey, int $_timeToLiveInSeconds, callable $_fetchCallback)
    {
        $result = $this->cache->get($_cacheKey);
        if ($result === null)
        {
            $result = call_user_func($_fetchCallback);
            $this->cache->set($_cacheKey, $result, $_timeToLiveInSeconds);
        }

        return $result;
    }
}
