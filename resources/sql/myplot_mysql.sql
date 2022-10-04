-- # !mysql

-- #{ myplot

-- #  { get
-- #    { Plots
SELECT level as worldName, X as x, Z as z, owner as playerName, helpers, denied, pvp FROM plotsV2;
-- #    }
-- #    { Merges
SELECT level as worldName, originX, originZ, mergedX, mergedZ FROM mergedPlotsV2;
-- #    }
-- #  }
-- #}