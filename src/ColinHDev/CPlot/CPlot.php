<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use ColinHDev\CPlot\commands\PlotCommand;
use ColinHDev\CPlot\listener\BlockBreakListener;
use ColinHDev\CPlot\listener\BlockBurnListener;
use ColinHDev\CPlot\listener\BlockFormListener;
use ColinHDev\CPlot\listener\BlockGrowListener;
use ColinHDev\CPlot\listener\BlockPlaceListener;
use ColinHDev\CPlot\listener\BlockSpreadListener;
use ColinHDev\CPlot\listener\BlockTeleportListener;
use ColinHDev\CPlot\listener\BlockUpdateListener;
use ColinHDev\CPlot\listener\ChunkPopulateListener;
use ColinHDev\CPlot\listener\EntityDamageByEntityListener;
use ColinHDev\CPlot\listener\EntityExplodeListener;
use ColinHDev\CPlot\listener\EntityItemPickupListener;
use ColinHDev\CPlot\listener\EntityShootBowListener;
use ColinHDev\CPlot\listener\EntityTrampleFarmlandListener;
use ColinHDev\CPlot\listener\PlayerBucketEmptyListener;
use ColinHDev\CPlot\listener\PlayerDropItemListener;
use ColinHDev\CPlot\listener\PlayerInteractListener;
use ColinHDev\CPlot\listener\PlayerLoginListener;
use ColinHDev\CPlot\listener\PlayerMoveListener;
use ColinHDev\CPlot\listener\ProjectileLaunchListener;
use ColinHDev\CPlot\listener\StructureGrowListener;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\tasks\EntityMovementTask;
use ColinHDev\CPlot\worlds\generator\PlotGenerator;
use ColinHDev\CPlot\worlds\generator\SchematicGenerator;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\generator\GeneratorManager;

class CPlot extends PluginBase {
    use SingletonTrait;

    public function onLoad() : void {
        self::setInstance($this);
    }

    public function onEnable() : void {
        ResourceManager::getInstance();
        DataProvider::getInstance();
        EconomyManager::getInstance();

        $generatorManager = GeneratorManager::getInstance();
        $generatorManager->addGenerator(PlotGenerator::class, PlotGenerator::GENERATOR_NAME, fn() => null, true);
        $generatorManager->addGenerator(SchematicGenerator::class, SchematicGenerator::GENERATOR_NAME, fn() => null, true);

        $this->getScheduler()->scheduleRepeatingTask(new EntityMovementTask(), 1);

        $server = $this->getServer();
        $pluginManager = $server->getPluginManager();
        $pluginManager->registerEvents(new BlockBreakListener(), $this);
        $pluginManager->registerEvents(new BlockBurnListener(), $this);
        $pluginManager->registerEvents(new BlockFormListener(), $this);
        $pluginManager->registerEvents(new BlockGrowListener(), $this);
        $pluginManager->registerEvents(new BlockPlaceListener(), $this);
        $pluginManager->registerEvents(new BlockSpreadListener(), $this);
        $pluginManager->registerEvents(new BlockTeleportListener(), $this);
        $pluginManager->registerEvents(new BlockUpdateListener(), $this);
        $pluginManager->registerEvents(new ChunkPopulateListener(), $this);
        $pluginManager->registerEvents(new EntityDamageByEntityListener(), $this);
        $pluginManager->registerEvents(new EntityExplodeListener(), $this);
        $pluginManager->registerEvents(new EntityItemPickupListener(), $this);
        $pluginManager->registerEvents(new EntityShootBowListener(), $this);
        $pluginManager->registerEvents(new EntityTrampleFarmlandListener(), $this);
        $pluginManager->registerEvents(new PlayerBucketEmptyListener(), $this);
        $pluginManager->registerEvents(new PlayerDropItemListener(), $this);
        $pluginManager->registerEvents(new PlayerInteractListener(), $this);
        $pluginManager->registerEvents(new PlayerMoveListener(), $this);
        $pluginManager->registerEvents(new PlayerLoginListener(), $this);
        $pluginManager->registerEvents(new ProjectileLaunchListener(), $this);
        $pluginManager->registerEvents(new StructureGrowListener(), $this);

        $server->getCommandMap()->register("CPlot", new PlotCommand());
    }
}