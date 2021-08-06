<?php

namespace ColinHDev\CPlot\tasks;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\WorldManager;

class EntityMovementTask extends Task {

    private WorldManager $worldManager;
    /** @var string[] */
    private array $entitiesToCheck = [
        PrimedTNT::class,
        Arrow::class,
        Egg::class,
        Snowball::class,
    ];
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
                $checkMovement = false;
                foreach ($this->entitiesToCheck as $entityToCheckClassName) {
                    if (!is_a($entity, $entityToCheckClassName)) continue;
                    $checkMovement = true;
                    break;
                }
                if (!$checkMovement) continue;

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

    // old try
    // i'll keep it if I possibly want to rewrite that stuff

    /*private WorldManager $worldManager;
    /** @var string[] *
    private array $entitiesToCheck = [
        PrimedTNT::class,
        Arrow::class,
        Egg::class,
        Snowball::class,
    ];
    /** @var Vector3[][] *
    private array $motions = [];

    public function __construct() {
        $this->worldManager = Server::getInstance()->getWorldManager();
    }

    public function onRun() : void {
        foreach ($this->worldManager->getWorlds() as $world) {

            $worldSettings = CPlot::getInstance()->getProvider()->getWorld($world->getFolderName());
            if ($worldSettings === null) continue;

            $worldId = $world->getId();
            if (!isset($this->motions[$worldId])) {
                $this->motions[$worldId] = [];
            }

            foreach ($world->updateEntities as $entity) {
                $checkMovement = false;
                foreach ($this->entitiesToCheck as $entityToCheckClassName) {
                    if (!is_a($entity, $entityToCheckClassName)) continue;
                    $checkMovement = true;
                    break;
                }
                if (!$checkMovement) continue;

                $entityId = $entity->getId();
                if ($entity->isClosed()) {
                    unset($this->motions[$worldId][$entityId]);
                    continue;
                }

                if (!$entity->hasMovementUpdate()) continue;

                $motion = $entity->getMotion();
                if ($motion->lengthSquared() !== 0.0) {
                    if (!isset($this->motions[$worldId][$entityId])) {
                        $this->motions[$worldId][$entityId] = clone $motion;
                    } else {
                        $d = 0.014;
                        $this->motions[$worldId][$entityId] = $this->motions[$worldId][$entityId]->addVector($motion->normalize()->multiply($d));
                    }
                    $entity->addMotion(
                        - $motion->getX(),
                        - $motion->getY(),
                        - $motion->getZ()
                    );
                }

                if (!isset($this->motions[$worldId][$entityId]) || $this->motions[$worldId][$entityId]->lengthSquared() === 0.0) {
                    unset($this->motions[$worldId][$entityId]);
                    continue;
                }

                $this->motions[$worldId][$entityId] = $this->motions[$worldId][$entityId]->withComponents(
                    abs($this->motions[$worldId][$entityId]->x) <= 0.00001 ? 0 : null,
                    abs($this->motions[$worldId][$entityId]->y) <= 0.00001 ? 0 : null,
                    abs($this->motions[$worldId][$entityId]->z) <= 0.00001 ? 0 : null
                );

                $this->moveEntity(
                    $entity,
                    $this->motions[$worldId][$entityId]->x,
                    $this->motions[$worldId][$entityId]->y,
                    $this->motions[$worldId][$entityId]->z
                );
            }
        }
    }

    private function moveEntity(Entity $entity, float $dx, float $dy, float $dz) : void{
        Timings::$entityMove->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $property = new ReflectionProperty(Entity::class, "ySize");
        $property->setAccessible(true);
        $ySize = $property->getValue($entity);

        if($entity->keepMovement){
            $entity->boundingBox->offset($dx, $dy, $dz);
        }else{
            $ySize *= 0.4;

            /*
            if($this->isColliding){ //With cobweb?
                $this->isColliding = false;
                $dx *= 0.25;
                $dy *= 0.05;
                $dz *= 0.25;
                $this->motionX = 0;
                $this->motionY = 0;
                $this->motionZ = 0;
            }
            *

            $moveBB = clone $entity->boundingBox;

            /*$sneakFlag = $this->onGround and $this instanceof Player;

            if($sneakFlag){
                for($mov = 0.05; $dx != 0.0 and count($this->world->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox($dx, -1, 0))) === 0; $movX = $dx){
                    if($dx < $mov and $dx >= -$mov){
                        $dx = 0;
                    }elseif($dx > 0){
                        $dx -= $mov;
                    }else{
                        $dx += $mov;
                    }
                }

                for(; $dz != 0.0 and count($this->world->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox(0, -1, $dz))) === 0; $movZ = $dz){
                    if($dz < $mov and $dz >= -$mov){
                        $dz = 0;
                    }elseif($dz > 0){
                        $dz -= $mov;
                    }else{
                        $dz += $mov;
                    }
                }

                //TODO: big messy loop
            }*

            assert(abs($dx) <= 20 and abs($dy) <= 20 and abs($dz) <= 20, "Movement distance is excessive: dx=$dx, dy=$dy, dz=$dz");

            $list = $entity->getWorld()->getCollisionBoxes($entity, $moveBB->addCoord($dx, $dy, $dz), false);

            foreach($list as $bb){
                $dy = $bb->calculateYOffset($moveBB, $dy);
            }

            $moveBB->offset(0, $dy, 0);

            $fallingFlag = ($entity->onGround or ($dy != $movY and $movY < 0));

            foreach($list as $bb){
                $dx = $bb->calculateXOffset($moveBB, $dx);
            }

            $moveBB->offset($dx, 0, 0);

            foreach($list as $bb){
                $dz = $bb->calculateZOffset($moveBB, $dz);
            }

            $moveBB->offset(0, 0, $dz);

            $stepHeightProperty = new ReflectionProperty(Entity::class, "stepHeight");
            $stepHeightProperty->setAccessible(true);
            $stepHeight = $stepHeightProperty->getValue($entity);
            if($stepHeight > 0 and $fallingFlag and ($movX != $dx or $movZ != $dz)){
                $cx = $dx;
                $cy = $dy;
                $cz = $dz;
                $dx = $movX;
                $dy = $stepHeight;
                $dz = $movZ;

                $stepBB = clone $entity->boundingBox;

                $list = $entity->getWorld()->getCollisionBoxes($entity, $stepBB->addCoord($dx, $dy, $dz), false);
                foreach($list as $bb){
                    $dy = $bb->calculateYOffset($stepBB, $dy);
                }

                $stepBB->offset(0, $dy, 0);

                foreach($list as $bb){
                    $dx = $bb->calculateXOffset($stepBB, $dx);
                }

                $stepBB->offset($dx, 0, 0);

                foreach($list as $bb){
                    $dz = $bb->calculateZOffset($stepBB, $dz);
                }

                $stepBB->offset(0, 0, $dz);

                $reverseDY = -$dy;
                foreach($list as $bb){
                    $reverseDY = $bb->calculateYOffset($stepBB, $reverseDY);
                }
                $dy += $reverseDY;
                $stepBB->offset(0, $reverseDY, 0);

                if(($cx ** 2 + $cz ** 2) >= ($dx ** 2 + $dz ** 2)){
                    $dx = $cx;
                    $dy = $cy;
                    $dz = $cz;
                }else{
                    $moveBB = $stepBB;
                    $ySize += $dy;
                }
            }

            $property->setValue($entity, $ySize);

            $entity->boundingBox = $moveBB;
        }

        $property = new ReflectionProperty(Entity::class, "location");
        $property->setAccessible(true);
        $oldLocation = $property->getValue($entity);

        $location = new Location(
            ($entity->boundingBox->minX + $entity->boundingBox->maxX) / 2,
            $entity->boundingBox->minY - $ySize,
            ($entity->boundingBox->minZ + $entity->boundingBox->maxZ) / 2,
            $oldLocation->yaw,
            $oldLocation->pitch,
            $oldLocation->world
        );
        $property->setValue($entity, $location);

        $entity->getWorld()->onEntityMoved($entity);
        // $entity->checkBlockCollision();
        $function = new ReflectionMethod($entity, "checkBlockCollision");
        $function->getClosure($entity)();
        // $entity->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $function = new ReflectionMethod($entity, "checkGroundState");
        $function->getClosure($entity)($movX, $movY, $movZ, $dx, $dy, $dz);
        // $entity->updateFallState($dy, $entity->onGround);
        $function = new ReflectionMethod($entity, "updateFallState");
        $function->getClosure($entity)($dy, $entity->onGround);

        $this->motions[$entity->getWorld()->getId()][$entity->getId()] = $this->motions[$entity->getWorld()->getId()][$entity->getId()]->withComponents(
            $movX != $dx ? 0 : null,
            $movY != $dy ? 0 : null,
            $movZ != $dz ? 0 : null
        );

        //TODO: vehicle collision events (first we need to spawn them!)

        Timings::$entityMove->stopTiming();
    }

    /**
     * @return AxisAlignedBB[]
     *
    public function getCollisionBoxes(Entity $entity, AxisAlignedBB $bb) : array{
        $minX = (int) floor($bb->minX - 1);
        $minY = (int) floor($bb->minY - 1);
        $minZ = (int) floor($bb->minZ - 1);
        $maxX = (int) floor($bb->maxX + 1);
        $maxY = (int) floor($bb->maxY + 1);
        $maxZ = (int) floor($bb->maxZ + 1);

        $collides = [];

        for($z = $minZ; $z <= $maxZ; ++$z){
            for($x = $minX; $x <= $maxX; ++$x){
                for($y = $minY; $y <= $maxY; ++$y){
                    $block = $entity->getWorld()->getBlockAt($x, $y, $z);
                    foreach($block->getCollisionBoxes() as $blockBB){
                        if($blockBB->intersectsWith($bb)){
                            $collides[] = $blockBB;
                        }
                    }
                }
            }
        }

        return $collides;
    }*/
}