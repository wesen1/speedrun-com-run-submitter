<?php

namespace SpeedrunComRunSubmitter\EntityFetcher;

class Level extends Base
{
    const CACHE_KEY_LEVEL_BY_NAME = "level_by_name_%s_%s";
    const CACHE_TTL_LEVEL_BY_NAME = 60 * 60;

    public function getLevelByName(string $_gameId, string $_name)
    {
        return $this->getOrFetch(
            sprintf(self::CACHE_KEY_LEVEL_BY_NAME, $_gameId, $_name),
            self::CACHE_TTL_LEVEL_BY_NAME,
            function() use($_gameId, $_name)
            {
                return $this->doFetchLevelByName($_gameId, $_name);
            }
        );
    }

    protected function doFetchLevelByName(string $_gameId, string $_name)
    {
        $levelsUrl = "https://www.speedrun.com/api/v1/games/" . $_gameId . "/levels";
        $response = $this->client->request('GET', $levelsUrl);

        $json = json_decode($response->getBody());
        foreach ($json->data as $level)
        {
            if ($level->name === $_name)
            {
                return $level;
            }
        }

        return null;
    }
}
