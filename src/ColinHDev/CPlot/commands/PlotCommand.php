<?php

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\commands\subcommands\AddSubcommand;
use ColinHDev\CPlot\commands\subcommands\AutoSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClaimSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClearSubcommand;
use ColinHDev\CPlot\commands\subcommands\DeniedSubcommand;
use ColinHDev\CPlot\commands\subcommands\DenySubcommand;
use ColinHDev\CPlot\commands\subcommands\FlagSubcommand;
use ColinHDev\CPlot\commands\subcommands\GenerateSubcommand;
use ColinHDev\CPlot\commands\subcommands\HelpersSubcommand;
use ColinHDev\CPlot\commands\subcommands\InfoSubcommand;
use ColinHDev\CPlot\commands\subcommands\MergeSubcommand;
use ColinHDev\CPlot\commands\subcommands\RemoveSubcommand;
use ColinHDev\CPlot\commands\subcommands\ResetSubcommand;
use ColinHDev\CPlot\commands\subcommands\SchematicSubcommand;
use ColinHDev\CPlot\commands\subcommands\SettingSubcommand;
use ColinHDev\CPlot\commands\subcommands\TrustedSubcommand;
use ColinHDev\CPlot\commands\subcommands\TrustSubcommand;
use ColinHDev\CPlot\commands\subcommands\UndenySubcommand;
use ColinHDev\CPlot\commands\subcommands\UntrustSubcommand;
use ColinHDev\CPlot\commands\subcommands\VisitSubcommand;
use ColinHDev\CPlot\commands\subcommands\WarpSubcommand;
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
        $this->loadSubcommand(new AddSubcommand($resourceManager->getCommandData("add"), "cplot.subcommand.add"));
        $this->loadSubcommand(new ClaimSubcommand($resourceManager->getCommandData("claim"), "cplot.subcommand.claim"));
        $this->loadSubcommand(new ClearSubcommand($resourceManager->getCommandData("clear"), "cplot.subcommand.clear"));
        $this->loadSubcommand(new DeniedSubcommand($resourceManager->getCommandData("denied"), "cplot.subcommand.denied"));
        $this->loadSubcommand(new DenySubcommand($resourceManager->getCommandData("deny"), "cplot.subcommand.deny"));
        $this->loadSubcommand(new FlagSubcommand($resourceManager->getCommandData("flag"), "cplot.subcommand.flag"));
        $this->loadSubcommand(new GenerateSubcommand($resourceManager->getCommandData("generate"), "cplot.subcommand.generate"));
        $this->loadSubcommand(new HelpersSubcommand($resourceManager->getCommandData("helpers"), "cplot.subcommand.helpers"));
        $this->loadSubcommand(new InfoSubcommand($resourceManager->getCommandData("info"), "cplot.subcommand.info"));
        $this->loadSubcommand(new MergeSubcommand($resourceManager->getCommandData("merge"), "cplot.subcommand.merge"));
        $this->loadSubcommand(new RemoveSubcommand($resourceManager->getCommandData("remove"), "cplot.subcommand.remove"));
        $this->loadSubcommand(new ResetSubcommand($resourceManager->getCommandData("reset"), "cplot.subcommand.reset"));
        $this->loadSubcommand(new SchematicSubcommand($resourceManager->getCommandData("schematic"), "cplot.subcommand.schematic"));
        $this->loadSubcommand(new SettingSubcommand($resourceManager->getCommandData("setting"), "cplot.subcommand.setting"));
        $this->loadSubcommand(new TrustedSubcommand($resourceManager->getCommandData("trusted"), "cplot.subcommand.trusted"));
        $this->loadSubcommand(new TrustSubcommand($resourceManager->getCommandData("trust"), "cplot.subcommand.trust"));
        $this->loadSubcommand(new UndenySubcommand($resourceManager->getCommandData("undeny"), "cplot.subcommand.undeny"));
        $this->loadSubcommand(new UntrustSubcommand($resourceManager->getCommandData("untrust"), "cplot.subcommand.untrust"));
        $this->loadSubcommand(new VisitSubcommand($resourceManager->getCommandData("visit"), "cplot.subcommand.visit"));
        $this->loadSubcommand(new WarpSubcommand($resourceManager->getCommandData("warp"), "cplot.subcommand.warp"));
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