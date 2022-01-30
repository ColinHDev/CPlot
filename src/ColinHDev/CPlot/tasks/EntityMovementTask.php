<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\entity\Human;
use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\WorldManager;

class EntityMovementTask extends Task {

    private WorldManager $worldManager;
    /** @var Position[] */
    private array $lastPositions = [];

    public function __construct() {
        $this->worldManager = Server::getInstance()->getWorldManager();
    }

    public function onRun() : void {
        foreach ($this->worldManager->getWorlds() as $world) {
            $worldName = $world->getFolderName();
            $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($worldName);
            if (!$worldSettings instanceof WorldSettings) {
                return;
            }

            foreach ($world->updateEntities as $entity) {
                if ($entity instanceof Human || $entity instanceof ItemEntity) {
                    continue;
                }

                $entityId = $entity->getId();
                if ($entity->isClosed()) {
                    unset($this->lastPositions[$entityId]);
                    continue;
                }

                if (!$entity->hasMovementUpdate()) {
                    continue;
                }

                if (!isset($this->lastPositions[$entityId])) {
                    $this->lastPositions[$entityId] = $entity->getPosition();
                    continue;
                }

                $lastPosition = $this->lastPositions[$entityId];
                $position = $this->lastPositions[$entityId] = $entity->getPosition();
                if (
                    // Only if the world did not change, e.g. due to a teleport, we need to check how far the entity moved.
                    $position->world === $lastPosition->world &&
                    // Check if the entity moved across a block and if not, we already checked that block and the entity just
                    // moved in the borders between that one.
                    $position->getFloorX() === $lastPosition->getFloorX() &&
                    $position->getFloorY() === $lastPosition->getFloorY() &&
                    $position->getFloorZ() === $lastPosition->getFloorZ()
                ) {
                    return;
                }

                $lastBasePlot = BasePlot::fromVector3($worldName, $worldSettings, $lastPosition);
                $basePlot = BasePlot::fromVector3($worldName, $worldSettings, $position);
                if ($lastBasePlot !== null && $basePlot !== null && $lastBasePlot->isSame($basePlot)) {
                    continue;
                }

                $lastPlot = $lastBasePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache($lastPosition);
                $plot = $basePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache($position);
                if ($lastPlot instanceof Plot && $plot instanceof Plot && $lastPlot->isSame($plot)) {
                    continue;
                }
                if ($lastPlot instanceof BasePlot || $plot instanceof BasePlot) {
                    continue;
                }

                $entity->flagForDespawn();
            }
        }
    }
}