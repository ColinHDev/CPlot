-- # !mysql

-- #{ cplot

-- #  { init
-- #    { foreignKeys
SET FOREIGN_KEY_CHECKS = 1;
-- #    }
-- #    { playerDataTable
CREATE TABLE IF NOT EXISTS playerData (
    playerID    BIGINT          NOT NULL    AUTO_INCREMENT,
    playerUUID  VARCHAR(256),
    playerXUID  VARCHAR(256),
    playerName  VARCHAR(256),
    lastJoin    TEXT            NOT NULL,
    PRIMARY KEY (playerID)
);
-- #    }
-- #    { asteriskPlayer
-- #      :lastJoin string
INSERT IGNORE INTO playerData (playerID, playerUUID, playerXUID, playerName, lastJoin)
VALUES (1, "*", "*", "*", :lastJoin);
-- #    }
-- #    { playerSettingsTable
CREATE TABLE IF NOT EXISTS playerSettings (
    playerID    BIGINT  NOT NULL,
    ID          TEXT    NOT NULL,
    value       TEXT    NOT NULL,
    PRIMARY KEY (playerID, ID),
    FOREIGN KEY (playerID) REFERENCES playerData (playerID) ON DELETE CASCADE
);
-- #    }
-- #    { worldsTable
CREATE TABLE IF NOT EXISTS worlds(
    worldName           VARCHAR(256)    NOT NULL,
    worldType           TEXT            NOT NULL,
    roadSchematic       TEXT            NOT NULL,
    mergeRoadSchematic  TEXT            NOT NULL,
    plotSchematic       TEXT            NOT NULL,
    roadSize            BIGINT          NOT NULL,
    plotSize            BIGINT          NOT NULL,
    groundSize          BIGINT          NOT NULL,
    roadBlock           TEXT            NOT NULL,
    borderBlock         TEXT            NOT NULL,
    plotFloorBlock      TEXT            NOT NULL,
    plotFillBlock       TEXT            NOT NULL,
    plotBottomBlock     TEXT            NOT NULL,
    PRIMARY KEY (worldName)
);
-- #    }
-- #    { plotAliasesTable
CREATE TABLE IF NOT EXISTS plotAliases (
    worldName       VARCHAR(256)    NOT NULL,
    x               BIGINT          NOT NULL,
    z               BIGINT          NOT NULL,
    alias           TEXT            NOT NULL,
    PRIMARY KEY (worldName, x, z, alias),
    FOREIGN KEY (worldName) REFERENCES worlds (worldName) ON DELETE CASCADE
);
-- #    }
-- #    { mergePlotsTable
CREATE TABLE IF NOT EXISTS mergePlots(
    worldName   VARCHAR(256)    NOT NULL,
    originX     BIGINT          NOT NULL,
    originZ     BIGINT          NOT NULL,
    mergeX      BIGINT          NOT NULL,
    mergeZ      BIGINT          NOT NULL,
    PRIMARY KEY (worldName, originX, originZ, mergeX, mergeZ)
);
-- #    }
-- #    { plotPlayersTable
CREATE TABLE IF NOT EXISTS plotPlayers (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    playerID    BIGINT          NOT NULL,
    state       TEXT            NOT NULL,
    addTime     TEXT            NOT NULL,
    PRIMARY KEY (worldName, x, z, playerID)
    FOREIGN KEY (playerID) REFERENCES playerData (playerID) ON DELETE CASCADE
);
-- #    }
-- #    { plotFlagsTable
CREATE TABLE IF NOT EXISTS plotFlags (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    ID          TEXT            NOT NULL,
    value       TEXT            NOT NULL,
    PRIMARY KEY (worldName, x, z, ID)
);
-- #    }
-- #    { plotRatesTable
CREATE TABLE IF NOT EXISTS plotRates (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    rate        TEXT            NOT NULL,
    playerID    BIGINT          NOT NULL,
    rateTime    TEXT            NOT NULL,
    comment     TEXT,
    PRIMARY KEY (worldName, x, z, playerID, rateTime),
    FOREIGN KEY (playerID) REFERENCES playerData (playerID) ON DELETE CASCADE
);
-- #    }
-- #  }

