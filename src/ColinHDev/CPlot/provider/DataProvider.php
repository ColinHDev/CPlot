<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\SettingManager;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\plots\MergePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\plots\PlotRate;
use ColinHDev\CPlot\provider\cache\Cache;
use ColinHDev\CPlot\provider\cache\CacheIDs;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use poggit\libasynql\SqlThread;
use SOFe\AwaitGenerator\Await;

/**
 * This is an @internal class for handling the storage of data of this plugin in a database.
 * This is NOT an API for other plugins.
 */
final class DataProvider {
    use SingletonTrait;

    private const INIT_PLAYERDATA_TABLE = "cplot.init.playerDataTable";
    private const INIT_PLAYERSETTINGS_TABLE = "cplot.init.playerSettingsTable";
    private const INIT_WORLDS_TABLE = "cplot.init.worldsTable";
    private const INIT_PLOTS_TABLE = "cplot.init.plotsTable";
    private const INIT_MERGEPLOTS_TABLE = "cplot.init.mergePlotsTable";
    private const INIT_PLOTPLAYERS_TABLE = "cplot.init.plotPlayersTable";
    private const INIT_PLOTFLAGS_TABLE = "cplot.init.plotFlagsTable";
    private const INIT_PLOTRATES_TABLE = "cplot.init.plotRatesTable";

    private const GET_PLAYERDATA_BY_UUID = "cplot.get.playerDataByUUID";
    private const GET_PLAYERDATA_BY_NAME = "cplot.get.playerDataByName";
    private const GET_PLAYERSETTINGS = "cplot.get.playerSettings";
    private const GET_WORLD = "cplot.get.world";
    private const GET_PLOT = "cplot.get.plot";
    private const GET_PLOT_BY_ALIAS = "cplot.get.plotByAlias";
    private const GET_ORIGINPLOT = "cplot.get.originPlot";
    private const GET_MERGEPLOTS = "cplot.get.mergePlots";
    private const GET_EXISTING_PLOTXZ = "cplot.get.existingPlotXZ";
    private const GET_PLOTPLAYERS = "cplot.get.plotPlayers";
    private const GET_PLOTS_BY_PLOTPLAYER = "cplot.get.plotsByPlotPlayer";
    private const GET_PLOTFLAGS = "cplot.get.plotFlags";
    private const GET_PLOTRATES = "cplot.get.plotRates";

    private const SET_PLAYERDATA = "cplot.set.playerData";
    private const SET_PLAYERSETTING = "cplot.set.playerSetting";
    private const SET_WORLD = "cplot.set.world";
    private const SET_PLOT = "cplot.set.plot";
    private const SET_MERGEPLOT = "cplot.set.mergePlot";
    private const SET_PLOTPLAYER = "cplot.set.plotPlayer";
    private const SET_PLOTFLAG = "cplot.set.plotFlag";
    private const SET_PLOTRATE = "cplot.set.plotRate";

    private const DELETE_PLAYERSETTING = "cplot.delete.playerSetting";
    private const DELETE_PLOT = "cplot.delete.playerSetting";
    private const DELETE_PLOTPLAYER = "cplot.delete.playerSetting";
    private const DELETE_PLOTFLAG = "cplot.delete.playerSetting";

    private DataConnector $database;

    /** @var array<string, Cache> */
    private array $caches;

