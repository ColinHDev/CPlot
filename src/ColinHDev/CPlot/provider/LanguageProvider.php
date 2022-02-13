<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use pocketmine\command\CommandSender;
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
     * @internal method to translate a message for the given {@see CommandSender} while also using a {@see \Generator}
     * function which we can handle with {@see Await}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-return \Generator<int, Await::RESOLVE|Await::REJECT|Await::ONCE, (\Closure(mixed=): void)|(\Closure(\Throwable): void)|string, string>
     */
    final public function awaitTranslationForCommandSender(CommandSender $sender, array|string $keys) : \Generator {
        /** @phpstan-var \Closure(mixed=): void $onSuccess */
        $onSuccess = yield Await::RESOLVE;
        /** @phpstan-var \Closure(\Throwable): void $onError */
        $onError = yield Await::REJECT;
        $this->translateForCommandSender($sender, $keys, $onSuccess, $onError);
        return yield Await::ONCE;
    }

    /**
     * This method is used to translate a message for the given {@see CommandSender}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-param null|\Closure(string): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    abstract public function translateForCommandSender(CommandSender $sender, array|string $keys, \Closure $onSuccess, ?\Closure $onError = null) : void;

    /**
     * @internal method to send a message to the given {@see CommandSender} while also using a {@see \Generator}
     * function which we can handle with {@see Await}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-return \Generator<int, Await::RESOLVE|Await::REJECT|Await::ONCE, (\Closure(mixed=): void)|(\Closure(\Throwable): void)|null, null>
     */
    final public function awaitMessageSendage(CommandSender $sender, array|string $keys) : \Generator {
        /** @phpstan-var \Closure(mixed=): void $onSuccess */
        $onSuccess = yield Await::RESOLVE;
        /** @phpstan-var \Closure(\Throwable): void $onError */
        $onError = yield Await::REJECT;
        $this->sendMessage($sender, $keys, $onSuccess, $onError);
        return yield Await::ONCE;
    }

    /**
     * This method is used to send a message to the given {@see CommandSender} (e.g. a player or the console).
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-param null|\Closure(null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    abstract public function sendMessage(CommandSender $sender, array|string $keys, ?\Closure $onSuccess = null, ?\Closure $onError = null) : void;

    /**
     * @internal method to send a tip to the given {@see Player} while also using a {@see \Generator}
     * function which we can handle with {@see Await}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-return \Generator<int, Await::RESOLVE|Await::REJECT|Await::ONCE, (\Closure(mixed=): void)|(\Closure(\Throwable): void)|null, null>
     */
    final public function awaitTipSendage(Player $player, array|string $keys) : \Generator {
        /** @phpstan-var \Closure(mixed=): void $onSuccess */
        $onSuccess = yield Await::RESOLVE;
        /** @phpstan-var \Closure(\Throwable): void $onError */
        $onError = yield Await::REJECT;
        $this->sendTip($player, $keys, $onSuccess, $onError);
        return yield Await::ONCE;
    }

    /**
     * This method is used to send a tip to the given {@see Player}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-param null|\Closure(null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    abstract public function sendTip(Player $player, array|string $keys, ?\Closure $onSuccess = null, ?\Closure $onError = null) : void;
}