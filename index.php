<?php
/**
 * @author wesen
 * @copyright 2021 wesen <wesen-ac@web.de>
 * @license MIT
 */

require_once __DIR__ . "/vendor/autoload.php";


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
$client = new \GuzzleHttp\Client();


// Fetch game
$response = $client->request('GET', 'https://www.speedrun.com/api/v1/games', [ "query" => [ "abbreviation" => $gameName ]]);

$json = json_decode($response->getBody());
$targetGame = null;
foreach ($json->data as $game)
{
    if ($game->abbreviation === $gameName)
    {
        $targetGame = $game;
        break;
    }
}

if ($targetGame)
{
    echo "Game ID: " . $game->id . PHP_EOL;


    // Fetch level
    $levelsUrl = "https://www.speedrun.com/api/v1/games/" . $targetGame->id . "/levels";
    $response = $client->request('GET', $levelsUrl);

    $json = json_decode($response->getBody());

    $targetLevel = null;
    foreach ($json->data as $level)
    {
        if ($level->name === $runInfo->level)
        {
            $targetLevel = $level;
            break;
        }
    }

    if ($targetLevel)
    {
        echo "Level ID: " . $targetLevel->id . PHP_EOL;


        // Fetch category
        $categoriesUrl = "https://www.speedrun.com/api/v1/levels/" . $targetLevel->id . "/categories";
        $response = $client->request('GET', $categoriesUrl);
        $json = json_decode($response->getBody());

        $targetCategory = null;
        foreach ($json->data as $category)
        {
            if ($category->name === $runInfo->category)
            {
                $targetCategory = $category;
                break;
            }
        }

        if ($targetCategory)
        {
            echo "Category ID: " . $targetCategory->id . PHP_EOL;
        }


        // Fetch variables
        $variablesUrl = "https://www.speedrun.com/api/v1/levels/" . $targetLevel->id . "/variables";
        $response = $client->request('GET', $variablesUrl);
        $json = json_decode($response->getBody());

        $variablesMap = [];
        foreach ($json->data as $variable)
        {
            $variablesMap[$variable->name] = $variable;
        }

        echo json_encode($json, JSON_PRETTY_PRINT);
        echo PHP_EOL;


        // Fetch platform
        $platformMap = [];
        foreach ($targetGame->platforms as $platformId)
        {
            $variablesUrl = "https://www.speedrun.com/api/v1/platforms/" . $platformId;
            $response = $client->request('GET', $variablesUrl);
            $json = json_decode($response->getBody());
            $platform = $json->data;
            $platformMap[$platform->name] = $platform;
        }

        $targetPlatform = $platformMap[$runInfo->platform] ?? null;
        if ($targetPlatform === null) exit("Could not find platform");


        // Build run
        $runInfo->category = $targetCategory->id;
        $runInfo->level = $targetLevel->id;
        $runInfo->platform = $targetPlatform->id;

        foreach (get_object_vars($runInfo->variables) as $variableName => $variableData)
        {
            if ($variableData->type === "pre-defined")
            {
                $variable = $variablesMap[$variableName] ?? null;
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
