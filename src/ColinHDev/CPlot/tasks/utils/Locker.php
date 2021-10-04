<?php

namespace ColinHDev\CPlot\tasks\utils;

interface Locker {

    public const LOCK_CAUSE_ENTITY_EXPLOSION = "entity_explosion";
    public const LOCK_CAUSE_PLOT_BORDER_CHANGE = "plot_border_change";
    public const LOCK_CAUSE_PLOT_CLEAR = "plot_clear";
    public const LOCK_CAUSE_PLOT_MERGE = "plot_merge";
    public const LOCK_CAUSE_PLOT_RESET = "plot_reset";
    public const LOCK_CAUSE_PLOT_WALL_CHANGE = "plot_wall_change";

    public function isEntryLocked(int | string $identifier) : ?string;
    public function addEntry(int | string $identifier, string $cause) : void;
    public function removeEntry(int | string $identifier) : void;
}