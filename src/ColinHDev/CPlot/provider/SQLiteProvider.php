<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlotAPI\plots\flags\utils\FlagParseException;
use ColinHDev\CPlotAPI\players\settings\BaseSetting;
use ColinHDev\CPlotAPI\players\PlayerData;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use ColinHDev\CPlotAPI\plots\PlotRate;
use ColinHDev\CPlotAPI\utils\ParseUtils;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\BaseFlag;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use ColinHDev\CPlotAPI\plots\MergePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use Exception;
use pocketmine\data\bedrock\BiomeIds;
use SQLite3;
use SQLite3Stmt;
use SQLite3Result;

class SQLiteProvider extends DataProvider {

    private SQLite3 $database;

    private SQLite3Stmt $getPlayerByUUID;
    private SQLite3Stmt $getPlayerByName;
    private SQLite3Stmt $setPlayer;

    private SQLite3Stmt $getPlayerSettings;
    private SQLite3Stmt $setPlayerSetting;
    private SQLite3Stmt $deletePlayerSetting;

    private SQLite3Stmt $getWorld;
    private SQLite3Stmt $setWorld;

    private SQLite3Stmt $getPlot;
    private SQLite3Stmt $getPlotsByOwnerUUID;
    private SQLite3Stmt $getPlotByAlias;
    private SQLite3Stmt $setPlot;
    private SQLite3Stmt $deletePlot;

    private SQLite3Stmt $getOriginPlot;
    private SQLite3Stmt $getMergedPlots;
    private SQLite3Stmt $addMergedPlot;

    private SQLite3Stmt $getExistingPlotXZ;

    private SQLite3Stmt $getPlotPlayers;
    private SQLite3Stmt $setPlotPlayer;
    private SQLite3Stmt $deletePlotPlayer;

    private SQLite3Stmt $getPlotFlags;
    private SQLite3Stmt $setPlotFlag;
    private SQLite3Stmt $deletePlotFlag;

    private SQLite3Stmt $getPlotRates;
    private SQLite3Stmt $setPlotRate;

