<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\provider\cache\Cache;
use ColinHDev\CPlot\provider\cache\CacheIDs;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use ColinHDev\CPlotAPI\players\PlayerData;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use ColinHDev\CPlotAPI\plots\MergePlot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use ColinHDev\CPlotAPI\plots\PlotRate;
use ColinHDev\CPlotAPI\utils\ParseUtils;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlThread;

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
     * @throws \poggit\libasynql\SqlError
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

    private function getPlayerCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLAYER];
    }

    private function getWorldSettingCache() : Cache {
        return $this->caches[CacheIDs::CACHE_WORLDSETTING];
    }

    private function getPlotCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLOT];
    }

    public function getPlayerDataByUUID(string $playerUUID) : \Generator {
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
        $playerData = new PlayerData(
            $playerUUID,
            $playerData["playerName"],
            \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"])->getTimestamp(),
            $settings
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
        return $playerData;
    }

    public function getPlayerDataByName(string $playerName) : \Generator {
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
            yield $this->getPlayerSettings($playerUUID)
        );
        $this->getPlayerCache()->cacheObject($playerUUID, $playerData);
        return $playerData;
    }

    private function getPlayerSettings(string $playerUUID) : \Generator {
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

    public function setPlayerSetting(PlayerData $playerData, BaseAttribute $setting) : \Generator {
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

    public function getWorld(string $worldName) : \Generator {
        $worldSettings = $this->getWorldSettingCache()->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings) {
            return $worldSettings;
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_WORLD,
            ["worldName" => $worldName]
        );
        $worldData = $rows[array_key_first($rows)] ?? null;
        if ($worldData === null) {
            return null;
        }
        $worldSettings = WorldSettings::fromArray($worldData);
        $this->getWorldSettingCache()->cacheObject($worldName, $worldSettings);
        return $worldSettings;
    }

    public function addWorld(string $worldName, WorldSettings $worldSettings) : \Generator {
        yield $this->database->asyncInsert(
            self::SET_WORLD,
            [
                "worldName" => $worldName,
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

    public function getPlot(string $worldName, int $x, int $z) : \Generator {
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
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            return null;
        }
        $plot = new Plot(
            $worldName,
            $x,
            $z,
            $plotData["biomeID"],
            $plotData["alias"],
            yield $this->getMergePlots($worldName, $x, $z),
            yield $this->getPlotPlayers($worldName, $x, $z),
            yield $this->getPlotFlags($worldName, $x, $z),
            yield $this->getPlotRates($worldName, $x, $z)
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return $plot;
    }

    private function getMergePlots(string $worldName, int $x, int $z) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_MERGEPLOTS,
            [
                "worldName" => $worldName,
                "originX" => $x,
                "originZ" => $z
            ]
        );
        $mergePlots = [];
        foreach ($rows as $row) {
            $mergePlot = new MergePlot($worldName, $row["mergeX"], $row["mergeZ"], $x, $z);
            $mergePlotKey = $mergePlot->toString();
            $this->getPlotCache()->cacheObject($mergePlotKey, $mergePlot);
            $mergedPlots[$mergePlotKey] = $mergePlot;
        }
        return $mergePlots;
    }

    private function getPlotPlayers(string $worldName, int $x, int $z) : \Generator {
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

    private function getPlotFlags(string $worldName, int $x, int $z) : \Generator {
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

    private function getPlotRates(string $worldName, int $x, int $z) : \Generator {
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

    public function getPlotByAlias(string $alias) : \Generator {
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
            $x,
            $z,
            $plotData["biomeID"],
            $alias,
            yield $this->getMergePlots($worldName, $x, $z),
            yield $this->getPlotPlayers($worldName, $x, $z),
            yield $this->getPlotFlags($worldName, $x, $z),
            yield $this->getPlotRates($worldName, $x, $z)
        );
        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return $plot;
    }

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

    public function getMergeOrigin(BasePlot $plot) : \Generator {
        if ($plot instanceof Plot) {
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            return yield $this->getPlot($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }
        $rows = yield $this->database->asyncSelect(
            self::GET_ORIGINPLOT,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            return yield $this->getPlot($plot->getWorldName(), $plot->getX(), $plot->getZ());
        }
        return yield $this->getPlot($plot->getWorldName(), $plotData["originX"], $plotData["originZ"]);
    }

    public function getNextFreePlot(string $worldName, int $limitXZ = 0) : \Generator {
        $i = 0;
        for(; $limitXZ <= 0 or $i < $limitXZ; $i++) {
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
                $plot = new Plot($worldName, $x, $z);
                $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                return $plot;
            }
            for ($a = 1; $a < $i; $a++) {
                if (($ret = $this->findEmptyPlotSquared($a, $i, $plots)) !== null) {
                    [$x, $z] = $ret;
                    $plot = new Plot($worldName, $x, $z);
                    $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                    return $plot;
                }
            }
            if (($ret = $this->findEmptyPlotSquared($i, $i, $plots)) !== null) {
                [$x, $z] = $ret;
                $plot = new Plot($worldName, $x, $z);
                $this->getPlotCache()->cacheObject($plot->toString(), $plot);
                return $plot;
            }
        }
        return null;
    }

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

    public function getPlotsByPlotPlayer(string $playerUUID, string $state) : \Generator {
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTS_BY_PLOTPLAYER,
            [
                "playerUUID" => $playerUUID,
                "state" => $state,
            ]
        );
        $plots = [];
        foreach ($rows as $row) {
            $plot = yield $this->getPlot($row["worldName"], $row["x"], $row["z"]);
            if ($plot === null) {
                return null;
            }
            $plots[] = $plot;
        }
        return $plots;
    }

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