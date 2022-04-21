<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\ResourceManager;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\Capital\Capital;
use SOFe\Capital\CapitalException;
use SOFe\Capital\LabelSet;
use SOFe\Capital\Schema\Complete;

class CapitalEconomyProvider extends EconomyProvider {

    // The Capital API version this plugin is compatible with. Obviously, do not increment if the newer version was not tested.
    private const CAPITAL_API_VERSION = "0.1.0";

    private Complete $selector;
    private string $currency;

    /**
     * @throws \RuntimeException
     */
    public function __construct() {
        if (Server::getInstance()->getPluginManager()->getPlugin("Capital") === null) {
            throw new \RuntimeException("CapitalEconomyProvider requires the plugin \"Capital\" to be installed.");
        }
        $this->currency = ResourceManager::getInstance()->getConfig()->getNested("economy.capital.currency", "$");
        Capital::api(
            self::CAPITAL_API_VERSION,
            function(Capital $api) : void {
                $this->selector = $api->completeConfig(ResourceManager::getInstance()->getConfig()->getNested("economy.capital.selector", []));
            }
        );
    }

    public function getCurrency() : string {
        return $this->currency;
    }

    public function parseMoneyToString(float $money) : string {
        return (string) floor($money);
    }

    public function getMoney(Player $player, callable $onSuccess, callable $onError) : void {
        Capital::api(
            self::CAPITAL_API_VERSION,
            function(Capital $api) use($player, $onSuccess, $onError) : \Generator {
                try {
                    $accounts = yield from $api->findAccountsComplete($player, $this->selector);
                    $money = yield from $api->getBalance($account);
                    $onSuccess($money);
                } catch (CapitalException $e) {
                    $onError($e);
                }
            }
        );
    }

    /**
     * @throws \pocketmine\plugin\PluginException
     */
    public function removeMoney(Player $player, float $money, callable $onSuccess, callable $onError) : void {
        $intMoney = (int) floor($money);
        Capital::api(
            self::CAPITAL_API_VERSION,
            function(Capital $api) use($player) : \Generator {
                try {
                    yield from $api->takeMoney(
                        "CPlot",
                        $player,
                        $this->selector,
                        5,
                        new LabelSet(["reason" => "chatting"]),
                    );

                    $player->sendMessage("You lost $5 for chatting");
                } catch(CapitalException $e) {
                    $player->kick("You don't have money to chat");
                }
            }
        );
    }
}