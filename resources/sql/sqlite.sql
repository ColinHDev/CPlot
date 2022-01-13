- # !sqlite

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
-- #}