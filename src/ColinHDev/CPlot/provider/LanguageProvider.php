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
     * @internal method to translate a message for the given {@see CommandSender} while also using a {@see \Generator}
     * function which we can handle with {@see Await}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, string>
     */
    final public function awaitTranslationForCommandSender(CommandSender $sender, array|string $keys) : \Generator {
        /** @phpstan-var string $message */
        $message = yield from Await::promise(
            fn($onSuccess, $onError) => $this->translateForCommandSender($sender, $keys, $onSuccess, $onError)
        );
        return $message;
    }

    /**
     * This method is used to translate a message for the given {@see CommandSender}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-param \Closure(string): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    abstract public function translateForCommandSender(CommandSender $sender, array|string $keys, \Closure $onSuccess, ?\Closure $onError = null) : void;

    /**
     * @internal method to send a message to the given {@see CommandSender} while also using a {@see \Generator}
     * function which we can handle with {@see Await}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, void>
     */
    final public function awaitMessageSendage(CommandSender $sender, array|string $keys) : \Generator {
        yield from Await::promise(
            fn($onSuccess, $onError) => $this->sendMessage($sender, $keys, $onSuccess, $onError)
        );
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
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, void>
     */
    final public function awaitTipSendage(Player $player, array|string $keys) : \Generator {
        yield from Await::promise(
            fn($onSuccess, $onError) => $this->sendTip($player, $keys, $onSuccess, $onError)
        );
    }

    /**
     * This method is used to send a tip to the given {@see Player}.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     * @phpstan-param null|\Closure(null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    abstract public function sendTip(Player $player, array|string $keys, ?\Closure $onSuccess = null, ?\Closure $onError = null) : void;
}