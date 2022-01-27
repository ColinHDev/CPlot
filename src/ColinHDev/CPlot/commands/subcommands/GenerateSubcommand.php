<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\provider\DataProvider;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;
use pocketmine\math\Vector3;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\worlds\generator\PlotGenerator;
use ColinHDev\CPlot\worlds\WorldSettings;
use poggit\libasynql\SqlError;

class GenerateSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return null;
        }
        $worldName = $args[0];
        if ($sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("generate.worldExists", [$worldName]));
            return null;
        }

        $options = new WorldCreationOptions();
        $options->setGeneratorClass(PlotGenerator::class);
        $worldSettings = WorldSettings::fromConfig();
        $options->setGeneratorOptions(json_encode($worldSettings->toArray()));
        $options->setSpawnPosition(new Vector3(0, $worldSettings->getGroundSize() + 1, 0));
        if (!Server::getInstance()->getWorldManager()->generateWorld($worldName, $options)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("generate.generateError"));
            return null;
        }
        yield from DataProvider::getInstance()->addWorld($worldName, $worldSettings);
        return $worldName;
    }

    /**
     * @param string $worldName
     */
    public function onSuccess(CommandSender $sender, mixed $worldName) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("generate.success", [$worldName]));
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("generate.saveError", [$error->getMessage()]));
    }
}