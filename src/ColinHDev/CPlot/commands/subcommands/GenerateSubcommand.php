<?php

namespace ColinHDev\CPlot\commands\subcommands;

use pocketmine\command\CommandSender;
use pocketmine\world\WorldCreationOptions;
use pocketmine\math\Vector3;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\worlds\PlotGenerator;
use ColinHDev\CPlotAPI\worlds\WorldSettings;

class GenerateSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }
        $worldName = $args[0];
        if ($sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("generate.worldExists", [$worldName]));
            return;
        }

        $options = new WorldCreationOptions();
        $options->setGeneratorClass(PlotGenerator::class);
        $worldSettings = WorldSettings::fromConfig();
        $options->setGeneratorOptions(json_encode($worldSettings->toArray()));
        $options->setSpawnPosition(new Vector3(0, $worldSettings->getGroundSize() + 1, 0));
        if (!$this->getPlugin()->getServer()->getWorldManager()->generateWorld($worldName, $options)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("generate.generateError"));
            return;
        }
        if (!$this->getPlugin()->getProvider()->addWorld($worldName, $worldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("generate.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("generate.success", [$worldName]));
    }
}