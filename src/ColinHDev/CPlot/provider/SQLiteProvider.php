<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\worlds\WorldSettings;
use Exception;
use SQLite3;
use SQLite3Stmt;
use SQLite3Result;

class SQLiteProvider extends DataProvider {

    /** @var SQLite3 $database */
    private SQLite3 $database;

    /** @var SQLite3Stmt $getPlayerNameByUUID */
    private SQLite3Stmt $getPlayerNameByUUID;
    /** @var SQLite3Stmt $getPlayerUUIDByName */
    private SQLite3Stmt $getPlayerUUIDByName;
    /** @var SQLite3Stmt $setPlayer */
    private SQLite3Stmt $setPlayer;

    /** @var SQLite3Stmt $getPlayerSettings */
    private SQLite3Stmt $getPlayerSettings;
    /** @var SQLite3Stmt $setPlayerSetting */
    private SQLite3Stmt $setPlayerSetting;
    /** @var SQLite3Stmt $deletePlayerSetting */
    private SQLite3Stmt $deletePlayerSetting;

    /** @var SQLite3Stmt $getWorld */
    private SQLite3Stmt $getWorld;
    /** @var SQLite3Stmt $setWorld */
    private SQLite3Stmt $setWorld;

    /** @var SQLite3Stmt $getPlot */
    private SQLite3Stmt $getPlot;
    /** @var SQLite3Stmt $getPlotByOwnerUUID */
    private SQLite3Stmt $getPlotByOwnerUUID;
    /** @var SQLite3Stmt $getPlotByAlias */
    private SQLite3Stmt $getPlotByAlias;
    /** @var SQLite3Stmt $getPlotXZ */
    private SQLite3Stmt $getPlotXZ;
    /** @var SQLite3Stmt $setPlot */
    private SQLite3Stmt $setPlot;
    /** @var SQLite3Stmt $deletePlot */
    private SQLite3Stmt $deletePlot;

    /** @var SQLite3Stmt $setMergedPlot */
    private SQLite3Stmt $setMergedPlot;
    /** @var SQLite3Stmt $getBasePlot */
    private SQLite3Stmt $getBasePlot;
    /** @var SQLite3Stmt $getMergedPlots */
    private SQLite3Stmt $getMergedPlots;

    /** @var SQLite3Stmt $getPlotPlayers */
    private SQLite3Stmt $getPlotPlayers;
    /** @var SQLite3Stmt $addPlotPlayer */
    private SQLite3Stmt $addPlotPlayer;
    /** @var SQLite3Stmt $deletePlotPlayer */
    private SQLite3Stmt $deletePlotPlayer;

    /** @var SQLite3Stmt $getPlotFlags */
    private SQLite3Stmt $getPlotFlags;
    /** @var SQLite3Stmt $setPlotFlag */
    private SQLite3Stmt $setPlotFlag;
    /** @var SQLite3Stmt $deletePlotFlag */
    private SQLite3Stmt $deletePlotFlag;

