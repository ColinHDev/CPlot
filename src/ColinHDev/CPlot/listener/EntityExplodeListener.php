<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\tasks\async\EntityExplodeAsyncTask;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\block\BlockFactory;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\world\Explosion;
use pocketmine\world\World;

class EntityExplodeListener implements Listener {

    public function onEntityExplode(EntityExplodeEvent $event) : void {
        if ($event->isCancelled()) return;
        if (count($event->getBlockList()) === 0) return;

        $position = $event->getPosition();
        $world = $position->getWorld();
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($world->getFolderName());
        if ($worldSettings === null) return;

        $event->cancel();
        $plot = Plot::fromPosition($position);
        if ($plot === null) return;
        if (!$plot->loadFlags()) return;
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_EXPLOSION);
        if ($flag === null || $flag->getValue() === false) return;
        if (!$plot->loadMergedPlots()) return;

        $affectedBlocks = [];
        foreach ($event->getBlockList() as $block) {
            $position = $block->getPosition();
            $affectedBlocks[World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ())] = $block->getFullId();
        }
        $task = new EntityExplodeAsyncTask($worldSettings, $plot, $affectedBlocks);
        //$yield = (1 / $this->size) * 100;
        $size = 100 / $event->getYield();
        $plots = array_map(
            function (BasePlot $plot) : string {
                return $plot->toSmallString();
            },
            array_merge([$plot], $plot->getMergedPlots())
        );
        $plotString = (count($plots) > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "].";
        $task->setClosure(
            function (int $elapsedTime, string $elapsedTimeString, array $result) use ($position, $size, $plotString) {
                /** @var $affectedBlocks int[] */
                /** @var $oldAffectedBlocksCount int */
                /** @var $affectedBlocksCount int */
                [$affectedBlocks, $oldAffectedBlocksCount, $affectedBlocksCount] = $result;

                $world = $position->getWorld();
                $explosion = new Explosion($position, $size);
                $newAffectedBlocks = [];
                foreach ($affectedBlocks as $positionHash => $fullId) {
                    $block = BlockFactory::getInstance()->fromFullBlock($fullId);
                    World::getBlockXYZ($positionHash, $positionX, $positionY, $positionZ);
                    $block->position($world, $positionX, $positionY, $positionZ);
                    $newAffectedBlocks[] = $block;
                }
                $explosion->affectedBlocks = $newAffectedBlocks;
                // result can be ignored because Explosion::explodeB() only returns false if the EntityExplodeEvent is called,
                // which isn't because we don't declare an entity in the explosion's constructor
                $explosion->explodeB();

                Server::getInstance()->getLogger()->debug(
                    "Calculating explosion in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for: " . $oldAffectedBlocksCount . " blocks to " . $affectedBlocksCount . " blocks for plot" . $plotString . "."
                );
            }
        );
        CPlot::getInstance()->getServer()->getAsyncPool()->submitTask($task);
    }
}