    /**
     * @throws SqlError
     */
    public function __construct() {
        $this->database = libasynql::create(CPlot::getInstance(), ResourceManager::getInstance()->getConfig()->get("database"), [
            "sqlite" => "sql" . DIRECTORY_SEPARATOR . "sqlite.sql",
            "mysql" => "sql" . DIRECTORY_SEPARATOR . "mysql.sql"
        ]);

        $this->database->executeGeneric(self::INIT_PLAYERDATA_TABLE);
        $this->database->executeImplRaw(
            ["INSERT OR IGNORE INTO playerData (playerUUID, playerName, lastJoin) VALUES (\"*\", \"*\", \"01.01.1970 00:00:00\");"],
            [[]],
            [SqlThread::MODE_INSERT],
            static function ($results) : void {
            },
            null
        );
        $this->database->executeGeneric(self::INIT_PLAYERSETTINGS_TABLE);
        $this->database->executeGeneric(self::INIT_WORLDS_TABLE);
        $this->database->executeGeneric(self::INIT_PLOTS_TABLE);
        $this->database->executeGeneric(self::INIT_MERGEPLOTS_TABLE);
        $this->database->executeGeneric(self::INIT_PLOTPLAYERS_TABLE);
        $this->database->executeGeneric(self::INIT_PLOTFLAGS_TABLE);
        $this->database->executeGeneric(self::INIT_PLOTRATES_TABLE);

        $this->caches = [
            CacheIDs::CACHE_PLAYER => new Cache(64),
            CacheIDs::CACHE_WORLDSETTING => new Cache(16),
            CacheIDs::CACHE_PLOT => new Cache(128),
        ];
    }

