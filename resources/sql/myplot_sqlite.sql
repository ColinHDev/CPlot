-- # !sqlite

-- #{ myplot

-- #  { get
-- #    { Plots
-- #      :worldName string
SELECT level, X, Z, owner, helpers, denied, pvp FROM plotsV2 WHERE level = :worldName;
-- #    }
-- #    { Merges
-- #      :worldName string
SELECT level, originX, originZ, mergedX, mergedZ FROM mergedPlotsV2 WHERE level = :worldName;
-- #    }
-- #  }
-- #}