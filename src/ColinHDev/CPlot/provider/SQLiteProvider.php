<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\worlds\WorldSettings;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\FlagManager;
use ColinHDev\CPlotAPI\MergedPlot;
use ColinHDev\CPlotAPI\Plot;
use Exception;
use pocketmine\data\bedrock\BiomeIds;
use SQLite3;
use SQLite3Stmt;
use SQLite3Result;

class SQLiteProvider extends DataProvider {

    private SQLite3 $database;

    private SQLite3Stmt $getPlayerNameByUUID;
    private SQLite3Stmt $getPlayerUUIDByName;
    private SQLite3Stmt $setPlayer;

    private SQLite3Stmt $getPlayerSettings;
    private SQLite3Stmt $setPlayerSetting;
    private SQLite3Stmt $deletePlayerSetting;

    private SQLite3Stmt $getWorld;
    private SQLite3Stmt $setWorld;

    private SQLite3Stmt $getPlot;
    private SQLite3Stmt $getPlotsByOwnerUUID;
    private SQLite3Stmt $getPlotByAlias;
    private SQLite3Stmt $getPlotXZ;
    private SQLite3Stmt $setPlot;
    private SQLite3Stmt $deletePlot;

    private SQLite3Stmt $getOriginPlot;
    private SQLite3Stmt $getMergedPlots;
    private SQLite3Stmt $addMergedPlot;
    private SQLite3Stmt $deleteMergedPlots;

    private SQLite3Stmt $getPlotPlayers;
    private SQLite3Stmt $addPlotPlayer;
    private SQLite3Stmt $deletePlotPlayer;

    private SQLite3Stmt $getPlotFlags;
    private SQLite3Stmt $setPlotFlag;
    private SQLite3Stmt $deletePlotFlags;
    private SQLite3Stmt $deletePlotFlag;

    private SQLite3Stmt $getPlotRates;
    private SQLite3Stmt $addPlotRate;

