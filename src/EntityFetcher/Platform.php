<?php
/**
 * @author wesen
 * @copyright 2021 wesen <wesen-ac@web.de>
 * @license MIT
 */

namespace SpeedrunComRunSubmitter\EntityFetcher;

use stdClass;

class Platform extends Base
{
    const CACHE_KEY_PLATFORMS_BY_GAME = "platforms_by_game_%s";
    const CACHE_TTL_PLATFORMS_BY_GAME = 60 * 60;

    public function getPlatformByName(stdClass $_game, string $_name)
    {
        $platforms = $this->getOrFetch(
            sprintf(self::CACHE_KEY_PLATFORMS_BY_GAME, $_game->id),
            self::CACHE_TTL_PLATFORMS_BY_GAME,
            function() use($_game)
            {
                return $this->doFetchPlatformsByGame($_game);
            }
        );

        return $platforms[$_name] ?? null;
    }

    protected function doFetchPlatformsByGame(stdClass $_game)
    {
        $platformMap = [];
        foreach ($_game->platforms as $platformId)
        {
            $platformUrl = "https://www.speedrun.com/api/v1/platforms/" . $platformId;
            $response = $this->client->request('GET', $platformUrl);

            $json = json_decode($response->getBody());
            $platform = $json->data;
            $platformMap[$platform->name] = $platform;
        }

        return $platformMap;
    }
}
