<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;

class EntityExplodeListener implements Listener {

    /**
     * @handleCancelled false
     */
    public function onEntityExplode(EntityExplodeEvent $event) : void {
        if (count($event->getBlockList()) === 0) {
            return;
        }

        $position = $event->getPosition();
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
        if ($plot instanceof Plot) {
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_EXPLOSION);
            if ($flag->getValue() === true) {
                $affectedBlocks = [];
                foreach ($event->getBlockList() as $hash => $block) {
                    if ($plot->isOnPlot($block->getPosition())) {
                        $affectedBlocks[$hash] = $block;
                    }
                }
                $event->setBlockList($affectedBlocks);
                return;
            }
        }

        $event->cancel();
    }
}