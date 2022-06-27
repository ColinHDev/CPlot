<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
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
                if ($entity instanceof Player) {
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
                $position = ($this->lastPositions[$entityId] = $entity->getLocation());
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
                if ($lastBasePlot instanceof BasePlot && $basePlot instanceof BasePlot && $lastBasePlot->isSame($basePlot)) {
                    continue;
                }

                $lastPlot = $lastBasePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache($lastPosition);
                $plot = $basePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache($position);
                if ($lastPlot instanceof Plot && $plot instanceof Plot && $lastPlot->isSame($plot)) {
                    continue;
                }
                // If the entity's last position was not on a plot, the entity can be removed, as there is no longer an
                // origin plot associated with it.
                if ($lastBasePlot === null && $lastPlot === null) {
                    $entity->flagForDespawn();
                    continue;
                }
                // Either if the entity actually left the plot or the plot could not be correctly fetched, its movement
                // needs to be reversed, so the entity does not actually leave a plot without the plugin
                // noticing / acting accordingly.
                $this->lastPositions[$entityId] = $lastPosition;
                $entity->teleport($lastPosition);
                // If the entity's origin or current plot could not be correctly fetched, we can not perform any
                // actions on that entity.
                if (($lastPlot instanceof BasePlot && !($lastPlot instanceof Plot)) || ($plot instanceof BasePlot && !($plot instanceof Plot))) {
                    continue;
                }

                $this->knockBackEntity(
                    $entity,
                    $lastPosition->x - $position->x,
                    $lastPosition->z - $position->z,
                    0.25
                );
            }
        }
    }

    private function knockBackEntity(Entity $entity, float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4) : void {
        $f = sqrt($x * $x + $z * $z);
        if ($f <= 0) {
            return;
        }
        if (mt_rand() / mt_getrandmax() > ($entity->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue() ?? -1.0)) {
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