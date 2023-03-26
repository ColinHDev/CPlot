<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class ResourceManager {
    use SingletonTrait;

    private Config $config;

    public function __construct() {
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "language")) {
            mkdir(CPlot::getInstance()->getDataFolder() . "language");
        }
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) {
            mkdir(CPlot::getInstance()->getDataFolder() . "schematics");
        }

        CPlot::getInstance()->saveResource("config.yml");

        foreach (CPlot::getInstance()->getResources() as $path => $fileInfo) {
			if(str_contains($path, "language") and strtolower($fileInfo->getExtension()) === "ini") {
				CPlot::getInstance()->saveResource($path);
			}
        }

        $this->config = new Config(CPlot::getInstance()->getDataFolder() . "config.yml", Config::YAML);
    }

    public function getConfig() : Config {
        $this->config->reload();
        return $this->config;
    }
}