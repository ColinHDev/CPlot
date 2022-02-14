<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\CommandSender;

/**
 * @phpstan-extends Subcommand<null>
 */
class HelpSubcommand extends Subcommand {

    private PlotCommand $command;

    /**
     * @throws \JsonException
     */
    public function __construct(string $key, PlotCommand $command) {
        parent::__construct($key);
        $this->command = $command;
    }

    public function execute(CommandSender $sender, array $args) : \Generator {
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
        $checkPermission = (bool) ResourceManager::getInstance()->getConfig()->get("help.checkPermission", true);
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
        /** @var array<int, array<string, Subcommand<mixed>>> $subcommands */
        $subcommands = array_chunk($subcommands, $sender->getScreenLineHeight());
        /** @var int $page */
        $page = min(count($subcommands), $page);

        $subcommandsOnPage = [];
        foreach ($subcommands[$page - 1] as $subcommand) {
            /** @phpstan-var string $description */
            $description = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, $subcommand->getName() . ".description");
            $subcommandsOnPage[] = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $sender,
                ["help.success.list" => [$subcommand->getName(), $description]]
            );
        }

        /** @phpstan-var string $separator */
        $separator = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "help.success.list.separator");
        $list = implode($separator, $subcommandsOnPage);
        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
            $sender,
            [
                "prefix",
                "help.success" => [$page, count($subcommands), $list]
            ]
        );
        return null;
    }
}