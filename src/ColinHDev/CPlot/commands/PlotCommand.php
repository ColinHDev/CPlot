<?php

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\commands\subcommands\AutoSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClaimSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClearSubcommand;
use ColinHDev\CPlot\commands\subcommands\FlagSubcommand;
use ColinHDev\CPlot\commands\subcommands\GenerateSubcommand;
use ColinHDev\CPlot\commands\subcommands\InfoSubcommand;
use ColinHDev\CPlot\commands\subcommands\MergeSubcommand;
use ColinHDev\CPlot\commands\subcommands\SchematicSubcommand;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class PlotCommand extends Command {

    /** @var SubCommand[] */
    private array $subcommands = [];

    /**
     * @return SubCommand[]
     */
    public function getSubcommands() : array {
        return $this->subcommands;
    }

    public function __construct() {
        $resourceManager = ResourceManager::getInstance();
        $commandData = $resourceManager->getCommandData("plot");
        parent::__construct($commandData["name"]);
        $this->setAliases($commandData["alias"]);
        $this->setDescription($commandData["description"]);
        $this->setUsage($commandData["usage"]);
        $this->setPermission("cplot.command.plot");
        $this->setPermissionMessage($resourceManager->getPrefix() . $commandData["permissionMessage"]);

        $this->loadSubcommand(new AutoSubcommand($resourceManager->getCommandData("auto"), "cplot.subcommand.auto"));
        $this->loadSubcommand(new ClaimSubcommand($resourceManager->getCommandData("claim"), "cplot.subcommand.claim"));
        $this->loadSubcommand(new ClearSubcommand($resourceManager->getCommandData("clear"), "cplot.subcommand.clear"));
        $this->loadSubcommand(new FlagSubcommand($resourceManager->getCommandData("flag"), "cplot.subcommand.flag"));
        $this->loadSubcommand(new GenerateSubcommand($resourceManager->getCommandData("generate"), "cplot.subcommand.generate"));
        $this->loadSubcommand(new InfoSubcommand($resourceManager->getCommandData("info"), "cplot.subcommand.info"));
        $this->loadSubcommand(new MergeSubcommand($resourceManager->getCommandData("merge"), "cplot.subcommand.merge"));
        $this->loadSubcommand(new SchematicSubcommand($resourceManager->getCommandData("schematic"), "cplot.subcommand.schematic"));
    }

    private function loadSubcommand(Subcommand $subcommand) : void {
        $this->subcommands[$subcommand->getName()] = $subcommand;
        foreach ($subcommand->getAlias() as $alias) {
            $this->subcommands[$alias] = $subcommand;
        }
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage(ResourceManager::getInstance()->getPrefix() . $this->getUsage());
            return;
        }

        $subcommand = strtolower(array_shift($args));
        if (!isset($this->subcommands[$subcommand])) {
            $sender->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("plot.unknownSubcommand"));
            return;
        }

        $command = $this->subcommands[$subcommand];
        if (!$command->testPermission($sender)) {
            return;
        }
        $command->execute($sender, $args);
    }
}