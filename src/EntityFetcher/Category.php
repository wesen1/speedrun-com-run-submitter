<?php

namespace SpeedrunComRunSubmitter\EntityFetcher;

class Category extends Base
{
    const CACHE_KEY_CATEGORY_BY_LEVEL = "category_by_level_%s";
    const CACHE_TTL_CATEGORY_BY_LEVEL = 60 * 60;

    public function getCategoryByName(string $_levelId, string $_name)
    {
        $categories = $this->getOrFetch(
            sprintf(self::CACHE_KEY_CATEGORY_BY_LEVEL, $_levelId),
            self::CACHE_TTL_CATEGORY_BY_LEVEL,
            function() use($_levelId)
            {
                return $this->doFetchCategoriesByLevel($_levelId);
            }
        );

        return $categories[$_name] ?? null;
    }

    protected function doFetchCategoriesByLevel(string $_levelId)
    {
        $categoriesUrl = "https://www.speedrun.com/api/v1/levels/" . $_levelId . "/categories";
        $response = $this->client->request('GET', $categoriesUrl);

        $json = json_decode($response->getBody());
        $categoryMap = [];
        foreach ($json->data as $category)
        {
            $categoryMap[$category->name] = $category;
        }

        return $categoryMap;
    }
}
