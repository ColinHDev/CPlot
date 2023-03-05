<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

/**
 * @phpstan-type MessageKey string
 * @phpstan-type MessageParam float|int|string|Translatable
 */
abstract class LanguageProvider {

    /**
     * This method is used to translate a message using the fallback language.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    abstract public function translateString(array|string $keys) : string;

    /**
     * This method is used to translate a message for the given {@see CommandSender}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    abstract public function translateForCommandSender(CommandSender $sender, array|string $keys) : string;

    /**
     * This method is used to send a message to the given {@see CommandSender} (e.g. a player or the console).
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    abstract public function sendMessage(CommandSender $sender, array|string $keys) : void;

    /**
     * This method is used to send a tip to the given {@see Player}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    abstract public function sendTip(Player $player, array|string $keys) : void;
}