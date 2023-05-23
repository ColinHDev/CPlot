<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\generator\MyPlotGenerator;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use SOFe\AwaitGenerator\Await;

class MyPlotConversionListener implements Listener{

    public function onWorldLoad(WorldLoadEvent $event) : void {
        $world = $event->getWorld();
        if ($world->getProvider()->getWorldData()->getGenerator() !== MyPlotGenerator::GENERATOR_NAME) {
            return;
        }
        Await::f2c(
            static function() use($world) : Generator {
                $worldSettings = yield from DataProvider::getInstance()->awaitWorld($world->getFolderName());
                if ($worldSettings !== false) {
                    return;
                }
                yield from DataProvider::getInstance()->importMyPlotData($world);
            }
        );
    }
}