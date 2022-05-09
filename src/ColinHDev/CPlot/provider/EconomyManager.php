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
    public const REASON_CLAIM = "claimReason";
    public const REASON_CLEAR = "clearReason";
    public const REASON_MERGE = "mergeReason";
    public const REASON_RESET = "resetReason";

    private ?EconomyProvider $provider;

    /** @var array<string, float> */
    private array $prices = [];
    /** @var array<string, string> */
    private array $reasons = [];

    public function __construct() {
        /** @var array{provider: string, claimPrice?: float|string, clearPrice?: float|string, mergePrice?: float|string, resetPrice?: float|string} $settings */
        $settings = ResourceManager::getInstance()->getConfig()->get("economy", []);
        $this->provider = match (strtolower($settings["provider"])) {
            "bedrockeconomy" => new BedrockEconomyProvider(),
            "capital" => new CapitalEconomyProvider(),
            default => null
        };
        $this->prices[self::PRICE_CLAIM] = (float) ($settings[self::PRICE_CLAIM] ?? 0.0);
        $this->prices[self::PRICE_CLEAR] = (float) ($settings[self::PRICE_CLEAR] ?? 0.0);
        $this->prices[self::PRICE_MERGE] = (float) ($settings[self::PRICE_MERGE] ?? 0.0);
        $this->prices[self::PRICE_RESET] = (float) ($settings[self::PRICE_RESET] ?? 0.0);
        $this->reasons[self::REASON_CLAIM] = $settings[self::REASON_CLAIM] ?? "plot claiming";
        $this->reasons[self::REASON_CLEAR] = $settings[self::REASON_CLEAR] ?? "plot clearing";
        $this->reasons[self::REASON_MERGE] = $settings[self::REASON_MERGE] ?? "plot merging";
        $this->reasons[self::REASON_RESET] = $settings[self::REASON_RESET] ?? "plot resetting";
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

    /**
     * Returns the charge reason which is maybe saved in the economy plugin's database when a plot is claimed.
     */
    public function getClaimReason() : string {
        return $this->reasons[self::REASON_CLAIM];
    }

    /**
     * Returns the charge reason which is maybe saved in the economy plugin's database when a plot is cleared.
     */
    public function getClearReason() : string {
        return $this->reasons[self::REASON_CLEAR];
    }

    /**
     * Returns the charge reason which is maybe saved in the economy plugin's database when plots are merged.
     */
    public function getMergeReason() : string {
        return $this->reasons[self::REASON_MERGE];
    }

    /**
     * Returns the charge reason which is maybe saved in the economy plugin's database when a plot is resetted.
     */
    public function getResetReason() : string {
        return $this->reasons[self::REASON_RESET];
    }
}