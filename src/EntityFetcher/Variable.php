<?php
/**
 * @author wesen
 * @copyright 2021 wesen <wesen-ac@web.de>
 * @license MIT
 */

namespace SpeedrunComRunSubmitter\EntityFetcher;

class Variable extends Base
{
    const CACHE_KEY_VARIABLES_BY_LEVEL = "variables_by_level_%s";
    const CACHE_TTL_VARIABLES_BY_LEVEL = 60 * 60;

    public function getVariableByName(string $_levelId, string $_name)
    {
        $variables = $this->getOrFetch(
            sprintf(self::CACHE_KEY_VARIABLES_BY_LEVEL, $_levelId),
            self::CACHE_TTL_VARIABLES_BY_LEVEL,
            function() use($_levelId)
            {
                return $this->doFetchVariablesByLevel($_levelId);
            }
        );

        return $variables[$_name] ?? null;
    }

    protected function doFetchVariablesByLevel(string $_levelId)
    {
        $variablesUrl = "https://www.speedrun.com/api/v1/levels/" . $_levelId . "/variables";
        $response = $this->client->request('GET', $variablesUrl);

        $json = json_decode($response->getBody());

        $variablesMap = [];
        foreach ($json->data as $variable)
        {
            $variablesMap[$variable->name] = $variable;
        }

        return $variablesMap;
    }
}