-- #  { get
-- #    { playerDataByIdentifier
-- #      :playerID int
SELECT playerUUID, playerXUID, playerName, lastJoin
FROM playerData
WHERE playerID = :playerID;
-- #    }
-- #    { playerDataByUUID
-- #      :playerUUID string
SELECT playerID, playerXUID, playerName, lastJoin
FROM playerData
WHERE playerUUID = :playerUUID;
-- #    }
-- #    { playerDataByXUID
-- #      :playerXUID string
SELECT playerID, playerUUID, playerName, lastJoin
FROM playerData
WHERE playerXUID = :playerXUID;
-- #    }
-- #    { playerDataByName
-- #      :playerName string
SELECT playerID, playerUUID, playerXUID, lastJoin
FROM playerData
WHERE playerName = :playerName;
-- #    }
-- #    { playerSettings
-- #      :playerID int
SELECT ID, value
FROM playerSettings
WHERE playerID = :playerID;
-- #    }
-- #    { world
-- #      :worldName string
SELECT *
FROM worlds
WHERE worldName = :worldName;
-- #    }
-- #    { plotAliases
-- #      :worldName string
-- #      :x int
-- #      :z int
SELECT alias
FROM plotAliases
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotByAlias
-- #      :alias string
SELECT worldName, x, z
FROM plotAliases
WHERE alias = :alias;
-- #    }
-- #    { originPlot
-- #      :worldName string
-- #      :mergeX int
-- #      :mergeZ int
SELECT originX, originZ
FROM mergePlots
WHERE worldName = :worldName AND mergeX = :mergeX AND mergeZ = :mergeZ;
-- #    }
-- #    { mergePlots
-- #      :worldName string
-- #      :originX int
-- #      :originZ int
SELECT mergeX, mergeZ
FROM mergePlots
WHERE worldName = :worldName AND originX = :originX AND originZ = :originZ;
-- #    }
-- #    { existingPlotXZ
-- #      :worldName string
-- #      :number int
SELECT x, z
FROM plots
WHERE (
    worldName = :worldName AND (
        (abs(x) = :number AND abs(z) <= :number) OR
        (abs(z) = :number AND abs(x) <= :number)
    )
)
UNION
SELECT mergeX, mergeZ
FROM mergePlots
WHERE (
    worldName = :worldName AND (
        (abs(mergeX) = :number AND abs(mergeZ) <= :number) OR
        (abs(mergeZ) = :number AND abs(mergeX) <= :number)
    )
);
-- #    }
-- #    { plotPlayers
-- #      :worldName string
-- #      :x int
-- #      :z int
SELECT playerID, state, addTime
FROM plotPlayers
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotsByPlotPlayer
-- #      :playerID int
-- #      :state string
SELECT worldName, x, z
FROM plotPlayers
WHERE playerID = :playerID AND state = :state;
-- #    }
-- #    { plotFlags
-- #      :worldName string
-- #      :x int
-- #      :z int
SELECT ID, value
FROM plotFlags
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotRates
-- #      :worldName string
-- #      :x int
-- #      :z int
SELECT rate, playerID, rateTime, comment
FROM plotRates
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #  }

