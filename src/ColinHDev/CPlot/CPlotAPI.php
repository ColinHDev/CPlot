<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use Closure;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\ApiVersion;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\VersionString;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use function abs;
use function ceil;
use function count;
use function floor;
use function is_array;

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

    /**
     * Get the {@see Plot} at the given {@see Position}.
     *
     * @param Position $position The position to get the plot of
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
     * Returns the {@see Plot} at the given {@see Position}, false if there is no plot there, or null if there is no
     * cached data about the plot, which could synchronously be gotten.
     * @return Plot|false|null
     *
     * @throws RuntimeException when called outside of main thread.
     * @throws AssumptionFailedError if given a {@see Position} with an invalid or unloaded {@see World}.
     */
    public function getOrLoadPlotAtPosition(Position $position, ?Closure $onSuccess = null, ?Closure $onError = null) : Plot|false|null {
        $world = $position->getWorld();
        $worldSettings = $this->getOrLoadWorldSettings(
            $world,
            function(WorldSettings|false $worldSettings) use($position, $world, $onSuccess, $onError) : void {
                if ($worldSettings === false) {
                    if ($onSuccess !== null) {
                        $onSuccess(false);
                    }
                    return;
                }
                $worldName = $world->getFolderName();
                $basePlot = $this->getBasePlotAtPoint($worldName, $worldSettings, $position);
                if ($basePlot instanceof BasePlot) {
                    $this->getOrLoadPlot($world, $basePlot->getX(), $basePlot->getZ(), $onSuccess, $onError);
                    return;
                }
                $roadSize = $worldSettings->getRoadSize();
                $basePlotInNorth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::NORTH, $roadSize));
                $basePlotInSouth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::SOUTH, $roadSize));
                if ($basePlotInNorth instanceof BasePlot && $basePlotInSouth instanceof BasePlot) {
                    $this->getOrLoadPlotsFromBasePlots(
                        [$basePlotInNorth, $basePlotInSouth],
                        static function(array $plots) use($onSuccess) : void {
                            [$plotInNorth, $plotInSouth] = $plots;
                            if ($plotInNorth instanceof Plot && $plotInSouth instanceof Plot && $plotInNorth->isSame($plotInSouth)) {
                                $plot = $plotInNorth;
                            } else {
                                $plot = false;
                            }
                            if ($onSuccess !== null) {
                                $onSuccess($plot);
                            }
                        },
                        $onError
                    );
                    return;
                }
                $basePlotInWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::WEST, $roadSize));
                $basePlotInEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::EAST, $roadSize));
                if ($basePlotInWest instanceof BasePlot && $basePlotInEast instanceof BasePlot) {
                    $this->getOrLoadPlotsFromBasePlots(
                        [$basePlotInWest, $basePlotInEast],
                        static function(array $plots) use($onSuccess) : void {
                            [$plotInWest, $plotInEast] = $plots;
                            if ($plotInWest instanceof Plot && $plotInEast instanceof Plot && $plotInWest->isSame($plotInEast)) {
                                $plot = $plotInWest;
                            } else {
                                $plot = false;
                            }
                            if ($onSuccess !== null) {
                                $onSuccess($plot);
                            }
                        },
                        $onError
                    );
                    return;
                }
                $basePlotInNorthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, - $roadSize));
                $basePlotInNorthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, - $roadSize));
                $basePlotInSouthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, $roadSize));
                $basePlotInSouthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, $roadSize));
                if ($basePlotInNorthWest instanceof BasePlot && $basePlotInNorthEast instanceof BasePlot && $basePlotInSouthWest instanceof BasePlot && $basePlotInSouthEast instanceof BasePlot) {
                    $this->getOrLoadPlotsFromBasePlots(
                        [$basePlotInNorthWest, $basePlotInNorthEast, $basePlotInSouthWest, $basePlotInSouthEast],
                        static function(array $plots) use($onSuccess) : void {
                            [$plotInNorthWest, $plotInNorthEast, $plotInSouthWest, $plotInSouthEast] = $plots;
                            if (
                                $plotInNorthWest instanceof Plot && $plotInNorthEast instanceof Plot && $plotInSouthWest instanceof Plot && $plotInSouthEast instanceof Plot &&
                                $plotInNorthWest->isSame($plotInNorthEast) && $plotInNorthWest->isSame($plotInSouthWest) && $plotInNorthWest->isSame($plotInSouthEast)
                            ) {
                                $plot = $plotInNorthWest;
                            } else {
                                $plot = false;
                            }
                            if ($onSuccess !== null) {
                                $onSuccess($plot);
                            }
                        },
                        $onError
                    );
                    return;
                }
            },
            $onError
        );
        if (!($worldSettings instanceof WorldSettings)) {
            return $worldSettings === false ? false : null;
        }
        $worldName = $world->getFolderName();
        $basePlot = $this->getBasePlotAtPoint($worldName, $worldSettings, $position);
        if ($basePlot instanceof BasePlot) {
            return $this->getOrLoadPlot($world, $basePlot->getX(), $basePlot->getZ(), $onSuccess, $onError);
        }
        $roadSize = $worldSettings->getRoadSize();
        $basePlotInNorth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::NORTH, $roadSize));
        $basePlotInSouth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::SOUTH, $roadSize));
        if ($basePlotInNorth instanceof BasePlot && $basePlotInSouth instanceof BasePlot) {
            $plots = $this->getOrLoadPlotsFromBasePlots(
                [$basePlotInNorth, $basePlotInSouth],
                static function(array $plots) use($onSuccess) : void {
                    [$plotInNorth, $plotInSouth] = $plots;
                    if ($plotInNorth instanceof Plot && $plotInSouth instanceof Plot && $plotInNorth->isSame($plotInSouth)) {
                        $plot = $plotInNorth;
                    } else {
                        $plot = false;
                    }
                    if ($onSuccess !== null) {
                        $onSuccess($plot);
                    }
                },
                $onError
            );
            if (is_array($plots)) {
                [$plotInNorth, $plotInSouth] = $plots;
                if ($plotInNorth instanceof Plot && $plotInSouth instanceof Plot && $plotInNorth->isSame($plotInSouth)) {
                    return $plotInNorth;
                }
                return false;
            }
            return null;
        }
        $basePlotInWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::WEST, $roadSize));
        $basePlotInEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::EAST, $roadSize));
        if ($basePlotInWest instanceof BasePlot && $basePlotInEast instanceof BasePlot) {
            $plots = $this->getOrLoadPlotsFromBasePlots(
                [$basePlotInWest, $basePlotInEast],
                static function(array $plots) use($onSuccess) : void {
                    [$plotInWest, $plotInEast] = $plots;
                    if ($plotInWest instanceof Plot && $plotInEast instanceof Plot && $plotInWest->isSame($plotInEast)) {
                        $plot = $plotInWest;
                    } else {
                        $plot = false;
                    }
                    if ($onSuccess !== null) {
                        $onSuccess($plot);
                    }
                },
                $onError
            );
            if (is_array($plots)) {
                [$plotInWest, $plotInEast] = $plots;
                if ($plotInWest instanceof Plot && $plotInEast instanceof Plot && $plotInWest->isSame($plotInEast)) {
                    return $plotInWest;
                }
                return false;
            }
            return null;
        }
        $basePlotInNorthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, - $roadSize));
        $basePlotInNorthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, - $roadSize));
        $basePlotInSouthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, $roadSize));
        $basePlotInSouthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, $roadSize));
        if ($basePlotInNorthWest instanceof BasePlot && $basePlotInNorthEast instanceof BasePlot && $basePlotInSouthWest instanceof BasePlot && $basePlotInSouthEast instanceof BasePlot) {
            $plots = $this->getOrLoadPlotsFromBasePlots(
                [$basePlotInNorthWest, $basePlotInNorthEast, $basePlotInSouthWest, $basePlotInSouthEast],
                static function(array $plots) use($onSuccess) : void {
                    [$plotInNorthWest, $plotInNorthEast, $plotInSouthWest, $plotInSouthEast] = $plots;
                    if (
                        $plotInNorthWest instanceof Plot && $plotInNorthEast instanceof Plot && $plotInSouthWest instanceof Plot && $plotInSouthEast instanceof Plot &&
                        $plotInNorthWest->isSame($plotInNorthEast) && $plotInNorthWest->isSame($plotInSouthWest) && $plotInNorthWest->isSame($plotInSouthEast)
                    ) {
                        $plot = $plotInNorthWest;
                    } else {
                        $plot = false;
                    }
                    if ($onSuccess !== null) {
                        $onSuccess($plot);
                    }
                },
                $onError
            );
            if (is_array($plots)) {
                [$plotInNorthWest, $plotInNorthEast, $plotInSouthWest, $plotInSouthEast] = $plots;
                if (
                    $plotInNorthWest instanceof Plot && $plotInNorthEast instanceof Plot && $plotInSouthWest instanceof Plot && $plotInSouthEast instanceof Plot &&
                    $plotInNorthWest->isSame($plotInNorthEast) && $plotInNorthWest->isSame($plotInSouthWest) && $plotInNorthWest->isSame($plotInSouthEast)
                ) {
                    return $plotInNorthWest;
                }
                return false;
            }
            return null;
        }
        return false;
    }

    /**
     * Get the {@see BasePlot} at the given point in the given world.
     *
     * @param string $worldName The name of the world
     * @param WorldSettings $worldSettings The settings of the world
     * @param Vector3 $point The point to check represented with a Vector3 object
     *
     * Returns the {@see BasePlot} at the given point or false if no base plot exists there.
     * @return BasePlot|false
     */
    public function getBasePlotAtPoint(string $worldName, WorldSettings $worldSettings, Vector3 $point) : BasePlot|false {
        $roadSize = $worldSettings->getRoadSize();
        $plotSize = $worldSettings->getPlotSize();
        $roadPlotSize = $roadSize + $plotSize;
        $x = $point->getFloorX() - $roadSize;
        if ($x >= 0) {
            $X = (int) floor($x / $roadPlotSize);
            $difX = $x % $roadPlotSize;
        } else {
            $X = (int) ceil(($x - $plotSize + 1) / $roadPlotSize);
            $difX = abs(($x - $plotSize + 1) % $roadPlotSize);
        }
        $z = $point->getFloorZ() - $roadSize;
        if ($z >= 0) {
            $Z = (int) floor($z / $roadPlotSize);
            $difZ = $z % $roadPlotSize;
        } else {
            $Z = (int) ceil(($z - $plotSize + 1) / $roadPlotSize);
            $difZ = abs(($z - $plotSize + 1) % $roadPlotSize);
        }
        if (($difX > $plotSize - 1) || ($difZ > $plotSize - 1)) {
            return false;
        }
        return new BasePlot($worldName, $worldSettings, $X, $Z);
    }

    /**
     * Converts the given array of {@see BasePlot}s to an array of corresponding {@see Plot}s.
     *
     * @param array $basePlots The array of {@see BasePlot} objects
     * @phpstan-param non-empty-array<mixed, BasePlot> $basePlots
     *
     * If data about the plots is cached, the $onSuccess function is called immediately, while also letting the method
     * return the array of plot objects.
     * If no data about a plot is cached, it needs to be asychronously loaded from the database. Once this is done,
     * the $onSuccess function is called and the result cached for the next call of this method.
     *
     * @param Closure|null $onSuccess The callback function to call if the data about the plots is cached or loaded
     *                                successfully.
     * @phpstan-param (Closure(non-empty-array<mixed, Plot|false>):void)|null $onSuccess
     *
     * @param Closure|null $onError The callback function to call if something went wrong during the loading of the
     *                              data from the database.
     * @phpstan-param (Closure():void)|(Closure(Throwable):void)|null $onError
     *
     * @param array $plots The array of already converted {@see Plot} objects. This parameter is for internal use only.
     * @phpstan-param array<mixed, Plot|false> $plots
     *
     * Returns the array of {@see Plot}s, or null if there is no cached data about them, which could synchronously be
     * gotten.
     * @return non-empty-array<mixed, Plot|false>|null
     *
     * @throws RuntimeException when called outside of main thread.
     */
    public function getOrLoadPlotsFromBasePlots(array $basePlots, ?Closure $onSuccess = null, ?Closure $onError = null, array $plots = []) : array|null {
        foreach($basePlots as $key => $basePlot) {
            if (isset($plots[$key])) {
                continue;
            }
            $plot = $this->getOrLoadPlot($basePlot->getWorld(), $basePlot->getX(), $basePlot->getZ());
            if ($plot instanceof Plot || $plot === false) {
                $plots[$key] = $plot;
                continue;
            }
            $this->getOrLoadPlot(
                $basePlot->getWorld(), $basePlot->getX(), $basePlot->getZ(),
                function(Plot|false $plot) use($basePlots, $key, $onSuccess, $onError, $plots) : void {
                    $plots[$key] = $plot;
                    $this->getOrLoadPlotsFromBasePlots($basePlots, $onSuccess, $onError, $plots);
                },
                $onError
            );
            break;
        }
        if (count($plots) === count($basePlots)) {
            if ($onSuccess !== null) {
                $onSuccess($plots);
            }
            return $plots;
        }
        return null;
    }

    /**
     * Loads all the {@see Plot}s where the given {@see Player} is an owner of.
     *
     * @param Player $player The player to load the plots of
     *
     * @param Closure $onSuccess The callback function to call if the data about the plots is loaded successfully.
     * @phpstan-param (Closure(array<string, Plot>):void)|null $onSuccess
     *
     * @param Closure $onError The callback function to call if something went wrong during the loading of the data from
     *                         the database.
     * @phpstan-param (Closure():void)|(Closure(Throwable):void)|null $onError
     */
    public function loadPlotsOfPlayer(Player $player, Closure $onSuccess, Closure $onError) : void {
        Await::f2c(
            static function() use($player) : Generator {
                /** @phpstan-var PlayerData|null $playerData */
                $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                if ($playerData === null) {
                    throw new RuntimeException("Player data not found");
                }
                /** @phpstan-var array<string, Plot> $plots */
                $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                return $plots;
            },
            $onSuccess,
            $onError
        );
    }
}