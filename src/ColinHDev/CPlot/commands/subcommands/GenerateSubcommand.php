<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\event\PlotWorldGenerateAsyncEvent;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\generator\PlotGenerator;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use JsonException;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;
use poggit\libasynql\SqlError;

class GenerateSubcommand extends AsyncSubcommand {

    /**
     * @throws JsonException
     */
    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "generate.usage"]);
            return;
        }
        $worldName = $args[0];
        if ($sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
            self::sendMessage($sender, ["prefix", "generate.worldExists" => $worldName]);
            return;
        }

        $options = new WorldCreationOptions();
        $options->setGeneratorClass(PlotGenerator::class);
        $worldSettings = WorldSettings::fromConfig();
        $worldSettingsArray = $worldSettings->toArray();
        $worldSettingsArray["worldName"] = $worldName;
        $options->setGeneratorOptions(json_encode($worldSettingsArray, JSON_THROW_ON_ERROR));
        $options->setSpawnPosition(new Vector3(0, $worldSettings->getGroundSize() + 1, 0));

        /** @phpstan-var PlotWorldGenerateAsyncEvent $event */
        $event = yield from PlotWorldGenerateAsyncEvent::create($worldName, $worldSettings, $options);
        if ($event->isCancelled()) {
            return;
        }
        $worldName = $event->getWorldName();

        if (!Server::getInstance()->getWorldManager()->generateWorld($worldName, $event->getWorldCreationOptions())) {
            self::sendMessage($sender, ["prefix", "generate.generateError"]);
            return;
        }
        try {
            yield from DataProvider::getInstance()->addWorld($worldName, $event->getWorldSettings());
        } catch(SqlError $exception) {
            self::sendMessage($sender, ["prefix", "generate.saveError"]);
            $sender->getServer()->getLogger()->logException($exception);
            return;
        }
        self::sendMessage($sender, ["prefix", "generate.success" => $worldName]);
    }
}