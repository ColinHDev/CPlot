<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\utils\promise\Promise;
use ColinHDev\CPlot\utils\promise\PromiseResolver;
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
use Throwable;
use function abs;
use function ceil;
use function floor;

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
     * Returns a {@see Promise} that resolves to true if the world is a plot world, or false if it is not.
     * If data about the world was cached, the promise might already be resolved once it is returned by this method.
     * @return Promise
     * @phpstan-return Promise<bool>
     */
    public function isPlotWorld(World $world) : Promise {
        /** @phpstan-var PromiseResolver<bool> $resolver */
        $resolver = new PromiseResolver();
        $worldSettings = DataProvider::getInstance()->getOrLoadWorldSettings(
            $world->getFolderName(),
            static function(WorldSettings|false $worldSettings) use($resolver) : void {
                $resolver->resolveSilent($worldSettings instanceof WorldSettings);
            },
            static function(Throwable $error) use($resolver) : void {
                $resolver->rejectSilent($error);
            }
        );
        if ($worldSettings instanceof WorldSettings) {
            $resolver->resolveSilent(true);
        } else if ($worldSettings === false) {
            $resolver->resolveSilent(false);
        }
        return $resolver->getPromise();
    }

    /**
     * Get the {@see WorldSettings} associated to a given {@see World}.
     *
     * @param World $world The world to get the {@see WorldSettings} of
     *
     * Returns a {@see Promise} that resolves the associated {@see WorldSettings} object of the world, or false if it
     * is not a plot world.
     * If data about the world was cached, the promise might already be resolved once it is returned by this method.
     * @return Promise
     * @phpstan-return Promise<WorldSettings|false>
     */
    public function getOrLoadWorldSettings(World $world) : Promise {
        /** @phpstan-var PromiseResolver<WorldSettings|false> $resolver */
        $resolver = new PromiseResolver();
        $worldSettings = DataProvider::getInstance()->getOrLoadWorldSettings(
            $world->getFolderName(),
            static function(WorldSettings|false $worldSettings) use($resolver) : void {
                $resolver->resolveSilent($worldSettings);
            },
            static function(Throwable $error) use($resolver) : void {
                $resolver->rejectSilent($error);
            }
        );
        if ($worldSettings instanceof WorldSettings) {
            $resolver->resolveSilent($worldSettings);
        } else if ($worldSettings === false) {
            $resolver->resolveSilent(false);
        }
        return $resolver->getPromise();
    }

    /**
     * Get the {@see Plot} in the given {@see World} with the given coordinates.
     *
     * @param World $world The world the plot is in
     * @param int $x The X coordinate of the plot
     * @param int $z The Z coordinate of the plot
     *
     * Returns a {@see Promise} that resolves the requested {@see Plot} object with the given coordinates in the given
     * world, or false if it is not a plot world.
     * If data about the plot was cached, the promise might already be resolved once it is returned by this method.
     * @return Promise
     * @phpstan-return Promise<Plot|false>
     */
    public function getOrLoadPlot(World $world, int $x, int $z) : Promise {
        /** @phpstan-var PromiseResolver<Plot|false> $resolver */
        $resolver = new PromiseResolver();
        $this->getOrLoadWorldSettings($world)->onCompletion(
            static function(WorldSettings|false $worldSettings) use($resolver, $world, $x, $z) : void {
                if ($worldSettings === false) {
                    $resolver->resolveSilent(false);
                    return;
                }
                DataProvider::getInstance()->getOrLoadMergeOrigin(new BasePlot($world->getFolderName(), $worldSettings, $x, $z),
                    static function(Plot|null $plot) use($resolver) : void {
                        $resolver->resolveSilent($plot instanceof Plot ? $plot : false);
                    },
                    static function(Throwable $error) use($resolver) : void {
                        $resolver->rejectSilent($error);
                    }
                );
            },
            static function(Throwable $error) use($resolver) : void {
                $resolver->rejectSilent($error);
            }
        );
        return $resolver->getPromise();
    }

    /**
     * Get the {@see Plot} at the given {@see Position}.
     *
     * @param Position $position The position to get the plot of
     *
     * Returns a {@see Promise} that resolves the requested {@see Plot} object at the given {@see Position}, or false
     * if there is no plot there.
     * If data about the plot was cached, the promise might already be resolved once it is returned by this method.
     * @return Promise
     * @phpstan-return Promise<Plot|false>
     *
     * @throws AssumptionFailedError if given a {@see Position} with an invalid or unloaded {@see World}
     */
    public function getOrLoadPlotAtPosition(Position $position) : Promise {
        /** @phpstan-var PromiseResolver<Plot|false> $resolver */
        $resolver = new PromiseResolver();
        $world = $position->getWorld();
        $this->getOrLoadWorldSettings($world)->onCompletion(
            function(WorldSettings|false $worldSettings) use($position, $world, $resolver) : void {
                if ($worldSettings === false) {
                    $resolver->resolveSilent(false);
                    return;
                }
                $worldName = $world->getFolderName();
                $basePlot = $this->getBasePlotAtPoint($worldName, $worldSettings, $position);
                if ($basePlot instanceof BasePlot) {
                    $this->getOrLoadPlot($world, $basePlot->getX(), $basePlot->getZ())->onCompletion(
                        static function(Plot|false $plot) use($resolver) : void {
                            $resolver->resolveSilent($plot instanceof Plot ? $plot : false);
                        },
                        static function(Throwable $error) use($resolver) : void {
                            $resolver->rejectSilent($error);
                        }
                    );
                    return;
                }
                $roadSize = $worldSettings->getRoadSize();
                $basePlotInNorth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::NORTH, $roadSize));
                $basePlotInSouth = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::SOUTH, $roadSize));
                if ($basePlotInNorth instanceof BasePlot && $basePlotInSouth instanceof BasePlot) {
                    /** @phpstan-var Promise<Plot|false> $promiseNorth */
                    $promiseNorth = $this->getOrLoadPlot($world, $basePlotInNorth->getX(), $basePlotInNorth->getZ());
                    if ($promiseNorth->isResolved() && ($plotInNorth = $promiseNorth->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInNorth */
                        $resolver->resolveSilent($plotInNorth->isMerged($basePlotInSouth) ? $plotInNorth : false);
                        return;
                    }
                    /** @phpstan-var Promise<Plot|false> $promiseSouth */
                    $promiseSouth = $this->getOrLoadPlot($world, $basePlotInSouth->getX(), $basePlotInSouth->getZ());
                    if ($promiseSouth->isResolved() && ($plotInSouth = $promiseSouth->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInSouth */
                        $resolver->resolveSilent($plotInSouth->isMerged($basePlotInNorth) ? $plotInSouth : false);
                        return;
                    }
                    Promise::all([$promiseNorth, $promiseSouth])->onCompletion(
                        /**
                         * @phpstan-param array<mixed, Plot|false> $plots
                         */
                        static function(array $plots) use($resolver) : void {
                            [$plotInNorth, $plotInSouth] = $plots;
                            $resolver->resolveSilent(
                                ($plotInNorth instanceof Plot && $plotInSouth instanceof Plot && $plotInNorth->isSame($plotInSouth)) ? $plotInNorth : false
                            );
                        },
                        static function(Throwable $error) use($resolver) : void {
                            $resolver->rejectSilent($error);
                        }
                    );
                    return;
                }
                $basePlotInWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::WEST, $roadSize));
                $basePlotInEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->getSide(Facing::EAST, $roadSize));
                if ($basePlotInWest instanceof BasePlot && $basePlotInEast instanceof BasePlot) {
                    /** @phpstan-var Promise<Plot|false> $promiseWest */
                    $promiseWest = $this->getOrLoadPlot($world, $basePlotInWest->getX(), $basePlotInWest->getZ());
                    if ($promiseWest->isResolved() && ($plotInWest = $promiseWest->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInWest */
                        $resolver->resolveSilent($plotInWest->isMerged($basePlotInEast) ? $plotInWest : false);
                        return;
                    }
                    /** @phpstan-var Promise<Plot|false> $promiseEast */
                    $promiseEast = $this->getOrLoadPlot($world, $basePlotInEast->getX(), $basePlotInEast->getZ());
                    if ($promiseEast->isResolved() && ($plotInEast = $promiseEast->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInEast */
                        $resolver->resolveSilent($plotInEast->isMerged($basePlotInWest) ? $plotInEast : false);
                        return;
                    }
                    Promise::all([$promiseWest, $promiseEast])->onCompletion(
                    /**
                     * @phpstan-param array<mixed, Plot|false> $plots
                     */
                        static function(array $plots) use($resolver) : void {
                            [$plotInWest, $plotInEast] = $plots;
                            $resolver->resolveSilent(
                                ($plotInWest instanceof Plot && $plotInEast instanceof Plot && $plotInWest->isSame($plotInEast)) ? $plotInWest : false
                            );
                        },
                        static function(Throwable $error) use($resolver) : void {
                            $resolver->rejectSilent($error);
                        }
                    );
                    return;
                }
                $basePlotInNorthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, - $roadSize));
                $basePlotInNorthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, - $roadSize));
                $basePlotInSouthWest = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add(- $roadSize, 0, $roadSize));
                $basePlotInSouthEast = $this->getBasePlotAtPoint($worldName, $worldSettings, $position->add($roadSize, 0, $roadSize));
                if ($basePlotInNorthWest instanceof BasePlot && $basePlotInNorthEast instanceof BasePlot && $basePlotInSouthWest instanceof BasePlot && $basePlotInSouthEast instanceof BasePlot) {
                    /** @phpstan-var Promise<Plot|false> $promiseNorthWest */
                    $promiseNorthWest = $this->getOrLoadPlot($world, $basePlotInNorthWest->getX(), $basePlotInNorthWest->getZ());
                    if ($promiseNorthWest->isResolved() && ($plotInNorthWest = $promiseNorthWest->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInNorthWest */
                        if (
                            $plotInNorthWest->isMerged($basePlotInNorthEast) &&
                            $plotInNorthWest->isMerged($basePlotInSouthWest) &&
                            $plotInNorthWest->isMerged($basePlotInSouthEast)
                        ) {
                            $resolver->resolveSilent($plotInNorthWest);
                        } else {
                            $resolver->resolveSilent(false);
                        }
                        return;
                    }
                    /** @phpstan-var Promise<Plot|false> $promiseNorthEast */
                    $promiseNorthEast = $this->getOrLoadPlot($world, $basePlotInNorthEast->getX(), $basePlotInNorthEast->getZ());
                    if ($promiseNorthEast->isResolved() && ($plotInNorthEast = $promiseNorthEast->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInNorthEast */
                        if (
                            $plotInNorthEast->isMerged($basePlotInNorthWest) &&
                            $plotInNorthEast->isMerged($basePlotInSouthWest) &&
                            $plotInNorthEast->isMerged($basePlotInSouthEast)
                        ) {
                            $resolver->resolveSilent($plotInNorthEast);
                        } else {
                            $resolver->resolveSilent(false);
                        }
                        return;
                    }
                    /** @phpstan-var Promise<Plot|false> $promiseSouthWest */
                    $promiseSouthWest = $this->getOrLoadPlot($world, $basePlotInSouthWest->getX(), $basePlotInSouthWest->getZ());
                    if ($promiseSouthWest->isResolved() && ($plotInSouthWest = $promiseSouthWest->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInSouthWest */
                        if (
                            $plotInSouthWest->isMerged($basePlotInNorthWest) &&
                            $plotInSouthWest->isMerged($basePlotInNorthEast) &&
                            $plotInSouthWest->isMerged($basePlotInSouthEast)
                        ) {
                            $resolver->resolveSilent($plotInSouthWest);
                        } else {
                            $resolver->resolveSilent(false);
                        }
                        return;
                    }
                    /** @phpstan-var Promise<Plot|false> $promiseSouthEast */
                    $promiseSouthEast = $this->getOrLoadPlot($world, $basePlotInSouthEast->getX(), $basePlotInSouthEast->getZ());
                    if ($promiseSouthEast->isResolved() && ($plotInSouthEast = $promiseSouthEast->getResult()) instanceof Plot) {
                        /** @phpstan-var Plot $plotInSouthEast */
                        if (
                            $plotInSouthEast->isMerged($basePlotInNorthWest) &&
                            $plotInSouthEast->isMerged($basePlotInNorthEast) &&
                            $plotInSouthEast->isMerged($basePlotInSouthWest)
                        ) {
                            $resolver->resolveSilent($plotInSouthEast);
                        } else {
                            $resolver->resolveSilent(false);
                        }
                        return;
                    }
                    Promise::all([$promiseNorthWest, $promiseNorthEast, $promiseSouthWest, $promiseSouthEast])->onCompletion(
                    /**
                     * @phpstan-param array<mixed, Plot|false> $plots
                     */
                        static function(array $plots) use($resolver) : void {
                            [$plotInNorthWest, $plotInNorthEast, $plotInSouthWest, $plotInSouthEast] = $plots;
                            $resolver->resolveSilent(
                                (
                                    $plotInNorthWest instanceof Plot && $plotInNorthEast instanceof Plot &&
                                    $plotInSouthWest instanceof Plot && $plotInSouthEast instanceof Plot &&
                                    $plotInNorthWest->isSame($plotInNorthEast) &&
                                    $plotInNorthWest->isSame($plotInSouthWest) &&
                                    $plotInNorthWest->isSame($plotInSouthEast)
                                ) ? $plotInNorthWest : false
                            );
                        },
                        static function(Throwable $error) use($resolver) : void {
                            $resolver->rejectSilent($error);
                        }
                    );
                    return;
                }
                $resolver->resolveSilent(false);
            },
            static function(Throwable $error) use($resolver) : void {
                $resolver->rejectSilent($error);
            }
        );
        return $resolver->getPromise();
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
     * Loads all the {@see Plot}s where the given {@see Player} is an owner of.
     *
     * @param Player $player The player to load the plots of
     *
     * Returns a {@see Promise} that resolves the requested {@see Plot}s object of the given {@see Player}.
     * @return Promise
     * @phpstan-return Promise<array<string, Plot>>
     */
    public function loadPlotsOfPlayer(Player $player) : Promise {
        /** @phpstan-var PromiseResolver<array<string, Plot>> $resolver */
        $resolver = new PromiseResolver();
        Await::f2c(
            /**
             * @phpstan-return Generator<mixed, mixed, mixed, array<string, Plot>>
             */
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
            /**
             * @phpstan-param array<string, Plot> $plots
             */
            static function(array $plots) use($resolver) : void {
                $resolver->resolveSilent($plots);
            },
            static function(Throwable $error) use($resolver) : void {
                $resolver->rejectSilent($error);
            }
        );
        return $resolver->getPromise();
    }
}