-- #  { set
-- #    { newPlayerData
-- #      :playerUUID string
-- #      :playerXUID string
-- #      :playerName string
-- #      :lastJoin string
INSERT INTO playerData (playerUUID, playerXUID, playerName, lastJoin)
VALUES (:playerUUID, :playerXUID, :playerName, :lastJoin);
-- #    }
-- #    { playerData
-- #      :playerID int
-- #      :playerUUID string
-- #      :playerXUID string
-- #      :playerName string
-- #      :lastJoin string
UPDATE playerData
SET playerUUID = :playerUUID, playerXUID = :playerXUID, playerName = :playerName, lastJoin = :lastJoin
WHERE playerID = :playerID;
-- #    }
-- #    { playerSetting
-- #      :playerID int
-- #      :ID string
-- #      :value string
REPLACE INTO playerSettings (playerID, ID, value)
VALUES (:playerID, :ID, :value);
-- #    }
-- #    { world
-- #      :worldName string
-- #      :worldType string
-- #      :roadSchematic string
-- #      :mergeRoadSchematic string
-- #      :plotSchematic string
-- #      :roadSize int
-- #      :plotSize int
-- #      :groundSize int
-- #      :roadBlock string
-- #      :borderBlock string
-- #      :plotFloorBlock string
-- #      :plotFillBlock string
-- #      :plotBottomBlock string
INSERT OR REPLACE INTO worlds (
    worldName, worldType,
    roadSchematic, mergeRoadSchematic, plotSchematic,
    roadSize, plotSize, groundSize,
    roadBlock, borderBlock, plotFloorBlock, plotFillBlock, plotBottomBlock
) VALUES (
    :worldName, :worldType,
    :roadSchematic, :mergeRoadSchematic, :plotSchematic,
    :roadSize, :plotSize, :groundSize,
    :roadBlock, :borderBlock, :plotFloorBlock, :plotFillBlock, :plotBottomBlock
);
-- #    }
-- #    { plotAlias
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :alias string
REPLACE INTO plotAliases (worldName, x, z, alias)
VALUES (:worldName, :x, :z, :alias);
-- #    }
-- #    { mergePlot
-- #      :worldName string
-- #      :originX int
-- #      :originZ int
-- #      :mergeX int
-- #      :mergeZ int
REPLACE INTO mergePlots (worldName, originX, originZ, mergeX, mergeZ)
VALUES (:worldName, :originX, :originZ, :mergeX, :mergeZ);
-- #    }
-- #    { plotPlayer
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :playerID int
-- #      :state string
-- #      :addTime string
INSERT INTO plotPlayers (worldName, x, z, playerID, state, addTime)
VALUES (:worldName, :x, :z, :playerID, :state, :addTime) AS new
ON DUPLICATE KEY UPDATE state = new.state, addTime = new.addTime;
-- #    }
-- #    { plotFlag
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :ID string
-- #      :value string
REPLACE INTO plotFlags (worldName, x, z, ID, value)
VALUES (:worldName, :x, :z, :ID, :value);
-- #    }
-- #    { plotRate
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :rate string
-- #      :playerID int
-- #      :rateTime string
-- #      :comment ?string
INSERT INTO plotRates (worldName, x, z, rate, playerID, rateTime, comment)
VALUES (:worldName, :x, :z, :rate, :playerID, :rateTime, :comment) AS new
ON DUPLICATE KEY UPDATE rate = new.rate, comment = new.comment;
-- #    }
-- #  }

-- #  { delete
-- #    { playerSetting
-- #      :playerID int
-- #      :ID string
DELETE FROM playerSettings
WHERE playerID = :playerID AND ID = :ID;
-- #    }
-- #    { plotAliases
-- #      :worldName string
-- #      :x int
-- #      :z int
DELETE FROM plotAliases
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { mergePlots
-- #      :worldName string
-- #      :originX int
-- #      :originZ int
DELETE FROM mergePlots
WHERE worldName = :worldName AND originX = :originX AND originZ = :originZ;
-- #    }
-- #    { plotPlayers
-- #      :worldName string
-- #      :x int
-- #      :z int
DELETE FROM plotPlayers
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotPlayer
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :playerID int
DELETE FROM plotPlayers
WHERE worldName = :worldName AND x = :x AND z = :z AND playerID = :playerID;
-- #    }
-- #    { plotFlags
-- #      :worldName string
-- #      :x int
-- #      :z int
DELETE FROM plotFlags
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotFlag
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :ID string
DELETE FROM plotFlags
WHERE worldName = :worldName AND x = :x AND z = :z AND ID = :ID;
-- #    }
-- #    { plotRates
-- #      :worldName string
-- #      :x int
-- #      :z int
DELETE FROM plotRates
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #  }
-- #}