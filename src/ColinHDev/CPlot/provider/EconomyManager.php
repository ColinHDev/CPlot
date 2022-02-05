<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\ResourceManager;
use pocketmine\utils\SingletonTrait;

final class EconomyManager {
    use SingletonTrait;

    public const PRICE_CLAIM = "claimPrice";
    public const PRICE_CLEAR = "clearPrice";
    public const PRICE_MERGE = "mergePrice";
    public const PRICE_RESET = "resetPrice";

    private ?EconomyProvider $provider;

    /** @var array<string, float> */
    private array $prices = [];

    public function __construct() {
        /** @var array{provider: string, claimPrice?: float|string, clearPrice?: float|string, mergePrice?: float|string, resetPrice?: float|string} $settings */
        $settings = ResourceManager::getInstance()->getConfig()->get("economy", []);
        $this->provider = match (strtolower($settings["provider"])) {
            default => null
        };
        $this->prices[self::PRICE_CLAIM] = (float) ($settings[self::PRICE_CLAIM] ?? 0.0);
        $this->prices[self::PRICE_CLEAR] = (float) ($settings[self::PRICE_CLEAR] ?? 0.0);
        $this->prices[self::PRICE_MERGE] = (float) ($settings[self::PRICE_MERGE] ?? 0.0);
        $this->prices[self::PRICE_RESET] = (float) ($settings[self::PRICE_RESET] ?? 0.0);
    }

    /**
     * Returns the {@see EconomyProvider} instance that is currently used for CPlot's economy plugin integration and
     * can be used to interact with the specific economy plugin or null if this integration is disabled
     */
    public function getProvider() : ?EconomyProvider {
        return $this->provider;
    }

    /**
     * Change the current {@see EconomyProvider} instance.
     * This method can be used to make CPlot compatible with your own, (maybe closed-source) economy plugin.
     */
    public function setProvider(?EconomyProvider $provider) : void {
        $this->provider = $provider;
    }

    /**
     * Returns the specific price that is attached to the provided price ID.
     */
    public function getPriceByID(string $priceID) : ?float {
        return $this->prices[$priceID] ?? null;
    }

    /**
     * Returns the price it costs to claim a plot.
     */
    public function getClaimPrice() : float {
        return $this->prices[self::PRICE_CLAIM];
    }

    /**
     * Returns the price it costs to clear a plot.
     */
    public function getClearPrice() : float {
        return $this->prices[self::PRICE_CLEAR];
    }

    /**
     * Returns the price it costs to merge plots.
     */
    public function getMergePrice() : float {
        return $this->prices[self::PRICE_MERGE];
    }

    /**
     * Returns the price it costs to reset a plot.
     */
    public function getResetPrice() : float {
        return $this->prices[self::PRICE_RESET];
    }
}