    public function getPlayerCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLAYER];
    }

    public function getWorldSettingCache() : Cache {
        return $this->caches[CacheIDs::CACHE_WORLDSETTING];
    }

    public function getPlotCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLOT];
    }

    /**
     * Fetches and returns the {@see PlayerData} of a player by its UUID synchronously from the {@see DataProvider::getPlayerCache()}.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadPlayerDataByUUIDIntoCache(string $playerUUID) : ?PlayerData {
        $playerData = $this->getPlayerCache()->getObjectFromCache($playerUUID);
        if ($playerData instanceof PlayerData) {
            return $playerData;
        }
        Await::g2c(
            $this->awaitPlayerDataByUUID($playerUUID),
        );
        return null;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its UUID asynchronously from the database (or synchronously from the
     * {@see DataProvider::getPlayerCache()} if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, PlayerData|null>
     */
    public function awaitPlayerDataByUUID(string $playerUUID) : \Generator {
        $playerData = $this->getPlayerCache()->getObjectFromCache($playerUUID);
        if ($playerData instanceof PlayerData) {
            return $playerData;
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_UUID,
            ["playerUUID" => $playerUUID]
        );
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerData = new PlayerData(
            $playerUUID,
            $playerData["playerName"],
            \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"])->getTimestamp(),
            (yield from $this->awaitPlayerSettings($playerUUID))
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
        return $playerData;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its name asynchronously from the database (or synchronously from the
     * {@see DataProvider::getPlayerCache()} if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, PlayerData|null>
     */
    public function awaitPlayerDataByName(string $playerName) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_NAME,
            ["playerName" => $playerName]
        );
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerUUID = $playerData["playerUUID"];
        $playerData = new PlayerData(
            $playerUUID,
            $playerName,
            \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"])->getTimestamp(),
            (yield from $this->awaitPlayerSettings($playerUUID))
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
        return $playerData;
    }

    /**
     * Fetches the settings ({@see BaseAttribute}s) of a player asynchronously from the database and returns a {@see \Generator}. The
     * player settings can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, array<string, BaseAttribute<mixed>>>
     */
    private function awaitPlayerSettings(string $playerUUID) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLAYERSETTINGS,
            ["playerUUID" => $playerUUID]
        );
        $settings = [];
        foreach ($rows as $row) {
            $setting = SettingManager::getInstance()->getSettingByID($row["ID"]);
            if ($setting === null) {
                continue;
            }
            try {
                $settings[$setting->getID()] = $setting->newInstance($setting->parse($row["value"]));
            } catch (AttributeParseException) {
            }
        }
        return $settings;
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function updatePlayerData(string $playerUUID, string $playerName) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_PLAYERDATA,
            [
                "playerUUID" => $playerUUID,
                "playerName" => $playerName,
                "lastJoin" => date("d.m.Y H:i:s")
            ]
        );
    }

    /**
     * @phpstan-param BaseAttribute<mixed> $setting
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function savePlayerSetting(PlayerData $playerData, BaseAttribute $setting) : \Generator {
        $playerUUID = $playerData->getPlayerUUID();
        yield $this->database->asyncInsert(
            self::SET_PLAYERSETTING,
            [
                "playerUUID" => $playerUUID,
                "ID" => $setting->getID(),
                "value" => $setting->toString()
            ]
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function deletePlayerSetting(PlayerData $playerData, string $settingID) : \Generator {
        $playerUUID = $playerData->getPlayerUUID();
        yield $this->database->asyncInsert(
            self::DELETE_PLAYERSETTING,
            [
                "playerUUID" => $playerUUID,
                "ID" => $settingID
            ]
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
    }

    /**
     * Fetches and returns the {@see WorldSettings} of a world synchronously from the {@see DataProvider::getWorldSettingCache()}.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadWorldIntoCache(string $worldName) : WorldSettings|NonWorldSettings|null {
        $worldSettings = $this->getWorldSettingCache()->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings || $worldSettings instanceof NonWorldSettings) {
            return $worldSettings;
        }
        Await::g2c(
            $this->awaitWorld($worldName),
        );
        return null;
    }

    /**
     * Fetches the {@see WorldSettings} of a world asynchronously from the database (or synchronously from the
     * {@see DataProvider::getWorldSettingCache()} if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, WorldSettings|NonWorldSettings>
     */
    public function awaitWorld(string $worldName) : \Generator {
        $worldSettings = $this->getWorldSettingCache()->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings || $worldSettings instanceof NonWorldSettings) {
            return $worldSettings;
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_WORLD,
            ["worldName" => $worldName]
        );
        $worldData = $rows[array_key_first($rows)] ?? null;
        if ($worldData === null) {
            $worldSettings = new NonWorldSettings();
        } else {
            $worldSettings = WorldSettings::fromArray($worldData);
        }
        $this->getWorldSettingCache()->cacheObject($worldName, $worldSettings);
        return $worldSettings;
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function addWorld(string $worldName, WorldSettings $worldSettings) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_WORLD,
            [
                "worldName" => $worldName,
                "worldType" => $worldSettings->getWorldType(),
                "roadSchematic" => $worldSettings->getRoadSchematic(),
                "mergeRoadSchematic" => $worldSettings->getMergeRoadSchematic(),
                "plotSchematic" => $worldSettings->getPlotSchematic(),
                "roadSize" => $worldSettings->getRoadSize(),
                "plotSize" => $worldSettings->getPlotSize(),
                "groundSize" => $worldSettings->getGroundSize(),
                "roadBlock" => ParseUtils::parseStringFromBlock($worldSettings->getRoadBlock()),
                "borderBlock" => ParseUtils::parseStringFromBlock($worldSettings->getBorderBlock()),
                "borderBlockOnClaim" => ParseUtils::parseStringFromBlock($worldSettings->getBorderBlockOnClaim()),
                "plotFloorBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotFloorBlock()),
                "plotFillBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotFillBlock()),
                "plotBottomBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotBottomBlock())
            ]
        );
        $this->getWorldSettingCache()->cacheObject($worldName, $worldSettings);
    }

    /**
     * Fetches and returns a {@see Plot} synchronously from the {@see DataProvider::getPlotCache()}.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadPlotIntoCache(string $worldName, int $x, int $z) : ?Plot {
        $plot = $this->getPlotCache()->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            if ($plot instanceof Plot) {
                return $plot;
            }
            return null;
        }
        Await::g2c(
            $this->awaitPlot($worldName, $x, $z),
        );
        return null;
    }

    /**
     * Fetches a {@see Plot} asynchronously from the database (or synchronously from the
     * {@see DataProvider::getPlotCache()} if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>|array<string, MergePlot>|array<string, PlotPlayer>|array<string, BaseAttribute<mixed>>|array<string, PlotRate>, Plot|null>
     */
    public function awaitPlot(string $worldName, int $x, int $z) : \Generator {
        $plot = $this->getPlotCache()->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            if ($plot instanceof Plot) {
                return $plot;
            }
            return null;
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOT,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $worldSettings = yield from $this->awaitWorld($worldName);
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            $plot = new Plot($worldName, $worldSettings, $x, $z);
            $this->getPlotCache()->cacheObject($plot->toString(), $plot);
            return $plot;
        }
        $plot = new Plot(
            $worldName,
            $worldSettings,
            $x,
            $z,
            $plotData["biomeID"],
            $plotData["alias"],
            (yield from $this->awaitMergePlots($worldName, $x, $z)),
            (yield from $this->awaitPlotPlayers($worldName, $x, $z)),
            (yield from $this->awaitPlotFlags($worldName, $x, $z)),
            (yield from $this->awaitPlotRates($worldName, $x, $z))
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return $plot;
    }

    /**
     * Fetches the {@see MergePlot}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * merge plots can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, array<string, MergePlot>>
     */
    private function awaitMergePlots(string $worldName, int $x, int $z) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_MERGEPLOTS,
            [
                "worldName" => $worldName,
                "originX" => $x,
                "originZ" => $z
            ]
        );
        $worldSettings = yield from $this->awaitWorld($worldName);
        $mergePlots = [];
        foreach ($rows as $row) {
            $mergePlot = new MergePlot($worldName, $worldSettings, $row["mergeX"], $row["mergeZ"], $x, $z);
            $mergePlotKey = $mergePlot->toString();
            $this->getPlotCache()->cacheObject($mergePlotKey, $mergePlot);
            $mergePlots[$mergePlotKey] = $mergePlot;
        }
        return $mergePlots;
    }

    /**
     * Fetches the {@see PlotPlayer}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot players can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, array<string, PlotPlayer>>
     */
    private function awaitPlotPlayers(string $worldName, int $x, int $z) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTPLAYERS,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotPlayers = [];
        foreach ($rows as $row) {
            $playerUUID = $row["playerUUID"];
            $plotPlayers[$playerUUID] = new PlotPlayer(
                $playerUUID,
                $row["state"],
                \DateTime::createFromFormat("d.m.Y H:i:s", $row["addTime"])->getTimestamp()
            );
        }
        return $plotPlayers;
    }

    /**
     * Fetches the flags ({@see BaseAttribute}s) of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot flags can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, array<string, BaseAttribute<mixed>>>
     */
    private function awaitPlotFlags(string $worldName, int $x, int $z) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTFLAGS,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotFlags = [];
        foreach ($rows as $row) {
            $plotFlag = FlagManager::getInstance()->getFlagByID($row["ID"]);
            if ($plotFlag === null) {
                continue;
            }
            try {
                $plotFlags[$plotFlag->getID()] = $plotFlag->newInstance($plotFlag->parse($row["value"]));
            } catch (AttributeParseException) {
            }
        }
        return $plotFlags;
    }

    /**
     * Fetches the {@see PlotRate}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot rates can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, array<string, PlotRate>>
     */
    private function awaitPlotRates(string $worldName, int $x, int $z) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTRATES,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotRates = [];
        foreach ($rows as $row) {
            $plotRate = new PlotRate(
                $row["rate"],
                $row["playerUUID"],
                \DateTime::createFromFormat("d.m.Y H:i:s", $row["rateTime"])->getTimestamp(),
                $row["comment"] ?? null
            );
            $plotRates[$plotRate->toString()] = $plotRate;
        }
        return $plotRates;
    }

    /**
     * Fetches a {@see Plot} by its alias asynchronously from the database and returns a {@see \Generator}. It can be get
     * by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>|WorldSettings|array<string, MergePlot>|array<string, PlotPlayer>|array<string, BaseAttribute<mixed>>|array<string, PlotRate>, Plot|null>
     */
    public function awaitPlotByAlias(string $alias) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOT_BY_ALIAS,
            ["alias" => $alias]
        );
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            return null;
        }
        $worldName = $plotData["worldName"];
        $x = $plotData["x"];
        $z = $plotData["z"];
        $plot = new Plot(
            $worldName,
            (yield from $this->awaitWorld($worldName)),
            $x,
            $z,
            $plotData["biomeID"],
            $alias,
            (yield from $this->awaitMergePlots($worldName, $x, $z)),
            (yield from $this->awaitPlotPlayers($worldName, $x, $z)),
            (yield from $this->awaitPlotFlags($worldName, $x, $z)),
            (yield from $this->awaitPlotRates($worldName, $x, $z))
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return $plot;
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function savePlot(Plot $plot) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_PLOT,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "biomeID" => $plot->getBiomeID(),
                "alias" => $plot->getAlias()
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function deletePlot(Plot $plot) : \Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOT,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
        $this->getPlotCache()->removeObjectFromCache($plot->toString());
    }

    /**
     * Fetches and returns the origin plot ({@see Plot}) of another plot synchronously from the {@see DataProvider::getPlotCache()}.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadMergeOriginIntoCache(BasePlot $plot) : ?Plot {
        if ($plot instanceof Plot) {
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            return $this->loadPlotIntoCache($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }
        $plotInCache = $this->getPlotCache()->getObjectFromCache($plot->toString());
        if ($plotInCache instanceof Plot) {
            return $plotInCache;
        }
        if ($plotInCache instanceof MergePlot) {
            return $this->loadPlotIntoCache($plotInCache->getWorldName(), $plotInCache->getOriginX(), $plotInCache->getOriginZ());
        }
        Await::g2c(
            $this->awaitMergeOrigin($plot),
        );
        return null;
    }

    /**
     * Fetches the origin plot ({@see Plot}) of another plot asynchronously from the database (or synchronously from the
     * {@see DataProvider::getPlotCache()} if contained) and returns a {@see \Generator}. It can be get
     * by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>|Plot|null, Plot|null>
     */
    public function awaitMergeOrigin(BasePlot $plot) : \Generator {
        if ($plot instanceof Plot) {
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            return yield $this->awaitPlot($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }
        $plotInCache = $this->getPlotCache()->getObjectFromCache($plot->toString());
        if ($plotInCache instanceof Plot) {
            return $plotInCache;
        }
        if ($plotInCache instanceof MergePlot) {
            return yield $this->awaitPlot($plotInCache->getWorldName(), $plotInCache->getOriginX(), $plotInCache->getOriginZ());
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_ORIGINPLOT,
            [
                "worldName" => $plot->getWorldName(),
                "mergeX" => $plot->getX(),
                "mergeZ" => $plot->getZ()
            ]
        );
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            return yield $this->awaitPlot($plot->getWorldName(), $plot->getX(), $plot->getZ());
        }
        return yield $this->awaitPlot($plot->getWorldName(), $plotData["originX"], $plotData["originZ"]);
    }

    /**
     * Fetches asynchronously all {@see Plot}s and {@see MergePlot}s in a plot world in a certain radius around plot
     * worldName;0;0 from the database. Returns a {@see \Generator} that returns a plot that is in the radius, closest
     * to the spawn and has no data in the database or null if no such plot could be found, by using {@see Await}.
     * @param int $limitXZ Limits the radius in which plots are fetched.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>, Plot|null>
     */
    public function awaitNextFreePlot(string $worldName, WorldSettings $worldSettings, int $limitXZ = 0) : \Generator {
        for ($i = 0; $limitXZ <= 0 || $i < $limitXZ; $i++) {
            $plots = [];
            $rows = yield $this->database->asyncSelect(
                self::GET_EXISTING_PLOTXZ,
                [
                    "worldName" => $worldName,
                    "number" => $i
                ]
            );
            foreach ($rows as $row) {
                $plots[$row["x"]][$row["z"]] = true;
            }
            if (count($plots) === max(1, 8 * $i)) {
                continue;
            }
            if (($ret = $this->findEmptyPlotSquared(0, $i, $plots)) !== null) {
                [$x, $z] = $ret;
                $plot = new Plot($worldName, $worldSettings, $x, $z);
                $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                return $plot;
            }
            for ($a = 1; $a < $i; $a++) {
                if (($ret = $this->findEmptyPlotSquared($a, $i, $plots)) !== null) {
                    [$x, $z] = $ret;
                    $plot = new Plot($worldName, $worldSettings, $x, $z);
                    $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                    return $plot;
                }
            }
            if (($ret = $this->findEmptyPlotSquared($i, $i, $plots)) !== null) {
                [$x, $z] = $ret;
                $plot = new Plot($worldName, $worldSettings, $x, $z);
                $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                return $plot;
            }
        }
        return null;
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function addMergePlot(Plot $origin, BasePlot $plot) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_MERGEPLOT,
            [
                "worldName" => $origin->getWorldName(),
                "originX" => $origin->getX(),
                "originZ" => $origin->getZ(),
                "mergeX" => $plot->getX(),
                "mergeZ" => $plot->getZ()
            ]
        );
        $this->getPlotCache()->cacheObject($origin->toString(), $origin);
        $mergePlot = MergePlot::fromBasePlot($plot, $origin->getX(), $origin->getZ());
        $this->getPlotCache()->cacheObject($mergePlot->toString(), $mergePlot);
    }

    /**
     * Fetches {@see Plot}s by a common {@see PlotPlayer} asynchronously from the database and returns a {@see \Generator}.
     * It can be get by using {@see Await}.
     * @phpstan-return \Generator<int, mixed, array<array<string, mixed>>|Plot|null, array<string, Plot>>
     */
    public function awaitPlotsByPlotPlayer(string $playerUUID, string $state) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTS_BY_PLOTPLAYER,
            [
                "playerUUID" => $playerUUID,
                "state" => $state,
            ]
        );
        $plots = [];
        foreach ($rows as $row) {
            $plot = yield $this->awaitPlot($row["worldName"], $row["x"], $row["z"]);
            if ($plot instanceof Plot) {
                $plots[$plot->toString()] = $plot;
            }
        }
        return $plots;
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_PLOTPLAYER,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "playerUUID" => $plotPlayer->getPlayerUUID(),
                "state" => $plotPlayer->getState(),
                "addTime" => date("d.m.Y H:i:s", $plotPlayer->getAddTime())
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function deletePlotPlayer(Plot $plot, string $playerUUID) : \Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTPLAYER,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "playerUUID" => $playerUUID
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-param BaseAttribute<mixed> $flag
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function savePlotFlag(Plot $plot, BaseAttribute $flag) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_PLOTFLAG,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "ID" => $flag->getID(),
                "value" => $flag->toString()
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function deletePlotFlag(Plot $plot, string $flagID) : \Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTFLAG,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "ID" => $flagID
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function savePlotRate(Plot $plot, PlotRate $plotRate) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_PLOTRATE,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "rate" => $plotRate->getRate(),
                "playerUUID" => $plotRate->getPlayerUUID(),
                "rateTime" => date("d.m.Y H:i:s", $plotRate->getRateTime()),
                "comment" => $plotRate->getComment()
            ]
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
    }

    public function close() : void {
        $this->database->close();
    }

    /**
     * @phpstan-param array<int, array<int, true>> $plots
     * @return int[] | null
     * code from @see https://github.com/jasonwynn10/MyPlot
     */
    private function findEmptyPlotSquared(int $a, int $b, array $plots) : ?array {
        if(!isset($plots[$a][$b]))
            return [$a, $b];
        if(!isset($plots[$b][$a]))
            return [$b, $a];
        if($a !== 0) {
            if(!isset($plots[-$a][$b]))
                return [-$a, $b];
            if(!isset($plots[$b][-$a]))
                return [$b, -$a];
        }
        if($b !== 0) {
            if(!isset($plots[-$b][$a]))
                return [-$b, $a];
            if(!isset($plots[$a][-$b]))
                return [$a, -$b];
        }
        if(($a | $b) === 0) {
            if(!isset($plots[-$a][-$b]))
                return [-$a, -$b];
            if(!isset($plots[-$b][-$a]))
                return [-$b, -$a];
        }
        return null;
    }
}