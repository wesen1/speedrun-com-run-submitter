<?php
/**
 * @author wesen
 * @copyright 2021 wesen <wesen-ac@web.de>
 * @license MIT
 */

$loader = require_once __DIR__ . "/vendor/autoload.php";
$loader->addPsr4("SpeedrunComRunSubmitter\\", __DIR__ . "/src");

use GuzzleHttp\Client;
use SpeedrunComRunSubmitter\Cache;
use SpeedrunComRunSubmitter\EntityFetcher\Category;
use SpeedrunComRunSubmitter\EntityFetcher\Game;
use SpeedrunComRunSubmitter\EntityFetcher\Level;
use SpeedrunComRunSubmitter\EntityFetcher\Platform;
use SpeedrunComRunSubmitter\EntityFetcher\Variable;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Config
$apiKey = "<your API key>";


// Input
$gameName = "assaultcube";
$runInfo = (object)[
    "category" => "Any%",
    "level" => "Gema-Magic",
    "date" => "2021-10-08",
    "platform" => "PC",
    "verified" => false,
    "times" => (object)[
        "realtime" => 40.79
    ],
    "players" => [
        (object)[
            "rel" => "guest",
            "name" => "unarmed"
        ]
    ],
    "emulated" => false,
    "comment" => "TODO",
    "variables" => (object)[
        "Weapon" => (object)[
            "type" => "pre-defined",
            "value" => "Knife Only"
        ],
        "Version" => (object)[
            "type" => "pre-defined",
            "value" => "1.2.0.2"
        ]
    ]
];



// Initialize required objects
$cacheDirectory = __DIR__  . "/cache";
$cache = new Cache(
    new FilesystemAdapter("speedrun_com_entities", 0, $cacheDirectory)
);
$client = new Client();

$game = new Game($cache, $client);
$level = new Level($cache, $client);
$category = new Category($cache, $client);
$variableFetcher = new Variable($cache, $client);
$platform = new Platform($cache, $client);

// Fetch game
$targetGame = $game->getGameByAbbreviation($gameName);
if ($targetGame)
{
    echo "Game ID: " . $targetGame->id . PHP_EOL;

    // Fetch level
    $targetLevel = $level->getLevelByName($targetGame->id, $runInfo->level);
    if ($targetLevel)
    {
        echo "Level ID: " . $targetLevel->id . PHP_EOL;

        // Fetch category
        $targetCategory = $category->getCategoryByName($targetLevel->id, $runInfo->category);
        if ($targetCategory)
        {
            echo "Category ID: " . $targetCategory->id . PHP_EOL;
        }

        // Fetch platform
        $targetPlatform = $platform->getPlatformByName($targetGame, $runInfo->platform);
        if (!$targetPlatform) exit("Could not find platform");


        // Build run
        $runInfo->category = $targetCategory->id;
        $runInfo->level = $targetLevel->id;
        $runInfo->platform = $targetPlatform->id;

        foreach (get_object_vars($runInfo->variables) as $variableName => $variableData)
        {
            if ($variableData->type === "pre-defined")
            {
                $variable = $variableFetcher->getVariableByName($targetLevel->id, $variableName);
                if ($variable)
                {
                    $valueMap = [];
                    foreach (get_object_vars($variable->values->values) as $valueId => $valueInfo)
                    {
                        $valueMap[$valueInfo->label] = $valueId;
                    }

                    $valueId = $valueMap[$variableData->value] ?? null;
                    if ($valueId !== null)
                    {
                        $runInfo->variables->{$variable->id} = $variableData;
                        $runInfo->variables->{$variable->id}->value = $valueId;
                    }
                }

                unset($runInfo->variables->{$variableName});
            }
        }

        $response = $client->request("POST", "https://www.speedrun.com/api/v1/runs", [
            "headers" => [
                "X-API-Key" => $apiKey
            ],
            "body" => json_encode((object)[
                "run" => $runInfo
            ])
        ]);

        echo json_encode($runInfo, JSON_PRETTY_PRINT);

        echo $response->getStatusCode() . PHP_EOL;
        echo $response->getBody();
    }
}
