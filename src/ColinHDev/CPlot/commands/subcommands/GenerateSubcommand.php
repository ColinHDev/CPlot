<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\generator\PlotGenerator;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;

/**
 * @phpstan-extends Subcommand<string>
 */
class GenerateSubcommand extends Subcommand {

    /**
     * @throws \JsonException
     */
    public function execute(CommandSender $sender, array $args) : \Generator {
        if (count($args) === 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "generate.usage"]);
            return null;
        }
        $worldName = $args[0];
        if ($sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "generate.worldExists" => $worldName]);
            return null;
        }

        $options = new WorldCreationOptions();
        $options->setGeneratorClass(PlotGenerator::class);
        $worldSettings = WorldSettings::fromConfig();
        $worldSettingsArray = $worldSettings->toArray();
        $worldSettingsArray["worldName"] = $worldName;
        $options->setGeneratorOptions(json_encode($worldSettingsArray, JSON_THROW_ON_ERROR));
        $options->setSpawnPosition(new Vector3(0, $worldSettings->getGroundSize() + 1, 0));
        if (!Server::getInstance()->getWorldManager()->generateWorld($worldName, $options)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "generate.generateError"]);
            return null;
        }
        yield DataProvider::getInstance()->addWorld($worldName, $worldSettings);
        return $worldName;
    }

    /**
     * @phpstan-param string $return
     */
    public function onSuccess(CommandSender $sender, mixed $return) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "generate.success" => $return]);
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "generate.saveError" => $error->getMessage()]);
    }
}