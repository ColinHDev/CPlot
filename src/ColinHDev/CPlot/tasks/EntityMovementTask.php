<?php

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\entity\Human;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\WorldManager;
use SOFe\AwaitGenerator\Await;

class EntityMovementTask extends Task {

    private WorldManager $worldManager;
    /** @var Position[] */
    private array $lastPositions = [];

    public function __construct() {
        $this->worldManager = Server::getInstance()->getWorldManager();
    }

    public function onRun() : void {
        foreach ($this->worldManager->getWorlds() as $world) {

            Await::f2c(
                function () use ($world) : \Generator {
                    $worldSettings = yield DataProvider::getInstance()->awaitWorld($world->getFolderName());
                    if (!$worldSettings instanceof WorldSettings) return;

                    foreach ($world->updateEntities as $entity) {
                        if ($entity instanceof Human || $entity instanceof ItemEntity) {
                            continue;
                        }

                        $entityId = $entity->getId();
                        if ($entity->isClosed()) {
                            unset($this->lastPositions[$entityId]);
                            continue;
                        }

                        if (!$entity->hasMovementUpdate()) continue;

                        if (!isset($this->lastPositions[$entityId])) {
                            $this->lastPositions[$entityId] = $entity->getPosition();
                            continue;
                        }

                        $position = $entity->getPosition();
                        $lastPosition = $this->lastPositions[$entityId];
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

                        $this->lastPositions[$entityId] = $position->asVector3();
                        $lastBasePlot = yield BasePlot::fromPosition($lastPosition);
                        $basePlot = yield BasePlot::fromPosition($position);
                        if ($lastBasePlot !== null && $basePlot !== null && (yield $lastBasePlot->isSame($basePlot))) continue;

                        $lastPlot = (yield $lastBasePlot?->toPlot()) ?? (yield Plot::fromPosition($lastPosition));
                        $plot = (yield $basePlot?->toPlot()) ?? (yield Plot::fromPosition($position));
                        if ($lastPlot !== null && $plot !== null && (yield $lastPlot->isSame($plot))) continue;

                        $entity->flagForDespawn();
                    }
                }
            );
        }
    }
}