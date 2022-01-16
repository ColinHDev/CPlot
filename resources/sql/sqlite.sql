-- # !sqlite

-- #{ cplot

-- #  { init
-- #    { playerDataTable
CREATE TABLE IF NOT EXISTS playerData (
    playerUUID      VARCHAR(256)    NOT NULL,
    playerName      VARCHAR(256)    NOT NULL,
    lastJoin        TEXT            NOT NULL,
    PRIMARY KEY (playerUUID)
);
-- #    }
-- #    { playerSettingsTable
CREATE TABLE IF NOT EXISTS playerSettings (
    playerUUID      VARCHAR(256)    NOT NULL,
    ID              TEXT            NOT NULL,
    value           TEXT            NOT NULL,
    PRIMARY KEY (playerUUID, ID),
    FOREIGN KEY (playerUUID) REFERENCES playerData (playerUUID) ON DELETE CASCADE
);
-- #    }
-- #    { worldsTable
CREATE TABLE IF NOT EXISTS worlds (
    worldName           VARCHAR(256)    NOT NULL,
    roadSchematic       TEXT            NOT NULL,
    mergeRoadSchematic  TEXT            NOT NULL,
    plotSchematic       TEXT            NOT NULL,
    roadSize            BIGINT          NOT NULL,
    plotSize            BIGINT          NOT NULL,
    groundSize          BIGINT          NOT NULL,
    roadBlock           TEXT            NOT NULL,
    borderBlock         TEXT            NOT NULL,
    borderBlockOnClaim  TEXT            NOT NULL,
    plotFloorBlock      TEXT            NOT NULL,
    plotFillBlock       TEXT            NOT NULL,
    plotBottomBlock     TEXT            NOT NULL,
    PRIMARY KEY (worldName)
);
-- #    }
-- #    { plotsTable
CREATE TABLE IF NOT EXISTS plots (
    worldName       VARCHAR(256)    NOT NULL,
    x               BIGINT          NOT NULL,
    z               BIGINT          NOT NULL,
    biomeID         BIGINT          NOT NULL,
    alias           TEXT,
    PRIMARY KEY (worldName, x, z),
    FOREIGN KEY (worldName) REFERENCES worlds (worldName) ON DELETE CASCADE
);
-- #    }
-- #    { mergePlotsTable
CREATE TABLE IF NOT EXISTS mergePlots (
    worldName   VARCHAR(256)    NOT NULL,
    originX     BIGINT          NOT NULL,
    originZ     BIGINT          NOT NULL,
    mergeX      BIGINT          NOT NULL,
    mergeZ      BIGINT          NOT NULL,
    PRIMARY KEY (worldName, originX, originZ, mergeX, mergeZ),
    FOREIGN KEY (worldName, originX, originZ) REFERENCES plots (worldName, x, z) ON DELETE CASCADE
);
-- #    }
-- #    { plotPlayersTable
CREATE TABLE IF NOT EXISTS plotPlayers (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    playerUUID  VARCHAR(256)    NOT NULL,
    state       TEXT            NOT NULL,
    addTime     TEXT            NOT NULL,
    PRIMARY KEY (worldName, x, z, playerUUID),
    FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE,
    FOREIGN KEY (playerUUID) REFERENCES playerData (playerUUID) ON DELETE CASCADE
);
-- #    }
-- #    { plotFlagsTable
CREATE TABLE IF NOT EXISTS plotFlags (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    ID          TEXT            NOT NULL,
    value       TEXT            NOT NULL,
    PRIMARY KEY (worldName, x, z, ID),
    FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE
);
-- #    }
-- #    { plotRatesTable
CREATE TABLE IF NOT EXISTS plotRates (
    worldName   VARCHAR(256)    NOT NULL,
    x           BIGINT          NOT NULL,
    z           BIGINT          NOT NULL,
    rate        TEXT            NOT NULL,
    playerUUID  VARCHAR(256)    NOT NULL,
    rateTime    TEXT            NOT NULL,
    comment     TEXT,
    PRIMARY KEY (worldName, x, z, playerUUID, rateTime),
    FOREIGN KEY (worldName, x, z) REFERENCES plots (worldName, x, z) ON DELETE CASCADE,
    FOREIGN KEY (playerUUID) REFERENCES playerData (playerUUID) ON DELETE CASCADE
);
-- #    }
-- #  }