    /**
     * SQLiteProvider constructor.
     * @param array $settings
     * @throws Exception
     */
    public function __construct(array $settings) {
        $this->database = new SQLite3($settings["folder"] . $settings["file"]);

        $sql =
            "CREATE TABLE IF NOT EXISTS players (
            playerUUID VARCHAR(256), playerName VARCHAR(256), 
            PRIMARY KEY (playerUUID)
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT playerName FROM players WHERE playerUUID = :playerUUID;";
        $this->getPlayerNameByUUID = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT playerUUID FROM players WHERE playerName = :playerName;";
        $this->getPlayerUUIDByName = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO players (playerUUID, playerName) VALUES (:playerUUID, :playerName);";
        $this->setPlayer = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS playerSettings (
            playerUUID VARCHAR(256), setting VARCHAR(256), value VARCHAR(512),
            PRIMARY KEY (playerUUID, setting), 
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT setting, value FROM playerSettings WHERE playerUUID = :playerUUID;";
        $this->getPlayerSettings = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO playerSettings (playerUUID, setting, value) VALUES (:playerUUID, :setting, :value);";
        $this->setPlayerSetting = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM playerSettings WHERE playerUUID = :playerUUID AND setting = :setting;";
        $this->deletePlayerSetting = $this->createSQLite3Stmt($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS worlds (
                worldName VARCHAR(256),
                schematicRoad VARCHAR(256), schematicMergeRoad VARCHAR(256), schematicPlot VARCHAR(256),
                sizeRoad INTEGER, sizePlot INTEGER, sizeGround INTEGER,
                blockRoad VARCHAR(32), blockBorder VARCHAR(32), blockBorderOnClaim VARCHAR(32), 
                blockPlotFloor VARCHAR(32), blockPlotFill VARCHAR(32), blockPlotBottom VARCHAR(32), 
                PRIMARY KEY (worldName)
            );";
        $this->database->exec($sql);
        $sql =
            "SELECT * FROM worlds WHERE worldName = :worldName;";
        $this->getWorld = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO worlds 
            (worldName, schematicRoad, schematicMergeRoad, schematicPlot, sizeRoad, sizePlot, sizeGround, blockRoad, blockBorder, blockBorderOnClaim, blockPlotFloor, blockPlotFill, blockPlotBottom) VALUES 
            (:worldName, :schematicRoad, :schematicMergeRoad, :schematicPlot, :sizeRoad, :sizePlot, :sizeGround, :blockRoad, :blockBorder, :blockBorderOnClaim, :blockPlotFloor, :blockPlotFill, :blockPlotBottom);";
        $this->setWorld = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plots (
            worldName VARCHAR(256), x INTEGER, z INTEGER,
            ownerUUID VARCHAR(256), claimTime INTEGER, alias VARCHAR(128),
            PRIMARY KEY (worldName, x, z),
            FOREIGN KEY (worldName) REFERENCES worlds (worldName) ON DELETE CASCADE,
            FOREIGN KEY (ownerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT ownerUUID, claimTime, alias FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT worldName, x, z, claimTime, alias FROM plots WHERE ownerUUID = :ownerUUID;";
        $this->getPlotsByOwnerUUID = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT worldName, x, z, ownerUUID, claimTime FROM plots WHERE alias = :alias;";
        $this->getPlotByAlias = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT x, z FROM plots WHERE (
                worldName = :worldName AND (
                    (abs(x) = :number AND abs(z) <= :number) OR
                    (abs(z) = :number AND abs(x) <= :number)
                )
			);";
        $this->getPlotXZ = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO plots (worldName, x, z, ownerUUID, claimTime, alias) VALUES (:worldName, :x, :z, :ownerUUID, :claimTime, :alias);";
        $this->setPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->deletePlot = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS mergedPlots (
            worldName VARCHAR(256), originX INTEGER, originZ INTEGER, mergedX INTEGER, mergedZ INTEGER, 
            PRIMARY KEY (worldName, originX, originZ, mergedX, mergedZ),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (originX) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (originZ) REFERENCES plots (z) ON DELETE CASCADE,
            FOREIGN KEY (mergedX) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (mergedZ) REFERENCES plots (z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT originX, originZ FROM mergedPlots WHERE worldName = :worldName AND mergedX = :mergedX AND mergedZ = :mergedZ;";
        $this->getOriginPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT mergedX, mergedZ FROM mergedPlots WHERE worldName = :worldName AND originX = :originX AND originZ = :originZ;";
        $this->getMergedPlots = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO mergedPlots (worldName, originX, originZ, mergedX, mergedZ) VALUES (:worldName, :originX, :originZ, :mergedX, :mergedZ);";
        $this->addMergedPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM mergedPlots WHERE worldName = :worldName AND originX = :originX AND originZ = :originZ;";
        $this->deleteMergedPlots = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotPlayers (
            worldName VARCHAR(256), x INTEGER, z INTEGER, playerUUID VARCHAR(256), state INTEGER, addTime INTEGER,
            PRIMARY KEY (worldName, x, z, playerUUID),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE,
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT playerUUID, addTime FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z AND state = :state;";
        $this->getPlotPlayers = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plotPlayers (worldName, x, z, playerUUID, state, addTime) VALUES (:worldName, :x, :z, :playerUUID, :state, :addTime);";
        $this->addPlotPlayer = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z AND playerUUID = :playerUUID;";
        $this->deletePlotPlayer = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotFlags (
            worldName VARCHAR(256), x INTEGER, z INTEGER, ID VARCHAR(256), value VARCHAR(512),
            PRIMARY KEY (worldName, x, z, ID),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT ID, value FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotFlags = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO plotFlags (worldName, x, z, ID, value) VALUES (:worldName, :x, :z, :ID, :value);";
        $this->setPlotFlag = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->deletePlotFlags = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z AND ID = :ID;";
        $this->deletePlotFlag = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotRates (
            worldName VARCHAR(256), x INTEGER, z INTEGER, rate FLOAT, playerUUID VARCHAR(256), rateTime INTEGER,
            PRIMARY KEY (worldName, x, z, playerUUID, rateTime),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE,
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT rate, playerUUID, rateTime FROM plotRates WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotRates = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plotRates (worldName, x, z, rate, playerUUID, rateTime) VALUES (:worldName, :x, :z, :rate, :playerUUID, :rateTime)";
        $this->addPlotRate = $this->createSQLite3Stmt($sql);
    }

    /**
     * @param string $sql
     * @return SQLite3Stmt
     * @throws Exception
     */
    private function createSQLite3Stmt(string $sql) : SQLite3Stmt {
        $stmt = $this->database->prepare($sql);
        if ($stmt === false) {
            throw new Exception("#" . $this->database->lastErrorCode() . ": " . $this->database->lastErrorMsg());
        }
        return $stmt;
    }


    /**
     * @param string $name
     * @return WorldSettings | null
     */
    public function getWorld(string $name) : ?WorldSettings {
        if (($settings = $this->getWorldFromCache($name)) !== null) return $settings;

        $this->getWorld->bindValue(":worldName", $name, SQLITE3_TEXT);

        $this->getWorld->reset();
        $results = $this->getWorld->execute();

        if ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $settings = WorldSettings::fromArray($result);
            $this->cacheWorld($name, $settings);
            return $settings;
        }
        return null;
    }

    /**
     * @param string        $name
     * @param WorldSettings $settings
     * @return bool
     */
    public function addWorld(string $name, WorldSettings $settings) : bool {
        $stmt = $this->setWorld;

        $stmt->bindValue(":worldName", $name, SQLITE3_TEXT);

        $stmt->bindValue(":schematicRoad", $settings->getSchematicRoad(), SQLITE3_TEXT);
        $stmt->bindValue(":schematicMergeRoad", $settings->getSchematicMergeRoad(), SQLITE3_TEXT);
        $stmt->bindValue(":schematicPlot", $settings->getSchematicPlot(), SQLITE3_TEXT);

        $stmt->bindValue(":sizeRoad", $settings->getSizeRoad(), SQLITE3_INTEGER);
        $stmt->bindValue(":sizePlot", $settings->getSizePlot(), SQLITE3_INTEGER);
        $stmt->bindValue(":sizeGround", $settings->getSizeGround(), SQLITE3_INTEGER);

        $stmt->bindValue(":blockRoad", $settings->getBlockRoadString(), SQLITE3_TEXT);
        $stmt->bindValue(":blockBorder", $settings->getBlockBorderString(), SQLITE3_TEXT);
        $stmt->bindValue(":blockBorderOnClaim", $settings->getBlockBorderOnClaimString(), SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotFloor", $settings->getBlockPlotFloorString(), SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotFill", $settings->getBlockPlotFillString(), SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotBottom", $settings->getBlockPlotBottomString(), SQLITE3_TEXT);

        $stmt->reset();
        $result = $stmt->execute();
        if (!$result instanceof SQLite3Result) return false;
        $this->cacheWorld($name, $settings);
        return true;
    }

    /**
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     * @return Plot | null
     */
    public function getPlot(string $worldName, int $x, int $z) : ?Plot {
        $plot = $this->getPlotFromCache($worldName, $x, $z);
        if ($plot !== null) {
            if (!$plot instanceof Plot) return null;
            return $plot;
        }

        $this->getPlot->bindValue(":worldName", $worldName, SQLITE3_TEXT);
        $this->getPlot->bindValue(":x", $x, SQLITE3_INTEGER);
        $this->getPlot->bindValue(":z", $z, SQLITE3_INTEGER);

        $this->getPlot->reset();
        $results = $this->getPlot->execute();
        if ($results === false) return null;

        if ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $worldName, $x, $z,
                $result["biomeID"] ?? BiomeIds::PLAINS, $result["ownerUUID"] ?? null, $result["claimTime"] ?? null, $result["alias"] ?? null
            );
            $this->cachePlot($plot);
            return $plot;
        }
        return new Plot($worldName, $x, $z);
    }

    /**
     * @param string $ownerUUID
     * @return Plot[]
     */
    public function getPlotsByOwnerUUID(string $ownerUUID) : array {
        $this->getPlotsByOwnerUUID->bindValue(":ownerUUID", $ownerUUID, SQLITE3_TEXT);

        $this->getPlotsByOwnerUUID->reset();
        $results = $this->getPlotsByOwnerUUID->execute();

        $plots = [];
        while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $plots[] = new Plot(
                $result["worldName"], $result["x"], $result["z"],
                $result["biomeID"] ?? BiomeIds::PLAINS, $ownerUUID, $result["claimTime"] ?? null, $result["alias"] ?? null
            );
        }
        return $plots;
    }

    /**
     * @param string $alias
     * @return Plot | null
     */
    public function getPlotByAlias(string $alias) : ?Plot {
        $this->getPlotByAlias->bindValue(":alias", $alias, SQLITE3_TEXT);

        $this->getPlotByAlias->reset();
        $results = $this->getPlotByAlias->execute();

        if ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $result["worldName"], $result["x"], $result["z"],
                $result["biomeID"] ?? BiomeIds::PLAINS, $result["ownerUUID"] ?? null, $result["claimTime"] ?? null, $alias
            );
            $this->cachePlot($plot);
            return $plot;
        }
        return null;
    }

    /**
     * @param Plot      $plot
     * @return MergedPlot[] | null
     */
    public function getMergedPlots(Plot $plot) : ?array {
        $this->getMergedPlots->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getMergedPlots->bindValue(":originX", $plot->getX(), SQLITE3_INTEGER);
        $this->getMergedPlots->bindValue(":originZ", $plot->getZ(), SQLITE3_INTEGER);

        $this->getMergedPlots->reset();
        $results = $this->getMergedPlots->execute();
        if ($results === false) return null;

        $mergedPlots = [];
        while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $mergedPlot = new MergedPlot($plot->getWorldName(), $result["mergedX"], $result["mergedZ"], $plot->getX(), $plot->getZ());
            $this->cachePlot($mergedPlot);
            $mergedPlots[$mergedPlot->toString()] = $mergedPlot;
        }
        return $mergedPlots;
    }

    /**
     * @param BasePlot $plot
     * @return Plot | null
     */
    public function getMergeOrigin(BasePlot $plot) : ?Plot {
        if ($plot instanceof MergedPlot) {
            return $this->getPlot($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }

        $this->getOriginPlot->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getOriginPlot->bindValue(":mergedX", $plot->getX(), SQLITE3_INTEGER);
        $this->getOriginPlot->bindValue(":mergedZ", $plot->getZ(), SQLITE3_INTEGER);

        $this->getOriginPlot->reset();
        $results = $this->getOriginPlot->execute();
        if ($results === false) return null;

        if ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $this->cachePlot(MergedPlot::fromBasePlot($plot, $result["originX"], $result["originZ"]));
            return $this->getPlot($plot->getWorldName(), $result["originX"], $result["originZ"]);
        }
        if ($plot instanceof Plot) return $plot;
        return $this->getPlot($plot->getWorldName(), $plot->getX(), $plot->getZ());
    }

    /**
     * @param Plot $origin
     * @param BasePlot ...$plots
     * @return bool
     */
    public function mergePlots(Plot $origin, BasePlot ...$plots) : bool {
        if ($origin->getMergedPlots() === null) return false;

        foreach ($plots as $plot) {
            $this->addMergedPlot->bindValue(":worldName", $origin->getWorldName(), SQLITE3_TEXT);
            $this->addMergedPlot->bindValue(":originX", $origin->getX(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":originZ", $origin->getZ(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":mergedX", $plot->getX(), SQLITE3_INTEGER);
            $this->addMergedPlot->bindValue(":mergedZ", $plot->getZ(), SQLITE3_INTEGER);

            $this->addMergedPlot->reset();
            $result = $this->addMergedPlot->execute();
            if (!$result instanceof SQLite3Result) return false;
        }

        foreach ($plots as $plot) {
            $this->cachePlot(MergedPlot::fromBasePlot($plot, $origin->getX(), $origin->getZ()));
        }
        $this->cachePlot($origin);
        return true;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public function deleteMergedPlots(Plot $plot) : bool {
        if ($plot->getMergedPlots() === null) return false;

        $this->deleteMergedPlots->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deleteMergedPlots->bindValue(":originX", $plot->getX(), SQLITE3_INTEGER);
        $this->deleteMergedPlots->bindValue(":originZ", $plot->getZ(), SQLITE3_INTEGER);

        $this->deleteMergedPlots->reset();
        $result = $this->deleteMergedPlots->execute();
        if (!$result instanceof SQLite3Result) return false;

        foreach ($plot->getMergedPlots() as $mergedPlot) {
            $this->removePlotFromCache($mergedPlot);
        }
        $this->removePlotFromCache($plot);
        return true;
    }

    /**
     * @param Plot $plot
     * @return BaseFlag | null
     */
    public function getPlotFlags(Plot $plot) : ?array {
        $this->getPlotFlags->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->getPlotFlags->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->getPlotFlags->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->getPlotFlags->reset();
        $results = $this->getPlotFlags->execute();
        if ($results === false) return null;

        $flags = [];
        while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
            $flag = FlagManager::getInstance()->getFlagByID($result["ID"]);
            if ($flag === null) continue;
            $flag->unserializeValue($result["value"]);
            $flags[$flag->getID()] = $flag;
        }
        return $flags;
    }

    /**
     * @param Plot      $plot
     * @param BaseFlag  $flag
     * @return bool
     */
    public function savePlotFlag(Plot $plot, BaseFlag $flag) : bool {
        if ($flag->getValue() === null) return false;
        if (!$plot->addFlag($flag)) return false;

        $stmt = $this->setPlotFlag;

        $stmt->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $stmt->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $stmt->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $stmt->bindValue(":ID", $flag->getID(), SQLITE3_TEXT);
        $stmt->bindValue(":value", $flag->serializeValue(), SQLITE3_TEXT);

        $stmt->reset();
        $result = $stmt->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param Plot      $plot
     * @param string    $flagID
     * @return bool
     */
    public function deletePlotFlag(Plot $plot, string $flagID) : bool {
        if (!$plot->removeFlag($flagID)) return false;

        $stmt = $this->deletePlotFlag;

        $stmt->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $stmt->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $stmt->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $stmt->bindValue(":ID", $flagID, SQLITE3_TEXT);

        $stmt->reset();
        $result = $stmt->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public function deletePlotFlags(Plot $plot) : bool {
        $stmt = $this->deletePlotFlags;

        $stmt->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $stmt->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $stmt->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $stmt->reset();
        $result = $stmt->execute();
        if (!$result instanceof SQLite3Result) return false;

        $plot->setFlags(null);
        $this->cachePlot($plot);
        return true;
    }

    /**
     * @return bool
     */
    public function close() : bool {
        return $this->database->close();
    }
}