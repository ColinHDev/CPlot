<?php

namespace ColinHDev\CPlot;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\SQLiteProvider;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use ColinHDev\CPlot\worlds\generators\PlotGenerator;
use ColinHDev\CPlot\worlds\generators\SchematicGenerator;

class CPlot extends PluginBase {

    private static CPlot $instance;

    private DataProvider $provider;

    /**
     * @return CPlot
     */
    public static function getInstance() : CPlot {
        return self::$instance;
    }

    /**
     * @return DataProvider
     */
    public function getProvider() : DataProvider {
        return $this->provider;
    }

    public function onLoad() : void {
        self::$instance = $this;

        new ResourceManager();
        $this->provider = new SQLiteProvider(ResourceManager::getInstance()->getConfig()->getNested("database.sqlite"));

        GeneratorManager::getInstance()->addGenerator(PlotGenerator::class, PlotGenerator::GENERATOR_NAME, true);
        GeneratorManager::getInstance()->addGenerator(SchematicGenerator::class, SchematicGenerator::GENERATOR_NAME, true);
    }

    public function onEnable() : void {
        $this->getServer()->getCommandMap()->register("plot", new PlotCommand());
    }
}