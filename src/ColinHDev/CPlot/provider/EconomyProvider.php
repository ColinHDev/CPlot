<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

abstract class EconomyProvider {

    /**
     * This method should return the currency that is used by the economy plugin.
     * For example: "$" or "Coins"
     */
    abstract public function getCurrency() : string;

    /**
     * With this method, it can be defined how a money value of the economy plugin should be represented as a string.
     * The result will be used when displaying a money value to a player.
     * For example: 12345.089 -> 12,345.09
     */
    abstract public function parseMoneyToString(float $money) : string;

    /**
     * @internal method to remove money from a player through the economy plugin while also using a
     * {@see \Generator} function which we can handle with {@see Await}.
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, null, void>
     */
    final public function awaitMoneyRemoval(Player $player, float $money, string $reason) : \Generator {
        yield from Await::promise(
            fn($onSuccess, $onError) => $this->removeMoney($player, $money, $reason, $onSuccess, $onError)
        );
    }

    /**
     * This method is used to remove money from a player through the economy plugin.
     * @phpstan-param callable(mixed=): void $onSuccess
     * @phpstan-param callable(\Throwable): void $onError
     */
    abstract public function removeMoney(Player $player, float $money, string $reason, callable $onSuccess, callable $onError) : void;
}