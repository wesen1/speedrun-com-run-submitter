<?php

namespace SpeedrunComRunSubmitter\EntityFetcher;

class Game extends Base
{
    const CACHE_KEY_GAME_BY_ABBREVIATION = "game_by_abbreviation_%s";
    const CACHE_TTL_GAME_BY_ABBREVIATION = 60 * 60;

    public function getGameByAbbreviation(string $_abbreviation)
    {
        return $this->getOrFetch(
            sprintf(self::CACHE_KEY_GAME_BY_ABBREVIATION, $_abbreviation),
            self::CACHE_TTL_GAME_BY_ABBREVIATION,
            function() use($_abbreviation)
            {
                return $this->doFetchGameByAbbreviation($_abbreviation);
            }
        );
    }

    protected function doFetchGameByAbbreviation(string $_abbreviation)
    {
        $response = $this->client->request('GET', 'https://www.speedrun.com/api/v1/games', [ "query" => [ "abbreviation" => $_abbreviation ]]);

        $json = json_decode($response->getBody());
        foreach ($json->data as $game)
        {
            if ($game->abbreviation === $_abbreviation)
            {
                return $game;
            }
        }

        return null;
    }
}
