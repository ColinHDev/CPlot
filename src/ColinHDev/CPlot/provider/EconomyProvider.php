<?php

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
     * @internal method to get fetch a player's money from the economy plugin while also using a
     * {@see \Generator} function which we can handle with {@see Await}.
     * @phpstan-param \Generator<null, \Generator, float|null, float|null>
     */
    final public function awaitMoney(Player $player) : \Generator {
        $this->getMoney($player, yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * This method is used to fetch a player's money from the economy plugin.
     * Since we want to support both economy plugins with asynchronous and synchronous database design, we provide
     * callbacks that can be called either directly if the plugin uses a synchronous design, or later when e.g. the
     * query for the money was finished if the plugin uses an asynchronous one.
     * @phpstan-param callable(float|null): void $onSuccess
     * @phpstan-param callable(\Throwable): void $onError
     */
    abstract public function getMoney(Player $player, callable $onSuccess, callable $onError) : void;

    /**
     * @internal method to remove money from a player through the economy plugin while also using a
     * {@see \Generator} function which we can handle with {@see Await}.
     * @phpstan-param \Generator<null, \Generator, null, null>
     */
    final public function awaitMoneyRemoval(Player $player, float $money) : \Generator {
        $this->removeMoney($player, $money, yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * This method is used to remove money from a player through the economy plugin.
     * @phpstan-param callable(): void $onSuccess
     * @phpstan-param callable(\Throwable): void $onError
     */
    abstract public function removeMoney(Player $player, float $money, callable $onSuccess, callable $onError) : void;
}