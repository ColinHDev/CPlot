<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class ResourceManager {
    use SingletonTrait;

    private Config $bordersConfig;
    private Config $config;
    private Config $wallsConfig;

    public function __construct() {
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "language")) {
            mkdir(CPlot::getInstance()->getDataFolder() . "language");
        }
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) {
            mkdir(CPlot::getInstance()->getDataFolder() . "schematics");
        }

        CPlot::getInstance()->saveResource("borders.yml");
        CPlot::getInstance()->saveResource("config.yml");
        CPlot::getInstance()->saveResource("walls.yml");

        foreach (CPlot::getInstance()->getResources() as $path => $fileInfo) {
            if (strtolower($fileInfo->getExtension()) !== "ini") {
                continue;
            }
            $subdirectory = str_replace(DIRECTORY_SEPARATOR . $fileInfo->getFilename(), "", $path);
            if (strtolower($subdirectory) === "language") {
                CPlot::getInstance()->saveResource($path);
            }
        }

        $this->bordersConfig = new Config(CPlot::getInstance()->getDataFolder() . "borders.yml", Config::YAML);
        $this->config = new Config(CPlot::getInstance()->getDataFolder() . "config.yml", Config::YAML);
        $this->wallsConfig = new Config(CPlot::getInstance()->getDataFolder() . "walls.yml", Config::YAML);
    }

    public function getBordersConfig() : Config {
        $this->bordersConfig->reload();
        return $this->bordersConfig;
    }

    public function getConfig() : Config {
        $this->config->reload();
        return $this->config;
    }

    public function getWallsConfig() : Config {
        $this->wallsConfig->reload();
        return $this->wallsConfig;
    }
}