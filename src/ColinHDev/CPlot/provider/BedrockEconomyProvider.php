<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\provider\utils\EconomyException;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use pocketmine\player\Player;
use pocketmine\Server;

class BedrockEconomyProvider extends EconomyProvider {

    /**
     * @throws \RuntimeException
     */
    public function __construct() {
        if (Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy") === null) {
            throw new \RuntimeException("BedrockEconomyProvider requires the plugin \"BedrockEconomy\" to be installed.");
        }
    }

    public function getCurrency() : string {
        return BedrockEconomy::getInstance()->getCurrencyManager()->getSymbol();
    }

    public function parseMoneyToString(float $money) : string {
        return (string) floor($money);
    }

    public function removeMoney(Player $player, float $money, string $reason, callable $onSuccess, callable $onError) : void {
        $intMoney = (int) floor($money);
        $promise = BedrockEconomyAPI::beta()->deduct(
            $player->getName(),
            $intMoney
        );
        $promise->onCompletion(
            \Closure::fromCallable($onSuccess),
            static function() use ($onError) {
                $onError(
                    new EconomyException()
                );
            }
        );
    }
}