    /**
     * SQLiteProvider constructor.
     * @throws Exception
     */
    public function __construct(array $settings) {
        parent::__construct();

        $this->database = new SQLite3($settings["folder"] . $settings["file"]);
        $this->database->exec("PRAGMA foreign_keys = TRUE;");

        $sql =
            "CREATE TABLE IF NOT EXISTS players (
            playerUUID VARCHAR(256) NOT NULL, playerName VARCHAR(256) NOT NULL, lastPlayed INTEGER NOT NULL,
            PRIMARY KEY (playerUUID)
            )";
        $this->database->exec($sql);
        $sql =
            "INSERT OR IGNORE INTO players (playerUUID, playerName, lastPlayed) VALUES (\"*\", \"*\", 0);";
        $result = $this->database->query($sql);
        if (!$result instanceof SQLite3Result) throw new Exception("#" . $this->database->lastErrorCode() . ": " . $this->database->lastErrorMsg());
        $sql =
            "SELECT playerName, lastPlayed FROM players WHERE playerUUID = :playerUUID;";
        $this->getPlayerByUUID = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT playerUUID, lastPlayed FROM players WHERE playerName = :playerName;";
        $this->getPlayerByName = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO players (playerUUID, playerName, lastPlayed) VALUES (:playerUUID, :playerName, :lastPlayed) ON CONFLICT (playerUUID) DO UPDATE SET playerName = excluded.playerName, lastPlayed = excluded.lastPlayed;";
        $this->setPlayer = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS playerSettings (
            playerUUID VARCHAR(256) NOT NULL, ID TEXT NOT NULL, value TEXT NOT NULL,
            PRIMARY KEY (playerUUID, ID), 
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT ID, value FROM playerSettings WHERE playerUUID = :playerUUID;";
        $this->getPlayerSettings = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO playerSettings (playerUUID, ID, value) VALUES (:playerUUID, :ID, :value);";
        $this->setPlayerSetting = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM playerSettings WHERE playerUUID = :playerUUID AND ID = :ID;";
        $this->deletePlayerSetting = $this->createSQLite3Stmt($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS worlds (
                worldName VARCHAR(256) NOT NULL,
                roadSchematic TEXT NOT NULL, mergeRoadSchematic TEXT NOT NULL, plotSchematic TEXT NOT NULL,
                roadSize TEXT NOT NULL, plotSize TEXT NOT NULL, groundSize TEXT NOT NULL,
                roadBlock TEXT NOT NULL, borderBlock TEXT NOT NULL, borderBlockOnClaim TEXT NOT NULL, 
                plotFloorBlock TEXT NOT NULL, plotFillBlock TEXT NOT NULL, plotBottomBlock TEXT NOT NULL, 
                PRIMARY KEY (worldName)
            );";
        $this->database->exec($sql);
        $sql =
            "SELECT * FROM worlds WHERE worldName = :worldName;";
        $this->getWorld = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO worlds 
            (worldName, roadSchematic, mergeRoadSchematic, plotSchematic, roadSize, plotSize, groundSize, roadBlock, borderBlock, borderBlockOnClaim, plotFloorBlock, plotFillBlock, plotBottomBlock) VALUES 
            (:worldName, :roadSchematic, :mergeRoadSchematic, :plotSchematic, :roadSize, :plotSize, :groundSize, :roadBlock, :borderBlock, :borderBlockOnClaim, :plotFloorBlock, :plotFillBlock, :plotBottomBlock);";
        $this->setWorld = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plots (
            worldName VARCHAR(256) NOT NULL, x INTEGER NOT NULL, z INTEGER NOT NULL,
            biomeID INTEGER NOT NULL, ownerUUID VARCHAR(256), claimTime INTEGER, alias TEXT,
            PRIMARY KEY (worldName, x, z),
            FOREIGN KEY (worldName) REFERENCES worlds (worldName) ON DELETE CASCADE,
            FOREIGN KEY (ownerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT biomeID, ownerUUID, claimTime, alias FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT worldName, x, z, biomeID, claimTime, alias FROM plots WHERE ownerUUID = :ownerUUID;";
        $this->getPlotsByOwnerUUID = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT worldName, x, z, biomeID, ownerUUID, claimTime FROM plots WHERE alias = :alias;";
        $this->getPlotByAlias = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plots (worldName, x, z, biomeID, ownerUUID, claimTime, alias) VALUES (:worldName, :x, :z, :biomeID, :ownerUUID, :claimTime, :alias) ON CONFLICT DO UPDATE SET biomeID = excluded.biomeID, ownerUUID = excluded.ownerUUID, claimTime = excluded.claimTime, alias = excluded.alias;";
        $this->setPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->deletePlot = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS mergePlots (
            worldName VARCHAR(256) NOT NULL, originX INTEGER NOT NULL, originZ INTEGER NOT NULL, mergeX INTEGER NOT NULL, mergeZ INTEGER NOT NULL, 
            PRIMARY KEY (worldName, originX, originZ, mergeX, mergeZ),
            FOREIGN KEY (worldName, originX, originZ) REFERENCES plots (worldName, x, z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT originX, originZ FROM mergePlots WHERE worldName = :worldName AND mergeX = :mergeX AND mergeZ = :mergeZ;";
        $this->getOriginPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT mergeX, mergeZ FROM mergePlots WHERE worldName = :worldName AND originX = :originX AND originZ = :originZ;";
        $this->getMergedPlots = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO mergePlots (worldName, originX, originZ, mergeX, mergeZ) VALUES (:worldName, :originX, :originZ, :mergeX, :mergeZ);";
        $this->addMergedPlot = $this->createSQLite3Stmt($sql);

        /** code (modified here) from @see https://github.com/jasonwynn10/MyPlot */
        $sql =
            "SELECT x, z FROM plots WHERE (
                worldName = :worldName AND (
                    (abs(x) = :number AND abs(z) <= :number) OR
                    (abs(z) = :number AND abs(x) <= :number)
                )
			)
            UNION 
            SELECT mergeX, mergeZ FROM mergePlots WHERE (
                worldName = :worldName AND (
                    (abs(mergeX) = :number AND abs(mergeZ) <= :number) OR
                    (abs(mergeZ) = :number AND abs(mergeX) <= :number)
                )
			);";
        $this->getExistingPlotXZ = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotPlayers (
            worldName VARCHAR(256) NOT NULL, x INTEGER NOT NULL, z INTEGER NOT NULL, playerUUID VARCHAR(256) NOT NULL, state TEXT NOT NULL, addTime INTEGER NOT NULL,
            PRIMARY KEY (worldName, x, z, playerUUID),
            FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE,
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT playerUUID, state, addTime FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotPlayers = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plotPlayers (worldName, x, z, playerUUID, state, addTime) VALUES (:worldName, :x, :z, :playerUUID, :state, :addTime) ON CONFLICT DO UPDATE SET state = excluded.state, addTime = excluded.addTime;";
        $this->setPlotPlayer = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z AND playerUUID = :playerUUID;";
        $this->deletePlotPlayer = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotFlags (
            worldName VARCHAR(256) NOT NULL, x INTEGER NOT NULL, z INTEGER NOT NULL, ID TEXT NOT NULL, value TEXT NOT NULL,
            PRIMARY KEY (worldName, x, z, ID),
            FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT ID, value FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotFlags = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO plotFlags (worldName, x, z, ID, value) VALUES (:worldName, :x, :z, :ID, :value);";
        $this->setPlotFlag = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z AND ID = :ID;";
        $this->deletePlotFlag = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotRates (
            worldName VARCHAR(256) NOT NULL, x INTEGER NOT NULL, z INTEGER NOT NULL, 
            rate DECIMAL(4, 2) NOT NULL, playerUUID VARCHAR(256) NOT NULL, rateTime INTEGER NOT NULL, comment TEXT,
            PRIMARY KEY (worldName, x, z, playerUUID, rateTime),
            FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE,
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT rate, playerUUID, rateTime, comment FROM plotRates WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotRates = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plotRates (worldName, x, z, rate, playerUUID, rateTime, comment) VALUES (:worldName, :x, :z, :rate, :playerUUID, :rateTime, :comment) ON CONFLICT DO UPDATE SET rate = excluded.rate, comment = excluded.comment;";
        $this->setPlotRate = $this->createSQLite3Stmt($sql);
    }

    /**
     * @throws Exception
     */
    private function createSQLite3Stmt(string $sql) : SQLite3Stmt {
        $stmt = $this->database->prepare($sql);
        if ($stmt === false) {
            throw new Exception("#" . $this->database->lastErrorCode() . ": " . $this->database->lastErrorMsg());
        }
        return $stmt;
    }

    public function getPlayerByUUID(string $playerUUID) : ?PlayerData {
        $player = $this->getPlayerCache()->getObjectFromCache($playerUUID);
        if ($player instanceof PlayerData) return $player;

        $this->getPlayerByUUID->bindValue(":playerUUID", $playerUUID, SQLITE3_TEXT);

        $this->getPlayerByUUID->reset();
        $result = $this->getPlayerByUUID->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $player = new PlayerData(
                $playerUUID, $var["playerName"], $var["lastPlayed"]
            );
            $this->getPlayerCache()->cacheObject($playerUUID, $player);
            return $player;
        }
        return null;
    }

    public function getPlayerByName(string $playerName) : ?PlayerData {
        $this->getPlayerByName->bindValue(":playerName", $playerName, SQLITE3_TEXT);

        $this->getPlayerByName->reset();
        $result = $this->getPlayerByName->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $player = new PlayerData(
                $var["playerUUID"], $playerName, $var["lastPlayed"]
            );
            $this->getPlayerCache()->cacheObject($player->getPlayerUUID(), $player);
            return $player;
        }
        return null;
    }

    public function getPlayerNameByUUID(string $playerUUID) : ?string {
        $player = $this->getPlayerByUUID($playerUUID);
        if ($player === null) return null;
        return $player->getPlayerName();
    }

    public function getPlayerUUIDByName(string $playerName) : ?string {
        $player = $this->getPlayerByName($playerName);
        if ($player === null) return null;
        return $player->getPlayerUUID();
    }

    public function setPlayer(PlayerData $player) : bool {
        $this->setPlayer->bindValue(":playerUUID", $player->getPlayerUUID(), SQLITE3_TEXT);
        $this->setPlayer->bindValue(":playerName", $player->getPlayerName(), SQLITE3_TEXT);
        $this->setPlayer->bindValue(":lastPlayed", $player->getLastPlayed(), SQLITE3_INTEGER);

        $this->setPlayer->reset();
        $result = $this->setPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlayerCache()->cacheObject($player->getPlayerUUID(), $player);
        return true;
    }


    /**
     * @return BaseSetting[] | null
     */
    public function getPlayerSettings(PlayerData $player) : ?array {
        $this->getPlayerSettings->bindValue(":playerUUID", $player->getPlayerUUID(), SQLITE3_TEXT);

        $this->getPlayerSettings->reset();
        $result = $this->getPlayerSettings->execute();
        if (!$result instanceof SQLite3Result) return null;

        $settings = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $setting = SettingManager::getInstance()->getSettingByID($var["ID"]);
            if ($setting === null) continue;
            $setting->setValue(
                $setting->unserializeValueType($var["value"])
            );
            $settings[$setting->getID()] = $setting;
        }
        return $settings;
    }

    public function savePlayerSetting(PlayerData $player, BaseSetting $setting) : bool {
        if ($setting->getValue() === null) return false;
        if (!$player->addSetting($setting)) return false;

        $this->setPlayerSetting->bindValue(":playerUUID", $player->getPlayerUUID(), SQLITE3_TEXT);
        $this->setPlayerSetting->bindValue(":ID", $setting->getID(), SQLITE3_TEXT);
        $this->setPlayerSetting->bindValue(":value", $setting->serializeValueType($setting->getValue()), SQLITE3_TEXT);

        $this->setPlayerSetting->reset();
        $result = $this->setPlayerSetting->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlayerCache()->cacheObject($player->getPlayerUUID(), $player);
        return true;
    }

    public function deletePlayerSetting(PlayerData $player, string $settingID) : bool {
        if (!$player->removeSetting($settingID)) return false;

        $this->deletePlayerSetting->bindValue(":playerUUID", $player->getPlayerUUID(), SQLITE3_TEXT);
        $this->deletePlayerSetting->bindValue(":ID", $settingID, SQLITE3_TEXT);

        $this->deletePlayerSetting->reset();
        $result = $this->deletePlayerSetting->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlayerCache()->cacheObject($player->getPlayerUUID(), $player);
        return true;
    }


    public function getWorld(string $worldName) : ?WorldSettings {
        $worldSettings = $this->getWorldSettingCache()->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings) return $worldSettings;

        $this->getWorld->bindValue(":worldName", $worldName, SQLITE3_TEXT);

        $this->getWorld->reset();
        $result = $this->getWorld->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $worldSettings = WorldSettings::fromArray($var);
            $this->getWorldSettingCache()->cacheObject($worldName, $worldSettings);
            return $worldSettings;
        }
        return null;
    }

    public function addWorld(string $worldName, WorldSettings $worldSettings) : bool {
        $this->setWorld->bindValue(":worldName", $worldName, SQLITE3_TEXT);

        $this->setWorld->bindValue(":roadSchematic", $worldSettings->getRoadSchematic(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":mergeRoadSchematic", $worldSettings->getMergeRoadSchematic(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":plotSchematic", $worldSettings->getPlotSchematic(), SQLITE3_TEXT);

        $this->setWorld->bindValue(":roadSize", $worldSettings->getRoadSize(), SQLITE3_INTEGER);
        $this->setWorld->bindValue(":plotSize", $worldSettings->getPlotSize(), SQLITE3_INTEGER);
        $this->setWorld->bindValue(":groundSize", $worldSettings->getGroundSize(), SQLITE3_INTEGER);

        $this->setWorld->bindValue(":roadBlock", ParseUtils::parseStringFromBlock($worldSettings->getRoadBlock()), SQLITE3_TEXT);
        $this->setWorld->bindValue(":borderBlock", ParseUtils::parseStringFromBlock($worldSettings->getBorderBlock()), SQLITE3_TEXT);
        $this->setWorld->bindValue(":borderBlockOnClaim", ParseUtils::parseStringFromBlock($worldSettings->getBorderBlockOnClaim()), SQLITE3_TEXT);
        $this->setWorld->bindValue(":plotFloorBlock", ParseUtils::parseStringFromBlock($worldSettings->getPlotFloorBlock()), SQLITE3_TEXT);
        $this->setWorld->bindValue(":plotFillBlock", ParseUtils::parseStringFromBlock($worldSettings->getPlotFillBlock()), SQLITE3_TEXT);
        $this->setWorld->bindValue(":plotBottomBlock", ParseUtils::parseStringFromBlock($worldSettings->getPlotBottomBlock()), SQLITE3_TEXT);

        $this->setWorld->reset();
        $result = $this->setWorld->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getWorldSettingCache()->cacheObject($worldName, $worldSettings);
        return true;
    }


    public function getPlot(string $worldName, int $x, int $z) : ?Plot {
        $plot = $this->getPlotCache()->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            if (!$plot instanceof Plot) return null;
            return $plot;
        }

        $this->getPlot->bindValue(":worldName", $worldName, SQLITE3_TEXT);
        $this->getPlot->bindValue(":x", $x, SQLITE3_INTEGER);
        $this->getPlot->bindValue(":z", $z, SQLITE3_INTEGER);

        $this->getPlot->reset();
        $result = $this->getPlot->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $worldName, $x, $z,
                $var["biomeID"] ?? BiomeIds::PLAINS, $var["ownerUUID"] ?? null, $var["claimTime"] ?? null, $var["alias"] ?? null
            );
            $this->getPlotCache()->cacheObject($plot->toString(), $plot);
            return $plot;
        }
        return new Plot($worldName, $x, $z);
    }

    /**
     * @return Plot[] | null
     */
    public function getPlotsByOwnerUUID(string $ownerUUID) : ?array {
        $this->getPlotsByOwnerUUID->bindValue(":ownerUUID", $ownerUUID, SQLITE3_TEXT);

        $this->getPlotsByOwnerUUID->reset();
        $result = $this->getPlotsByOwnerUUID->execute();
        if (!$result instanceof SQLite3Result) return null;

        $plots = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $var["worldName"], $var["x"], $var["z"],
                $var["biomeID"] ?? BiomeIds::PLAINS, $ownerUUID, $var["claimTime"] ?? null, $var["alias"] ?? null
            );
            $this->getPlotCache()->cacheObject($plot->toString(), $plot);
            $plots[] = $plot;
        }
        return $plots;
    }

    public function getPlotByAlias(string $alias) : ?Plot {
        $this->getPlotByAlias->bindValue(":alias", $alias, SQLITE3_TEXT);

        $this->getPlotByAlias->reset();
        $result = $this->getPlotByAlias->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $var["worldName"], $var["x"], $var["z"],
                $var["biomeID"] ?? BiomeIds::PLAINS, $var["ownerUUID"] ?? null, $var["claimTime"] ?? null, $alias
            );
            $this->getPlotCache()->cacheObject($plot->toString(), $plot);
            return $plot;
        }
        return null;
    }

    public function savePlot(Plot $plot) : bool {
        $this->setPlot->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlot->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlot->bindValue(":biomeID", $plot->getBiomeID(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":ownerUUID", $plot->getOwnerUUID(), SQLITE3_TEXT);
        $this->setPlot->bindValue(":claimTime", $plot->getClaimTime(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":alias", $plot->getAlias(), SQLITE3_TEXT);

        $this->setPlot->reset();
        $result = $this->setPlot->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }

    public function deletePlot(Plot $plot) : bool {
        $this->deletePlot->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlot->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlot->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlot->reset();
        $result = $this->deletePlot->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->removeObjectFromCache($plot->toString());
        if ($plot->getMergePlots() !== null) {
            foreach ($plot->getMergePlots() as $key => $mergedPlot) {
                $this->getPlotCache()->removeObjectFromCache($key);
            }
        }
        return true;
    }


    /**
     * @return MergePlot[] | null
     */
    public function getMergePlots(Plot $plot) : ?array {
        $this->getMergedPlots->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getMergedPlots->bindValue(":originX", $plot->getX(), SQLITE3_INTEGER);
        $this->getMergedPlots->bindValue(":originZ", $plot->getZ(), SQLITE3_INTEGER);

        $this->getMergedPlots->reset();
        $result = $this->getMergedPlots->execute();
        if (!$result instanceof SQLite3Result) return null;

        $mergedPlots = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $mergedPlot = new MergePlot($plot->getWorldName(), $var["mergeX"], $var["mergeZ"], $plot->getX(), $plot->getZ());
            $this->getPlotCache()->cacheObject($mergedPlot->toString(), $mergedPlot);
            $mergedPlots[$mergedPlot->toString()] = $mergedPlot;
        }
        return $mergedPlots;
    }

    public function getMergeOrigin(BasePlot $plot) : ?Plot {
        if ($plot instanceof MergePlot) {
            return $this->getPlot($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }

        $this->getOriginPlot->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getOriginPlot->bindValue(":mergeX", $plot->getX(), SQLITE3_INTEGER);
        $this->getOriginPlot->bindValue(":mergeZ", $plot->getZ(), SQLITE3_INTEGER);

        $this->getOriginPlot->reset();
        $result = $this->getOriginPlot->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $mergedPlot = MergePlot::fromBasePlot($plot, $var["originX"], $var["originZ"]);
            $this->getPlotCache()->cacheObject($mergedPlot->toString(), $mergedPlot);
            return $mergedPlot->toPlot();
        }
        if ($plot instanceof Plot) return $plot;
        return $this->getPlot($plot->getWorldName(), $plot->getX(), $plot->getZ());
    }

    /**
     * @param BasePlot ...$plots
     */
    public function mergePlots(Plot $origin, BasePlot ...$plots) : bool {
        if ($origin->getMergePlots() === null) return false;

        foreach ($plots as $plot) {
            $this->addMergedPlot->bindValue(":worldName", $origin->getWorldName(), SQLITE3_TEXT);
            $this->addMergedPlot->bindValue(":originX", $origin->getX(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":originZ", $origin->getZ(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":mergeX", $plot->getX(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":mergeZ", $plot->getZ(), SQLITE3_INTEGER);

            $this->addMergedPlot->reset();
            $result = $this->addMergedPlot->execute();
            if (!$result instanceof SQLite3Result) return false;
        }

        foreach ($plots as $plot) {
            $mergedPlot = MergePlot::fromBasePlot($plot, $origin->getX(), $origin->getZ());
            $this->getPlotCache()->cacheObject($mergedPlot->toString(), $mergedPlot);
        }
        $this->getPlotCache()->cacheObject($origin->toString(), $origin);
        return true;
    }


    /**
     * code (modified here) from @see https://github.com/jasonwynn10/MyPlot
     */
    public function getNextFreePlot(string $worldName, int $limitXZ = 0) : ?Plot {
        $i = 0;

        $this->getExistingPlotXZ->bindValue(":worldName", $worldName, SQLITE3_TEXT);
        $this->getExistingPlotXZ->bindParam(":number", $i, SQLITE3_INTEGER);

        for(; $limitXZ <= 0 or $i < $limitXZ; $i++) {
            $this->getExistingPlotXZ->reset();
            $result = $this->getExistingPlotXZ->execute();
            if (!$result instanceof SQLite3Result) continue;

            $plots = [];
            while ($val = $result->fetchArray(SQLITE3_NUM)) {
                $plots[$val[0]][$val[1]] = true;
            }
            if (count($plots) === max(1, 8 * $i)) continue;
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


    /**
     * @return PlotPlayer[] | null
     */
    public function getPlotPlayers(Plot $plot) : ?array {
        $this->getPlotPlayers->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getPlotPlayers->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->getPlotPlayers->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->getPlotPlayers->reset();
        $result = $this->getPlotPlayers->execute();
        if (!$result instanceof SQLite3Result) return null;

        $plotPlayers = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plotPlayer = new PlotPlayer($var["playerUUID"], $var["state"], $var["addTime"]);
            $plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
        }
        return $plotPlayers;
    }

    public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : bool {
        if (!$plot->addPlotPlayer($plotPlayer)) return false;

        $this->setPlotPlayer->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlotPlayer->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlotPlayer->bindValue(":playerUUID", $plotPlayer->getPlayerUUID(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":state", $plotPlayer->getState(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":addTime", $plotPlayer->getAddTime(), SQLITE3_INTEGER);

        $this->setPlotPlayer->reset();
        $result = $this->setPlotPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }

    public function deletePlotPlayer(Plot $plot, string $playerUUID) : bool {
        if (!$plot->removePlotPlayer($playerUUID)) return false;

        $this->deletePlotPlayer->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlotPlayer->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlotPlayer->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlotPlayer->bindValue(":playerUUID", $playerUUID, SQLITE3_TEXT);

        $this->deletePlotPlayer->reset();
        $result = $this->deletePlotPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }


    /**
     * @return BaseFlag[] | null
     * @throws FlagParseException
     */
    public function getPlotFlags(Plot $plot) : ?array {
        $this->getPlotFlags->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getPlotFlags->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->getPlotFlags->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->getPlotFlags->reset();
        $result = $this->getPlotFlags->execute();
        if (!$result instanceof SQLite3Result) return null;

        $flags = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $flag = FlagManager::getInstance()->getFlagByID($var["ID"]);
            if ($flag === null) {
                continue;
            }
            $flags[$flag->getID()] = $flag->flagOf($flag->parse($var["value"]));
        }
        return $flags;
    }

    public function savePlotFlag(Plot $plot, BaseFlag $flag) : bool {
        if ($flag->getValue() === null) return false;
        if (!$plot->addFlag($flag)) return false;

        $this->setPlotFlag->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlotFlag->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlotFlag->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlotFlag->bindValue(":ID", $flag->getID(), SQLITE3_TEXT);
        $this->setPlotFlag->bindValue(":value", $flag->toString(), SQLITE3_TEXT);

        $this->setPlotFlag->reset();
        $result = $this->setPlotFlag->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }

    public function deletePlotFlag(Plot $plot, string $flagID) : bool {
        if (!$plot->removeFlag($flagID)) return false;

        $this->deletePlotFlag->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlotFlag->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlotFlag->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlotFlag->bindValue(":ID", $flagID, SQLITE3_TEXT);

        $this->deletePlotFlag->reset();
        $result = $this->deletePlotFlag->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }


    /**
     * @return PlotRate[] | null
     */
    public function getPlotRates(Plot $plot) : ?array {
        $this->getPlotRates->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getPlotRates->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->getPlotRates->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->getPlotRates->reset();
        $result = $this->getPlotRates->execute();
        if (!$result instanceof SQLite3Result) return null;

        $plotRates = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plotRate = new PlotRate(
                $var["rate"],
                $var["playerUUID"],
                $var["rateTime"],
                $var["comment"] ?? null
            );
            $plotRates[$plotRate->toString()] = $plotRate;
        }
        return $plotRates;
    }

    public function savePlotRate(Plot $plot, PlotRate $plotRate) : bool {
        if (!$plot->addPlotRate($plotRate)) return false;

        $this->setPlotRate->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlotRate->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlotRate->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlotRate->bindValue(":rate", $plotRate->getRate(), SQLITE3_NUM);
        $this->setPlotRate->bindValue(":playerUUID", $plotRate->getPlayerUUID(), SQLITE3_TEXT);
        $this->setPlotRate->bindValue(":rateTime", $plotRate->getRateTime(), SQLITE3_INTEGER);
        $this->setPlotRate->bindValue(":comment", $plotRate->getComment(), SQLITE3_TEXT);

        $this->setPlotRate->reset();
        $result = $this->setPlotRate->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->getPlotCache()->cacheObject($plot->toString(), $plot);
        return true;
    }


    public function close() : bool {
        return $this->database->close();
    }
}