<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use Closure;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\plugin\ApiVersion;
use pocketmine\utils\VersionString;
use pocketmine\world\World;

final class CPlotAPI {

    public const API_VERSION = "1.0.0";

    private static ?CPlotAPI $instance = null;

    /**
     * @throws \InvalidArgumentException
     */
    public static function getInstance(string $requestedAPI) : self {
        if (!VersionString::isValidBaseVersion($requestedAPI)) {
            throw new \InvalidArgumentException(
                "Invalid API version \"" . $requestedAPI . "\", should contain at least three version digits in the form MAJOR.MINOR.PATCH"
            );
        }
        if (!ApiVersion::isCompatible(self::API_VERSION, [$requestedAPI])) {
            throw new \InvalidArgumentException(
                "Requested API version \"" . $requestedAPI . "\" is not compatible with this plugin's current API version \"" . self::API_VERSION . "\""
            );
        }
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Checks if a given {@see World} is one of CPlot's plot worlds with {@see WorldSettings} associated to it or not.
     *
     * @param World $world The world to check
     *
     * If data about the world is cached, the $onSuccess function is called immediately, while also letting the method
     * return either true or false.
     * If no data about the world is cached, it needs to be asychronously loaded from the database. Once this is done,
     * the $onSuccess function is called and the result cached for the next call of this method.
     *
     * @param Closure|null $onSuccess The callback function to call if the data about the world is cached or loaded
     *                                successfully and it is figured out whether the world is a plot world or not.
     * @phpstan-param (\Closure(bool):void)|null $onSuccess
     *
     * @param Closure|null $onError The callback function to call if something went wrong during the loading of the
     *                              data from the database.
     * @phpstan-param (Closure():void)|(Closure(Throwable):void)|null $onError
     *
     * Returns true if the world is a plot world, false otherwise, or null if there is no cached data about the world,
     * which could synchronously be gotten.
     * @return bool|null
     */
    public function isPlotWorld(World $world, ?Closure $onSuccess = null, ?Closure $onError = null) : ?bool {
        $worldSettings = DataProvider::getInstance()->getOrLoadWorldSettings(
            $world->getFolderName(),
            static function(WorldSettings|NonWorldSettings $worldSettings) use($onSuccess) : void {
                if ($onSuccess !== null) {
                    $onSuccess($worldSettings instanceof WorldSettings);
                }
            },
            $onError
        );
        if ($worldSettings instanceof WorldSettings) {
            return true;
        }
        if ($worldSettings instanceof NonWorldSettings) {
            return false;
        }
        return null;
    }

    /**
     * Get the {@see WorldSettings} associated to a given {@see World}.
     *
     * @param World $world The world to get the {@see WorldSettings} of
     *
     * If data about the world is cached, the $onSuccess function is called immediately, while also letting the method
     * return either the {@see WorldSettings} or false.
     * If no data about the world is cached, it needs to be asychronously loaded from the database. Once this is done,
     * the $onSuccess function is called and the result cached for the next call of this method.
     *
     * @param Closure|null $onSuccess The callback function to call if the data about the world is cached or loaded
     *                                successfully.
     * @phpstan-param (Closure(WorldSettings|false):void)|null $onSuccess
     *
     * @param Closure|null $onError The callback function to call if something went wrong during the loading of the
     *                              data from the database.
     * @phpstan-param (Closure():void)|(Closure(Throwable):void)|null $onError
     *
     * Returns the {@see WorldSettings} associated to the given world, false if the world is not a plot world, or null
     * if there is no cached data about the world, which could synchronously be gotten.
     * @return WorldSettings|false|null
     */
    public function getOrLoadWorldSettings(World $world, ?Closure $onSuccess = null, ?Closure $onError = null) : WorldSettings|false|null {
        $worldSettings = DataProvider::getInstance()->getOrLoadWorldSettings(
            $world->getFolderName(),
            static function(WorldSettings|NonWorldSettings $worldSettings) use($onSuccess) : void {
                if ($onSuccess !== null) {
                    $onSuccess($worldSettings instanceof WorldSettings ? $worldSettings : false);
                }
            },
            $onError
        );
        if ($worldSettings instanceof WorldSettings) {
            return $worldSettings;
        }
        if ($worldSettings instanceof NonWorldSettings) {
            return false;
        }
        return null;
    }

    /**
     * Get the {@see Plot} in the given world with the given coordinates.
     *
     * @param World $world The world the plot is in
     * @param int $x The X coordinate of the plot
     * @param int $z The Z coordinate of the plot
     *
     * If data about the plot is cached, the $onSuccess function is called immediately, while also letting the method
     * return either the {@see Plot} or false.
     * If no data about the plot is cached, it needs to be asychronously loaded from the database. Once this is done,
     * the $onSuccess function is called and the result cached for the next call of this method.
     *
     * @param Closure|null $onSuccess The callback function to call if the data about the plot is cached or loaded
     *                                successfully.
     * @phpstan-param (Closure(Plot|false):void)|null $onSuccess
     *
     * @param Closure|null $onError The callback function to call if something went wrong during the loading of the
     *                              data from the database.
     * @phpstan-param (Closure():void)|(Closure(Throwable):void)|null $onError
     *
     * Returns the {@see Plot} in the given world with the given coordinates, false if the world is not a plot world or
     * there is no plot to get, or null if there is no cached data about the plot, which could synchronously be gotten.
     * @return Plot|false|null
     */
    public function getOrLoadPlot(World $world, int $x, int $z, ?Closure $onSuccess = null, ?Closure $onError = null) : Plot|false|null {
        $worldSettings = $this->getOrLoadWorldSettings(
            $world,
            static function(WorldSettings|false $worldSettings) use($world, $x, $z, $onSuccess, $onError) : void {
                if ($worldSettings === false) {
                    if ($onSuccess !== null) {
                        $onSuccess(false);
                    }
                    return;
                }
                DataProvider::getInstance()->getOrLoadMergeOrigin(new BasePlot($world->getFolderName(), $worldSettings, $x, $z),
                    static function(Plot|null $plot) use($onSuccess) : void {
                        if ($onSuccess !== null) {
                            $onSuccess($plot instanceof Plot ? $plot : false);
                        }
                    },
                    $onError
                );
            },
            $onError
        );
        if (!($worldSettings instanceof WorldSettings)) {
            return $worldSettings === false ? false : null;
        }
        return DataProvider::getInstance()->getOrLoadMergeOrigin(new BasePlot($world->getFolderName(), $worldSettings, $x, $z),
            static function(Plot|null $plot) use($onSuccess) : void {
                if ($onSuccess !== null) {
                    $onSuccess($plot instanceof Plot ? $plot : false);
                }
            },
            $onError
        );
    }
}