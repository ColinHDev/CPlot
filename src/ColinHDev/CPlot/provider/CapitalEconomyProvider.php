<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\provider\utils\EconomyException;
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
     * @throws \pocketmine\plugin\PluginException
     */
    public function __construct() {
        if (Server::getInstance()->getPluginManager()->getPlugin("Capital") === null) {
            throw new \RuntimeException("CapitalEconomyProvider requires the plugin \"Capital\" to be installed.");
        }

        $currency = ResourceManager::getInstance()->getConfig()->getNested("economy.capital.currency", "$");
        assert(is_string($currency));
        $this->currency = $currency;

        Capital::api(
            self::CAPITAL_API_VERSION,
            function(Capital $api) {
                $selector = ResourceManager::getInstance()->getConfig()->getNested("economy.capital.selector", []);
                assert(is_array($selector));
                $this->selector = $api->completeConfig($selector);
                return null;
            }
        );
    }

    public function getCurrency() : string {
        return $this->currency;
    }

    public function parseMoneyToString(float $money) : string {
        return (string) floor($money);
    }

    public function removeMoney(Player $player, float $money, string $reason, callable $onSuccess, callable $onError) : void {
        $intMoney = (int) floor($money);
        Capital::api(
            self::CAPITAL_API_VERSION,
            function(Capital $api) use($player, $intMoney, $reason, $onSuccess, $onError) : \Generator {
                try {
                    yield from $api->takeMoney(
                        "CPlot",
                        $player,
                        $this->selector,
                        $intMoney,
                        new LabelSet(["reason" => $reason]),
                    );
                    $onSuccess();
                } catch(CapitalException $capitalException) {
                    $onError(
                        new EconomyException(
                            match ($capitalException->getCode()) {
                                CapitalException::SOURCE_UNDERFLOW => EconomyException::SOURCE_UNDERFLOW,
                                CapitalException::NO_SUCH_ACCOUNT => EconomyException::SOURCE_NON_EXISTENT,
                                CapitalException::EVENT_CANCELLED => EconomyException::EVENT_CANCELLED,
                                default => EconomyException::UNKNOWN
                            },
                            $capitalException
                        )
                    );
                }
            }
        );
    }
}