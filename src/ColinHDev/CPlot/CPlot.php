<?php

namespace ColinHDev\CPlot;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\listener\BlockBurningListener;
use ColinHDev\CPlot\listener\BlockGrowListener;
use ColinHDev\CPlot\listener\BlockSpreadListener;
use ColinHDev\CPlot\listener\BlockTeleportListener;
use ColinHDev\CPlot\listener\EntityExplodeListener;
use ColinHDev\CPlot\listener\PlayerMoveListener;
use ColinHDev\CPlot\listener\PlayerPreLoginListener;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\SQLiteProvider;
use ColinHDev\CPlot\tasks\EntityMovementTask;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use ColinHDev\CPlot\worlds\generators\PlotGenerator;
use ColinHDev\CPlot\worlds\generators\SchematicGenerator;

class CPlot extends PluginBase {

    private static CPlot $instance;

    public static function getInstance() : CPlot {
        return self::$instance;
    }


    private DataProvider $provider;

    public function getProvider() : DataProvider {
        return $this->provider;
    }


    public function onLoad() : void {
        self::$instance = $this;

        new ResourceManager();
        switch (strtolower(ResourceManager::getInstance()->getConfig()->getNested("database.provider", ""))) {
            case "sqlite":
            default:
                $this->provider = new SQLiteProvider(ResourceManager::getInstance()->getConfig()->getNested("database.sqlite"));
        }

        GeneratorManager::getInstance()->addGenerator(PlotGenerator::class, PlotGenerator::GENERATOR_NAME, true);
        GeneratorManager::getInstance()->addGenerator(SchematicGenerator::class, SchematicGenerator::GENERATOR_NAME, true);
    }

    public function onEnable() : void {
        $this->getScheduler()->scheduleRepeatingTask(new EntityMovementTask(), 1);

        $this->getServer()->getPluginManager()->registerEvents(new BlockBurningListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockGrowListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockSpreadListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityExplodeListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerMoveListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerPreLoginListener(), $this);

        $this->getServer()->getCommandMap()->register("plot", new PlotCommand());
    }
}