<?php

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\entity\Human;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\WorldManager;

class EntityMovementTask extends Task {

    private WorldManager $worldManager;
    /** @var Vector3[][] */
    private array $lastVector = [];

    public function __construct() {
        $this->worldManager = Server::getInstance()->getWorldManager();
    }

    public function onRun() : void {
        foreach ($this->worldManager->getWorlds() as $world) {

            $worldSettings = CPlot::getInstance()->getProvider()->getWorld($world->getFolderName());
            if ($worldSettings === null) continue;

            $worldId = $world->getId();
            if (!isset($this->lastVector[$worldId])) {
                $this->lastVector[$worldId] = [];
            }

            foreach ($world->updateEntities as $entity) {
                if ($entity instanceof Human || $entity instanceof ItemEntity) {
                    continue;
                }

                $entityId = $entity->getId();
                if ($entity->isClosed()) {
                    unset($this->lastVector[$worldId][$entityId]);
                    continue;
                }

                if (!$entity->hasMovementUpdate()) continue;

                $position = $entity->getPosition();
                if (!isset($this->lastVector[$worldId][$entityId])) {
                    $this->lastVector[$worldId][$entityId] = $position->asVector3();
                    continue;
                }

                $lastPosition = Position::fromObject($this->lastVector[$worldId][$entityId], $world);
                if ($lastPosition->equals($position)) continue;

                $this->lastVector[$worldId][$entityId] = $position->asVector3();
                $lastBasePlot = BasePlot::fromPosition($lastPosition);
                $basePlot = BasePlot::fromPosition($position);
                if ($lastBasePlot !== null && $basePlot !== null && $lastBasePlot->isSame($basePlot)) continue;

                $lastPlot = $lastBasePlot?->toPlot() ?? Plot::fromPosition($lastPosition);
                $plot = $basePlot?->toPlot() ?? Plot::fromPosition($position);
                if ($lastPlot !== null && $plot !== null && $lastPlot->isSame($plot)) continue;

                $entity->flagForDespawn();
            }
        }
    }
}