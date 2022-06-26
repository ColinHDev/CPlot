<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\WorldManager;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;

class EntityMovementTask extends Task {

    private WorldManager $worldManager;
    /** @var Location[] */
    private array $lastPositions = [];

    public function __construct() {
        $this->worldManager = Server::getInstance()->getWorldManager();
    }

    public function onRun() : void {
        if (!DataProvider::getInstance()->isInitialized()) {
            return;
        }
        foreach ($this->worldManager->getWorlds() as $world) {
            $worldName = $world->getFolderName();
            $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($worldName);
            if (!$worldSettings instanceof WorldSettings) {
                continue;
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
                    $this->lastPositions[$entityId] = $entity->getLocation();
                    continue;
                }

                $lastPosition = $this->lastPositions[$entityId];
                $position = $this->lastPositions[$entityId] = $entity->getLocation();
                if (
                    // Only if the world did not change, e.g. due to a teleport, we need to check how far the entity moved.
                    $position->world === $lastPosition->world &&
                    // Check if the entity moved across a block and if not, we already checked that block and the entity just
                    // moved in the borders between that one.
                    $position->getFloorX() === $lastPosition->getFloorX() &&
                    $position->getFloorY() === $lastPosition->getFloorY() &&
                    $position->getFloorZ() === $lastPosition->getFloorZ()
                ) {
                    continue;
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

                $this->knockBackEntity(
                    $entity,
                    $lastPosition->x - $position->x,
                    $lastPosition->z - $position->z
                );
            }
        }
    }

    private function knockBackEntity(Entity $entity, float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4) : void {
        $f = sqrt($x * $x + $z * $z);
        if ($f <= 0) {
            return;
        }
        if (mt_rand() / mt_getrandmax() > ($entity->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue() ?? -1)) {
            $f = 1 / $f;

            $oldVelocity = $entity->getMotion();
            $motionX = $oldVelocity->x / 2;
            $motionY = $oldVelocity->y / 2;
            $motionZ = $oldVelocity->z / 2;
            $motionX += $x * $f * $force;
            $motionY += $force;
            $motionZ += $z * $f * $force;

            $verticalLimit ??= $force;
            if ($motionY > $verticalLimit) {
                $motionY = $verticalLimit;
            }

            $entity->setMotion(new Vector3($motionX, $motionY, $motionZ));
        }
        return;
    }
}