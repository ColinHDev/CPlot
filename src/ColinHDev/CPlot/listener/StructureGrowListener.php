<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\attributes\BooleanAttribute;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class StructureGrowListener implements Listener {

    public function onStructureGrow(StructureGrowEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        $world = $position->getWorld();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($world->getFolderName());
        if ($worldSettings === null) {
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            $event->cancel();
            return;
        }
        if ($plot !== null) {
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
            if ($flag->getValue() === true) {
                $transaction = $event->getTransaction();
                foreach ($transaction->getBlocks() as [$x, $y, $z, $block]) {
                    $plotAtPosition = Plot::loadFromPositionIntoCache(new Position($x, $y, $z, $world));
                    if ($plotAtPosition instanceof Plot && $plotAtPosition->isSame($plot)) {
                        continue;
                    }
                    $transaction->addBlockAt($x, $y, $z, $world->getBlockAt($x, $y, $z));
                }
                return;
            }
        }

        $event->cancel();
    }
}