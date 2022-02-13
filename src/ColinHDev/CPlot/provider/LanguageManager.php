<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use pocketmine\utils\SingletonTrait;

final class LanguageManager {
    use SingletonTrait;

    private LanguageProvider $provider;

    public function __construct() {
        $this->provider = new CPlotLanguageProvider();
    }

    /**
     * Returns the {@see LanguageProvider} instance that is currently used for CPlot's language integration and
     * can be used to interact with the specific language provider.
     */
    public function getProvider() : LanguageProvider {
        return $this->provider;
    }

    /**
     * Change the current {@see LanguageProvider} instance.
     * This method can be used to make CPlot compatible with your own, (maybe closed-source) language manager.
     */
    public function setProvider(LanguageProvider $provider) : void {
        $this->provider = $provider;
    }
}