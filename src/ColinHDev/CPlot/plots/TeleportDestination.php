<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

interface TeleportDestination {

    /**
     * Teleports the player to the plot's spawn or if not set, to the edge of the plot.
     */
    public const PLOT_SPAWN_OR_EDGE = 0;
    /**
     * Teleports the player to the edge of the plot, while ignoring the plot's spawn.
     */
    public const PLOT_EDGE = 1;
    /**
     * Teleports the player to the plot's spawn or if not set, to the center of the plot.
     */
    public const PLOT_SPAWN_OR_CENTER = 2;
    /**
     * Teleports the player to the center of the plot, while ignoring the plot's spawn.
     */
    public const PLOT_CENTER = 3;
    /**
     * Teleports the player to nearly the same {@see Location} as {@see TeleportDestination::PLOT_EDGE}. But while it
     * would teleport the player to a location on the plot, this will teleport the player a few blocks behind on the
     * road.
     */
    public const ROAD_EDGE = 4;
}