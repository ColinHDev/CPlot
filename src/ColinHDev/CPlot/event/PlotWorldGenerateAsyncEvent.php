<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\worlds\WorldSettings;
use ColinHDev\libAsyncEvent\AsyncEvent;
use ColinHDev\libAsyncEvent\ConsecutiveEventHandlerExecutionTrait;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\world\generator\Generator;
use pocketmine\world\WorldCreationOptions;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when a new plot world is generated.
 * @link https://github.com/ColinHDev/libAsyncEvent/
 * @method void block()
 * @method void release()
 */
class PlotWorldGenerateAsyncEvent extends Event implements AsyncEvent, Cancellable {
    use ConsecutiveEventHandlerExecutionTrait;
    use CancellableTrait;

    private string $worldName;
    private WorldSettings $worldSettings;
    private WorldCreationOptions $worldCreationOptions;

    public function __construct(string $worldName, WorldSettings $worldSettings, WorldCreationOptions $worldCreationOptions) {
        $this->worldName = $worldName;
        $this->worldSettings = $worldSettings;
        $this->worldCreationOptions = $worldCreationOptions;
    }

    /**
     * Returns the name under which the plot world will be generated.
     */
    public function getWorldName() : string {
        return $this->worldName;
    }

    /**
     * Sets the name under which the plot world will be generated.
     */
    public function setWorldName(string $worldName) : void {
        $this->worldName = $worldName;
    }

    /**
     * Returns CPlot's {@see WorldSettings} which's contents will be saved to the database.
     */
    public function getWorldSettings() : WorldSettings {
        return $this->worldSettings;
    }

    /**
     * Sets CPlot's {@see WorldSettings} which's contents will be saved to the database.
     */
    public function setWorldSettings(WorldSettings $worldSettings) : void {
        $this->worldSettings = $worldSettings;
    }

    /**
     * Returns PocketMine-MP's {@see WorldCreationOptions} which's contents will be passed to the {@see Generator} and
     * used to generate the world.
     */
    public function getWorldCreationOptions() : WorldCreationOptions {
        return $this->worldCreationOptions;
    }

    /**
     * Sets PocketMine-MP's {@see WorldCreationOptions} which's contents will be passed to the {@see Generator} and
     * used to generate the world.
     */
    public function setWorldCreationOptions(WorldCreationOptions $worldCreationOptions) : void {
        $this->worldCreationOptions = $worldCreationOptions;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(string $worldName, WorldSettings $worldSettings, WorldCreationOptions $options) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($worldName, $worldSettings, $options) : void {
                $event = new self($worldName, $worldSettings, $options);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}