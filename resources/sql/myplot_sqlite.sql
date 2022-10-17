-- # !sqlite

-- #{ myplot

-- #  { get
-- #    { Plots
SELECT level, X as x, Z as z, owner, helpers, denied, pvp FROM plotsV2;
-- #    }
-- #    { Merges
SELECT level, originX, originZ, mergedX, mergedZ FROM mergedPlotsV2;
-- #    }
-- #  }
-- #}