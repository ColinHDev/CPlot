<?php

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\commands\subcommands\AddSubcommand;
use ColinHDev\CPlot\commands\subcommands\AutoSubcommand;
use ColinHDev\CPlot\commands\subcommands\BorderSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClaimSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClearSubcommand;
use ColinHDev\CPlot\commands\subcommands\DeniedSubcommand;
use ColinHDev\CPlot\commands\subcommands\DenySubcommand;
use ColinHDev\CPlot\commands\subcommands\FlagSubcommand;
use ColinHDev\CPlot\commands\subcommands\GenerateSubcommand;
use ColinHDev\CPlot\commands\subcommands\HelpersSubcommand;
use ColinHDev\CPlot\commands\subcommands\HelpSubcommand;
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
use ColinHDev\CPlot\commands\subcommands\WallSubcommand;
use ColinHDev\CPlot\commands\subcommands\WarpSubcommand;
use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use SOFe\AwaitGenerator\Await;

class PlotCommand extends Command implements PluginOwned {

    /** @var SubCommand[] */
    private array $subcommands = [];

    public function __construct() {
        $resourceManager = ResourceManager::getInstance();
        $commandData = $resourceManager->getCommandData("plot");
        parent::__construct($commandData["name"]);
        $this->setAliases($commandData["alias"]);
        $this->setDescription($commandData["description"]);
        $this->setUsage($commandData["usage"]);
        $this->setPermission("cplot.command.plot");
        $this->setPermissionMessage($resourceManager->getPrefix() . $commandData["permissionMessage"]);

        $this->registerSubcommand(new AddSubcommand($resourceManager->getCommandData("add"), "cplot.subcommand.add"));
        $this->registerSubcommand(new AutoSubcommand($resourceManager->getCommandData("auto"), "cplot.subcommand.auto"));
        $this->registerSubcommand(new BorderSubcommand($resourceManager->getCommandData("border"), "cplot.subcommand.border"));
        $this->registerSubcommand(new ClaimSubcommand($resourceManager->getCommandData("claim"), "cplot.subcommand.claim"));
        $this->registerSubcommand(new ClearSubcommand($resourceManager->getCommandData("clear"), "cplot.subcommand.clear"));
        $this->registerSubcommand(new DeniedSubcommand($resourceManager->getCommandData("denied"), "cplot.subcommand.denied"));
        $this->registerSubcommand(new DenySubcommand($resourceManager->getCommandData("deny"), "cplot.subcommand.deny"));
        $this->registerSubcommand(new FlagSubcommand($resourceManager->getCommandData("flag"), "cplot.subcommand.flag"));
        $this->registerSubcommand(new GenerateSubcommand($resourceManager->getCommandData("generate"), "cplot.subcommand.generate"));
        $this->registerSubcommand(new HelpersSubcommand($resourceManager->getCommandData("helpers"), "cplot.subcommand.helpers"));
        $this->registerSubcommand(new HelpSubcommand($resourceManager->getCommandData("help"), "cplot.subcommand.help", $this));
        $this->registerSubcommand(new InfoSubcommand($resourceManager->getCommandData("info"), "cplot.subcommand.info"));
        $this->registerSubcommand(new MergeSubcommand($resourceManager->getCommandData("merge"), "cplot.subcommand.merge"));
        $this->registerSubcommand(new RemoveSubcommand($resourceManager->getCommandData("remove"), "cplot.subcommand.remove"));
        $this->registerSubcommand(new ResetSubcommand($resourceManager->getCommandData("reset"), "cplot.subcommand.reset"));
        $this->registerSubcommand(new SchematicSubcommand($resourceManager->getCommandData("schematic"), "cplot.subcommand.schematic"));
        $this->registerSubcommand(new SettingSubcommand($resourceManager->getCommandData("setting"), "cplot.subcommand.setting"));
        $this->registerSubcommand(new TrustedSubcommand($resourceManager->getCommandData("trusted"), "cplot.subcommand.trusted"));
        $this->registerSubcommand(new TrustSubcommand($resourceManager->getCommandData("trust"), "cplot.subcommand.trust"));
        $this->registerSubcommand(new UndenySubcommand($resourceManager->getCommandData("undeny"), "cplot.subcommand.undeny"));
        $this->registerSubcommand(new UntrustSubcommand($resourceManager->getCommandData("untrust"), "cplot.subcommand.untrust"));
        $this->registerSubcommand(new VisitSubcommand($resourceManager->getCommandData("visit"), "cplot.subcommand.visit"));
        $this->registerSubcommand(new WallSubcommand($resourceManager->getCommandData("wall"), "cplot.subcommand.wall"));
        $this->registerSubcommand(new WarpSubcommand($resourceManager->getCommandData("warp"), "cplot.subcommand.warp"));
    }

    /**
     * @return SubCommand[]
     */
    public function getSubcommands() : array {
        return $this->subcommands;
    }

    public function registerSubcommand(Subcommand $subcommand) : void {
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
        Await::g2c(
            $command->execute($sender, $args),
            static function (mixed $return = null) use ($command, $sender) : void {
                $command->onSuccess($sender, $return);
            },
            static function (mixed $error = null) use ($command, $sender) : void {
                $command->onError($sender, $error);
            }
        );
    }

    public function getOwningPlugin() : Plugin {
        return CPlot::getInstance();
    }
}