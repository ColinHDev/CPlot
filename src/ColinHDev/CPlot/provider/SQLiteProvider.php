<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlotAPI\PlotPlayer;
use ColinHDev\CPlotAPI\PlotRate;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
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
    private SQLite3Stmt $setPlotPlayer;
    private SQLite3Stmt $deletePlotPlayer;

    private SQLite3Stmt $getPlotFlags;
    private SQLite3Stmt $setPlotFlag;
    private SQLite3Stmt $deletePlotFlags;
    private SQLite3Stmt $deletePlotFlag;

    private SQLite3Stmt $getPlotRates;
    private SQLite3Stmt $setPlotRate;

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
            "INSERT INTO players (playerUUID, playerName) VALUES (:playerUUID, :playerName) ON CONFLICT (playerUUID) DO UPDATE SET playerName = excluded.playerName;";
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
            biomeID INTEGER, ownerUUID VARCHAR(256), claimTime INTEGER, alias VARCHAR(128),
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
            "SELECT x, z FROM plots WHERE (
                worldName = :worldName AND (
                    (abs(x) = :number AND abs(z) <= :number) OR
                    (abs(z) = :number AND abs(x) <= :number)
                )
			);";
        $this->getPlotXZ = $this->createSQLite3Stmt($sql);
        $sql =
            "INSERT INTO plots (worldName, x, z, biomeID, ownerUUID, claimTime, alias) VALUES (:worldName, :x, :z, :biomeID, :ownerUUID, :claimTime, :alias) ON CONFLICT DO UPDATE SET biomeID = excluded.biomeID, ownerUUID = excluded.ownerUUID, claimTime = excluded.claimTime, alias = excluded.alias;";
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
            FOREIGN KEY (originZ) REFERENCES plots (z) ON DELETE CASCADE
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
            worldName VARCHAR(256), x INTEGER, z INTEGER, playerUUID VARCHAR(256), state VARCHAR(32), addTime INTEGER,
            PRIMARY KEY (worldName, x, z, playerUUID),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE,
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
            worldName VARCHAR(256), x INTEGER, z INTEGER, rate DECIMAL(4, 2), playerUUID VARCHAR(256), rateTime INTEGER, comment TEXT,
            PRIMARY KEY (worldName, x, z, playerUUID, rateTime),
            FOREIGN KEY (worldName) REFERENCES plots (worldName) ON DELETE CASCADE,
            FOREIGN KEY (x) REFERENCES plots (x) ON DELETE CASCADE,
            FOREIGN KEY (z) REFERENCES plots (z) ON DELETE CASCADE,
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
     * @param string $playerUUID
     * @return string | null
     */
    public function getPlayerNameByUUID(string $playerUUID) : ?string {
        $this->getPlayerNameByUUID->bindValue(":playerUUID", $playerUUID, SQLITE3_TEXT);

        $this->getPlayerNameByUUID->reset();
        $result = $this->getPlayerNameByUUID->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            return $var["playerName"];
        }
        return null;
    }

    /**
     * @param string $playerName
     * @return string | null
     */
    public function getPlayerUUIDByName(string $playerName) : ?string {
        $this->getPlayerUUIDByName->bindValue(":playerName", $playerName, SQLITE3_TEXT);

        $this->getPlayerNameByUUID->reset();
        $result = $this->getPlayerNameByUUID->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            return $var["playerUUID"];
        }
        return null;
    }

    /**
     * @param string    $playerUUID
     * @param string    $playerName
     * @return bool
     */
    public function setPlayer(string $playerUUID, string $playerName) : bool {
        $this->setPlayer->bindValue(":playerUUID", $playerUUID, SQLITE3_TEXT);
        $this->setPlayer->bindValue(":playerName", $playerName, SQLITE3_TEXT);

        $this->setPlayer->reset();
        $result = $this->setPlayer->execute();
        return $result instanceof SQLite3Result;
    }


    /**
     * @param string $worldName
     * @return WorldSettings | null
     */
    public function getWorld(string $worldName) : ?WorldSettings {
        $worldSettings = $this->getWorldFromCache($worldName);
        if ($worldSettings !== null) return $worldSettings;

        $this->getWorld->bindValue(":worldName", $worldName, SQLITE3_TEXT);

        $this->getWorld->reset();
        $result = $this->getWorld->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = WorldSettings::fromArray($var);
            $this->cacheWorld($worldName, $settings);
            return $settings;
        }
        return null;
    }

    /**
     * @param string        $worldName
     * @param WorldSettings $settings
     * @return bool
     */
    public function addWorld(string $worldName, WorldSettings $settings) : bool {
        $this->setWorld->bindValue(":worldName", $worldName, SQLITE3_TEXT);

        $this->setWorld->bindValue(":schematicRoad", $settings->getSchematicRoad(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":schematicMergeRoad", $settings->getSchematicMergeRoad(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":schematicPlot", $settings->getSchematicPlot(), SQLITE3_TEXT);

        $this->setWorld->bindValue(":sizeRoad", $settings->getSizeRoad(), SQLITE3_INTEGER);
        $this->setWorld->bindValue(":sizePlot", $settings->getSizePlot(), SQLITE3_INTEGER);
        $this->setWorld->bindValue(":sizeGround", $settings->getSizeGround(), SQLITE3_INTEGER);

        $this->setWorld->bindValue(":blockRoad", $settings->getBlockRoadString(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":blockBorder", $settings->getBlockBorderString(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":blockBorderOnClaim", $settings->getBlockBorderOnClaimString(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":blockPlotFloor", $settings->getBlockPlotFloorString(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":blockPlotFill", $settings->getBlockPlotFillString(), SQLITE3_TEXT);
        $this->setWorld->bindValue(":blockPlotBottom", $settings->getBlockPlotBottomString(), SQLITE3_TEXT);

        $this->setWorld->reset();
        $result = $this->setWorld->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cacheWorld($worldName, $settings);
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
        $result = $this->getPlot->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $worldName, $x, $z,
                $var["biomeID"] ?? BiomeIds::PLAINS, $var["ownerUUID"] ?? null, $var["claimTime"] ?? null, $var["alias"] ?? null
            );
            $this->cachePlot($plot);
            return $plot;
        }
        return new Plot($worldName, $x, $z);
    }

    /**
     * @param string $ownerUUID
     * @return Plot[] | null
     */
    public function getPlotsByOwnerUUID(string $ownerUUID) : ?array {
        $this->getPlotsByOwnerUUID->bindValue(":ownerUUID", $ownerUUID, SQLITE3_TEXT);

        $this->getPlotsByOwnerUUID->reset();
        $result = $this->getPlotsByOwnerUUID->execute();
        if (!$result instanceof SQLite3Result) return null;

        $plots = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plots[] = new Plot(
                $var["worldName"], $var["x"], $var["z"],
                $var["biomeID"] ?? BiomeIds::PLAINS, $ownerUUID, $var["claimTime"] ?? null, $var["alias"] ?? null
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
        $result = $this->getPlotByAlias->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $plot = new Plot(
                $var["worldName"], $var["x"], $var["z"],
                $var["biomeID"] ?? BiomeIds::PLAINS, $var["ownerUUID"] ?? null, $var["claimTime"] ?? null, $alias
            );
            $this->cachePlot($plot);
            return $plot;
        }
        return null;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public function savePlot(Plot $plot) : bool {
        $this->setPlot->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlot->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlot->bindValue(":biomeID", $plot->getBiomeID(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":ownerUUID", $plot->getOwnerUUID(), SQLITE3_TEXT);
        $this->setPlot->bindValue(":claimTIme", $plot->getClaimTime(), SQLITE3_INTEGER);
        $this->setPlot->bindValue(":alias", $plot->getAlias(), SQLITE3_TEXT);

        $this->setPlotPlayer->reset();
        $result = $this->setPlotPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     * @return bool
     */
    public function deletePlot(string $worldName, int $x, int $z) : bool {
        $this->deletePlot->bindValue(":worldName", $worldName, SQLITE3_TEXT);
        $this->deletePlot->bindValue(":x", $x, SQLITE3_INTEGER);
        $this->deletePlot->bindValue(":z", $z, SQLITE3_INTEGER);

        $this->deletePlot->reset();
        $result = $this->deletePlot->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->removePlotFromCache($worldName, $x, $z);
        return true;
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
        $result = $this->getMergedPlots->execute();
        if (!$result instanceof SQLite3Result) return null;

        $mergedPlots = [];
        while ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $mergedPlot = new MergedPlot($plot->getWorldName(), $var["mergedX"], $var["mergedZ"], $plot->getX(), $plot->getZ());
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
        $result = $this->getOriginPlot->execute();
        if (!$result instanceof SQLite3Result) return null;

        if ($var = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->cachePlot(MergedPlot::fromBasePlot($plot, $var["originX"], $var["originZ"]));
            return $this->getPlot($plot->getWorldName(), $var["originX"], $var["originZ"]);
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
            $this->removePlotFromCache($mergedPlot->getWorldName(), $mergedPlot->getX(), $mergedPlot->getZ());
        }
        $this->removePlotFromCache($plot->getWorldName(), $plot->getX(), $plot->getZ());
        return true;
    }

    /**
     * @param Plot $plot
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

    /**
     * @param Plot          $plot
     * @param PlotPlayer    $plotPlayer
     * @return bool
     */
    public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : bool {
        if (!$plot->addPlotPlayer($plotPlayer)) return false;

        $this->setPlotPlayer->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlotPlayer->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlotPlayer->bindValue(":playerUUID", $plotPlayer->getPlayerUUID(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":state", $plotPlayer->getState(), SQLITE3_TEXT);
        $this->setPlotPlayer->bindValue(":addTIme", $plotPlayer->getAddTime(), SQLITE3_INTEGER);

        $this->setPlotPlayer->reset();
        $result = $this->setPlotPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param Plot      $plot
     * @param string    $playerUUID
     * @return bool
     */
    public function deletePlotPlayer(Plot $plot, string $playerUUID) : bool {
        if (!$plot->removePlotPlayer($playerUUID)) return false;

        $this->deletePlotPlayer->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlotPlayer->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlotPlayer->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlotPlayer->bindValue(":playerUUID", $playerUUID, SQLITE3_TEXT);

        $this->deletePlotPlayer->reset();
        $result = $this->deletePlotPlayer->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param Plot $plot
     * @return BaseFlag[] | null
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
            if ($flag === null) continue;
            $flag->unserializeValue($var["value"]);
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

        $this->setPlotFlag->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->setPlotFlag->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->setPlotFlag->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->setPlotFlag->bindValue(":ID", $flag->getID(), SQLITE3_TEXT);
        $this->setPlotFlag->bindValue(":value", $flag->serializeValue(), SQLITE3_TEXT);

        $this->setPlotFlag->reset();
        $result = $this->setPlotFlag->execute();
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

        $this->deletePlotFlag->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlotFlag->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlotFlag->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlotFlag->bindValue(":ID", $flagID, SQLITE3_TEXT);

        $this->deletePlotFlag->reset();
        $result = $this->deletePlotFlag->execute();
        if (!$result instanceof SQLite3Result) return false;

        $this->cachePlot($plot);
        return true;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public function deletePlotFlags(Plot $plot) : bool {
        $this->deletePlotFlags->bindValue(":worldName", $plot->getWorldName(), SQLITE3_TEXT);
        $this->deletePlotFlags->bindValue(":x", $plot->getX(), SQLITE3_INTEGER);
        $this->deletePlotFlags->bindValue(":z", $plot->getZ(), SQLITE3_INTEGER);

        $this->deletePlotFlags->reset();
        $result = $this->deletePlotFlags->execute();
        if (!$result instanceof SQLite3Result) return false;

        $plot->setFlags(null);
        $this->cachePlot($plot);
        return true;
    }


    /**
     * @param Plot $plot
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

    /**
     * @param Plot      $plot
     * @param PlotRate  $plotRate
     * @return bool
     */
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