-- #  { get
-- #    { playerDataByUUID
-- #      :playerUUID string
SELECT playerName, lastJoin
FROM playerData
WHERE playerUUID = :playerUUID;
-- #    }
-- #    { playerDataByName
-- #      :playerName string
SELECT playerUUID, lastJoin
FROM playerData
WHERE playerName = :playerName;
-- #    }
-- #    { playerSettings
-- #      :playerUUID string
SELECT ID, value
FROM playerSettings
WHERE playerUUID = :playerUUID;
-- #    }
-- #    { world
-- #      :worldName string
SELECT *
FROM worlds
WHERE worldName = :worldName;
-- #    }
-- #    { plot
-- #      :worldName string
-- #      :x int
-- #      :z int
SELECT biomeID, alias
FROM plots
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotByAlias
-- #      :alias string
SELECT worldName, x, z, biomeID
FROM plots
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
SELECT mergeX AS x, mergeZ AS z
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
SELECT playerUUID, state, addTime
FROM plotPlayers
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotsByPlotPlayer
-- #      :playerUUID string
-- #      :state string
SELECT worldName, x, z
FROM plotPlayers
WHERE playerUUID = :playerUUID AND state = :state;
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
SELECT rate, playerUUID, rateTime, comment
FROM plotRates
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #  }

-- #  { set
-- #    { playerData
-- #      :playerUUID string
-- #      :playerName string
-- #      :lastJoin string
INSERT INTO playerData (playerUUID, playerName, lastJoin)
VALUES (:playerUUID, :playerName, :lastJoin)
ON CONFLICT (playerUUID) DO UPDATE SET playerName = excluded.playerName, lastJoin = excluded.lastJoin;
-- #    }
-- #    { playerSetting
-- #      :playerUUID string
-- #      :ID string
-- #      :value string
INSERT OR REPLACE INTO playerSettings (playerUUID, ID, value)
VALUES (:playerUUID, :ID, :value);
-- #    }
-- #    { world
-- #      :worldName string
-- #      :roadSchematic string
-- #      :mergeRoadSchematic string
-- #      :plotSchematic string
-- #      :roadSize int
-- #      :plotSize int
-- #      :groundSize int
-- #      :roadBlock string
-- #      :borderBlock string
-- #      :borderBlockOnClaim string
-- #      :plotFloorBlock string
-- #      :plotFillBlock string
-- #      :plotBottomBlock string
INSERT OR REPLACE INTO worlds (
    worldName,
    roadSchematic, mergeRoadSchematic, plotSchematic,
    roadSize, plotSize, groundSize,
    roadBlock, borderBlock, borderBlockOnClaim, plotFloorBlock, plotFillBlock, plotBottomBlock
) VALUES (
    :worldName,
    :roadSchematic, :mergeRoadSchematic, :plotSchematic,
    :roadSize, :plotSize, :groundSize,
    :roadBlock, :borderBlock, :borderBlockOnClaim, :plotFloorBlock, :plotFillBlock, :plotBottomBlock
);
-- #    }
-- #    { plot
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :biomeID int
-- #      :alias string
INSERT INTO plots (worldName, x, z, biomeID, alias)
VALUES (:worldName, :x, :z, :biomeID, :alias)
ON CONFLICT DO UPDATE SET biomeID = excluded.biomeID, alias = excluded.alias;
-- #    }
-- #    { mergePlot
-- #      :worldName string
-- #      :originX int
-- #      :originZ int
-- #      :mergeX int
-- #      :mergeZ int
INSERT OR REPLACE INTO mergePlots (worldName, originX, originZ, mergeX, mergeZ)
VALUES (:worldName, :originX, :originZ, :mergeX, :mergeZ);
-- #    }
-- #    { plotPlayer
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :playerUUID string
-- #      :state string
-- #      :addTime string
INSERT INTO plotPlayers (worldName, x, z, playerUUID, state, addTime)
VALUES (:worldName, :x, :z, :playerUUID, :state, :addTime)
ON CONFLICT DO UPDATE SET state = excluded.state, addTime = excluded.addTime;
-- #    }
-- #    { plotFlag
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :ID string
-- #      :value string
INSERT OR REPLACE INTO plotFlags (worldName, x, z, ID, value)
VALUES (:worldName, :x, :z, :ID, :value);
-- #    }
-- #    { plotRate
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :rate string
-- #      :playerUUID string
-- #      :rateTime string
-- #      :comment string
INSERT INTO plotRates (worldName, x, z, rate, playerUUID, rateTime, comment)
VALUES (:worldName, :x, :z, :rate, :playerUUID, :rateTime, :comment)
ON CONFLICT DO UPDATE SET rate = excluded.rate, comment = excluded.comment;
-- #    }
-- #  }

-- #  { delete
-- #    { playerSetting
-- #      :playerUUID string
-- #      :ID string
DELETE FROM playerSettings
WHERE playerUUID = :playerUUID AND ID = :ID;
-- #    }
-- #    { plot
-- #      :worldName string
-- #      :x int
-- #      :z int
DELETE FROM plots
WHERE worldName = :worldName AND x = :x AND z = :z;
-- #    }
-- #    { plotPlayer
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :playerUUID string
DELETE FROM plotPlayers
WHERE worldName = :worldName AND x = :x AND z = :z AND playerUUID = :playerUUID;
-- #    }
-- #    { plotFlag
-- #      :worldName string
-- #      :x int
-- #      :z int
-- #      :ID string
DELETE FROM plotFlags
WHERE worldName = :worldName AND x = :x AND z = :z AND ID = :ID;
-- #    }
-- #  }
-- #}