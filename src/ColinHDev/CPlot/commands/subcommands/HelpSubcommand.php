<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\CommandSender;

class HelpSubcommand extends Subcommand {

    private PlotCommand $command;

    public function __construct(array $commandData, string $permission, PlotCommand $command) {
        parent::__construct($commandData, $permission);
        $this->command = $command;
    }

    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $page = 1;
        } else if (is_numeric($args[0])) {
            $page = (int) $args[0];
            if ($page <= 0) {
                $page = 1;
            }
        } else {
            $page = 1;
        }

        $subcommands = [];
        $checkPermission = ResourceManager::getInstance()->getConfig()->get("help.checkPermission", true);
        foreach ($this->command->getSubcommands() as $subcommand) {
            if ($checkPermission) {
                if ($sender->hasPermission($subcommand->getPermission())) {
                    $subcommands[$subcommand->getName()] = $subcommand;
                }
            } else {
                $subcommands[$subcommand->getName()] = $subcommand;
            }
        }

        ksort($subcommands, SORT_NATURAL | SORT_FLAG_CASE);
        /** @var Subcommand[][] $subcommands */
        $subcommands = array_chunk($subcommands, $sender->getScreenLineHeight());
        $page = (int) min(count($subcommands), $page);

        $subcommandsOnPage = [];
        foreach ($subcommands[$page] as $subcommand) {
            $subcommandsOnPage[] = $this->translateString("help.success.list", [$subcommand->getName(), $subcommand->getDescription()]);
        }
        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "help.success",
                [
                    $page,
                    count($subcommands),
                    implode($this->translateString("help.success.list.separator"), $subcommandsOnPage)
                ]
            )
        );
    }
}