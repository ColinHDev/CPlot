<?php

namespace ColinHDev\CPlot\provider;

use pocketmine\player\Player;

abstract class EconomyProvider {

    abstract public function getCurrency() : string;

    abstract public function getMoney(Player $player) : ?float;
    abstract public function removeMoney(Player $player, float $money, string $message) : bool;
}