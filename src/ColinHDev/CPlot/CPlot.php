<?php

namespace ColinHDev\CPlot;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\listener\BlockBreakListener;
use ColinHDev\CPlot\listener\BlockBurningListener;
use ColinHDev\CPlot\listener\BlockGrowListener;
use ColinHDev\CPlot\listener\BlockPlaceListener;
use ColinHDev\CPlot\listener\BlockSpreadListener;
use ColinHDev\CPlot\listener\BlockTeleportListener;
use ColinHDev\CPlot\listener\EntityDamageByEntityListener;
use ColinHDev\CPlot\listener\EntityExplodeListener;
use ColinHDev\CPlot\listener\EntityItemPickupListener;
use ColinHDev\CPlot\listener\EntityTrampleFarmlandListener;
use ColinHDev\CPlot\listener\PlayerDropItemListener;
use ColinHDev\CPlot\listener\PlayerInteractListener;
use ColinHDev\CPlot\listener\PlayerMoveListener;
use ColinHDev\CPlot\listener\PlayerLoginListener;
use ColinHDev\CPlot\listener\StructureGrowListener;
use ColinHDev\CPlot\provider\CEconomyProvider;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\tasks\EntityMovementTask;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use ColinHDev\CPlot\worlds\PlotGenerator;
use ColinHDev\CPlot\worlds\SchematicGenerator;

class CPlot extends PluginBase {

    private static CPlot $instance;

    public static function getInstance() : CPlot {
        return self::$instance;
    }

    private ?EconomyProvider $economyProvider;

    public function getEconomyProvider() : ?EconomyProvider {
        return $this->economyProvider;
    }

    public function onLoad() : void {
        self::$instance = $this;

        $config = ResourceManager::getInstance()->getConfig();
        switch (strtolower($config->getNested("economy.provider", ""))) {
            case "ceconomy":
                $this->economyProvider = new CEconomyProvider($config->get("economy", []));
                break;
            default:
                $this->economyProvider = null;
                break;
        }

        GeneratorManager::getInstance()->addGenerator(PlotGenerator::class, PlotGenerator::GENERATOR_NAME, fn() => null, true);
        GeneratorManager::getInstance()->addGenerator(SchematicGenerator::class, SchematicGenerator::GENERATOR_NAME, fn() => null, true);
    }

    public function onEnable() : void {
        $this->getScheduler()->scheduleRepeatingTask(new EntityMovementTask(), 1);

        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockBurningListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockGrowListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockPlaceListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockSpreadListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityDamageByEntityListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityExplodeListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityItemPickupListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityTrampleFarmlandListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerDropItemListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerMoveListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerLoginListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new StructureGrowListener(), $this);

        $this->getServer()->getCommandMap()->register("plot", new PlotCommand());
    }
}