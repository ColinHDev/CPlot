<?php

namespace ColinHDev\CPlot\provider;

use pocketmine\player\Player;

abstract class EconomyProvider {

    public const PRICE_CLAIM = "claimPrice";
    public const PRICE_CLEAR = "clearPrice";
    public const PRICE_MERGE = "mergePrice";
    public const PRICE_RESET = "resetPrice";

    /** @var array<string, float> */
    private array $prices = [];

    public function __construct(array $settings) {
        $this->prices[self::PRICE_CLAIM] = (float) ($settings[self::PRICE_CLAIM] ?? 0.0);
        $this->prices[self::PRICE_CLEAR] = (float) ($settings[self::PRICE_CLEAR] ?? 0.0);
        $this->prices[self::PRICE_MERGE] = (float) ($settings[self::PRICE_MERGE] ?? 0.0);
        $this->prices[self::PRICE_RESET] = (float) ($settings[self::PRICE_RESET] ?? 0.0);
    }

    public function getPrice(string $priceID) : ?float {
        return $this->prices[$priceID] ?? null;
    }

    abstract public function getCurrency() : string;
    abstract public function parseMoneyToString(float $money) : string;

    abstract public function getMoney(Player $player) : ?float;
    abstract public function removeMoney(Player $player, float $money, string $message) : bool;
}