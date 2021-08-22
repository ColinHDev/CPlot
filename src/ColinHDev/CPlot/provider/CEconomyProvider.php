<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CEconomy\CEconomyAPI;
use ColinHDev\CEconomy\provider\Transaction;
use pocketmine\player\Player;

class CEconomyProvider extends EconomyProvider {

    public function getCurrency() : string {
        return CEconomyAPI::getCurrency();
    }

    public function getMoney(Player $player) : float {
        return CEconomyAPI::getPurseMoney($player->getUniqueId()->toString());
    }

    public function removeMoney(Player $player, float $money, string $message) : bool {
        return CEconomyAPI::removePurseMoney(
            $player->getUniqueId()->toString(),
            $money,
            new Transaction(
                Transaction::TRANSACTION_TYPE_PURSE,
                time(),
                $money,
                $message
            )
        );
    }
}