    /** @var SQLite3Stmt $getPlotRates */
    private SQLite3Stmt $getPlotRates;
    /** @var SQLite3Stmt $addPlotRate */
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
            playerUUID VARCHAR(512), playerName VARCHAR(512), 
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
            playerUUID VARCHAR(512), setting VARCHAR(256), value VARCHAR(512),
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
                worldName VARCHAR(512),
                schematicRoad VARCHAR(512), schematicMergeRoad VARCHAR(512), schematicPlot VARCHAR(512),
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
            worldName VARCHAR(512), x INTEGER, z INTEGER,
            ownerUUID VARCHAR(512), alias VARCHAR(128), claimTime INTEGER,
            PRIMARY KEY (worldName, x, z),
            FOREIGN KEY (worldName) REFERENCES worlds (worldName) ON DELETE CASCADE,
            FOREIGN KEY (ownerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT ownerUUID, alias, claimTime FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT worldName, x, z, alias, claimTime FROM plots WHERE ownerUUID = :ownerUUID;";
        $this->getPlotByOwnerUUID = $this->createSQLite3Stmt($sql);
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
            "INSERT OR REPLACE INTO plots (worldName, x, z, ownerUUID, alias, claimTime) VALUES (:worldName, :x, :z, :ownerUUID, :alias, :claimTime);";
        $this->setPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plots WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->deletePlot = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS mergedPlots (
            worldName VARCHAR(512), baseX INTEGER, baseZ INTEGER, mergedX INTEGER, mergedZ INTEGER, 
            PRIMARY KEY (worldName, baseX, baseZ, mergedX, mergedZ),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (baseX) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (baseZ) REFERENCES plots (z) ON DELETE CASCADE,
            FOREIGN KEY (mergedX) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (mergedZ) REFERENCES plots (z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "INSERT OR REPLACE INTO mergedPlots (worldName, baseX, baseZ, mergedX, mergedZ) VALUES (:worldName, :baseX, :baseZ, :mergedX, :mergedZ);";
        $this->setMergedPlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT * FROM plots LEFT JOIN mergedPlots ON mergedPlots.worldName = plots.worldName AND baseX = :baseX AND baseZ = :baseZ WHERE mergedPlots.worldName = :worldName AND mergedX = :mergedX AND mergedZ = :mergedZ;";
        $this->getBasePlot = $this->createSQLite3Stmt($sql);
        $sql =
            "SELECT * FROM plots LEFT JOIN mergedPlots ON mergedPlots.worldName = plots.worldName AND mergedX = :mergedX AND mergedZ = :mergedZ WHERE mergedPlots.worldName = :worldName AND baseX = :baseX AND baseZ = :baseZ;";
        $this->getMergedPlots = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotPlayers (
            worldName VARCHAR(512), x INTEGER, z INTEGER, playerUUID VARCHAR(512), playerState INTEGER, addTime INTEGER,
            PRIMARY KEY (worldName, x, z, playerUUID),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE,
            FOREIGN KEY (playerUUID) REFERENCES players (playerUUID) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT playerUUID, addTime FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z AND playerState = :playerState;";
        $this->getPlotPlayers = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plotPlayers (worldName, x, z, playerUUID, playerState, addTime) VALUES (:worldName, :x, :z, :playerUUID, :playerState, :addTime);";
        $this->addPlotPlayer = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotPlayers WHERE worldName = :worldName AND x = :x AND z = :z AND playerUUID = :playerUUID;";
        $this->deletePlotPlayer = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotFlags (
            worldName VARCHAR(512), x INTEGER, z INTEGER, setting VARCHAR(256), value VARCHAR(512),
            PRIMARY KEY (worldName, x, z, setting),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE
            )";
        $this->database->exec($sql);
        $sql =
            "SELECT setting, value FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z;";
        $this->getPlotFlags = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT OR REPLACE INTO plotFlags (worldName, x, z, setting, value) VALUES (:worldName, :x, :z, :setting, :value);";
        $this->setPlotFlag = $this->createSQLite3Stmt($sql);
        $sql =
            "DELETE FROM plotFlags WHERE worldName = :worldName AND x = :x AND z = :z AND setting = :setting;";
        $this->deletePlotFlag = $this->createSQLite3Stmt($sql);

        $sql =
            "CREATE TABLE IF NOT EXISTS plotRates (
            worldName VARCHAR(512), x INTEGER, z INTEGER, rate FLOAT, playerUUID VARCHAR(512), rateTime INTEGER,
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

        $this->getWorld->bindValue(':worldName', $name, SQLITE3_TEXT);

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
        $settingsArray = $settings->toArray();

        $stmt = $this->setWorld;

        $stmt->bindValue(":worldName", $name, SQLITE3_TEXT);

        $stmt->bindValue(":schematicRoad", $settingsArray["schematicRoad"], SQLITE3_TEXT);
        $stmt->bindValue(":schematicMergeRoad", $settingsArray["schematicMergeRoad"], SQLITE3_TEXT);
        $stmt->bindValue(":schematicPlot", $settingsArray["schematicPlot"], SQLITE3_TEXT);

        $stmt->bindValue(":sizeRoad", $settingsArray["sizeRoad"], SQLITE3_INTEGER);
        $stmt->bindValue(":sizePlot", $settingsArray["sizePlot"], SQLITE3_INTEGER);
        $stmt->bindValue(":sizeGround", $settingsArray["sizeGround"], SQLITE3_INTEGER);

        $stmt->bindValue(":blockRoad", $settingsArray["blockRoad"], SQLITE3_TEXT);
        $stmt->bindValue(":blockBorder", $settingsArray["blockBorder"], SQLITE3_TEXT);
        $stmt->bindValue(":blockBorderOnClaim", $settingsArray["blockBorderOnClaim"], SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotFloor", $settingsArray["blockPlotFloor"], SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotFill", $settingsArray["blockPlotFill"], SQLITE3_TEXT);
        $stmt->bindValue(":blockPlotBottom", $settingsArray["blockPlotBottom"], SQLITE3_TEXT);

        $stmt->reset();
        $result = $stmt->execute();
        if (!$result instanceof SQLite3Result) return false;
        $this->cacheWorld($name, $settings);
        return true;
    }

    public function close() : bool {
        return $this->database->close();
    }
}