<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

interface FlagIDs {

    public const FLAG_TITLE = "title";
    public const FLAG_PLOT_ENTER = "plot_enter";
    public const FLAG_PLOT_LEAVE = "plot_leave";
    public const FLAG_MESSAGE = "message";

    public const FLAG_SPAWN = "spawn";

    public const FLAG_ITEM_DROP = "item_drop";
    public const FLAG_ITEM_PICKUP = "item_pickup";

    public const FLAG_PVP = "pvp";
    public const FLAG_PVE = "pve";
    public const FLAG_EXPLOSION = "explosion";
    public const FLAG_BURNING = "burning";
    public const FLAG_FLOWING = "flowing";
    public const FLAG_GROWING = "growing";
    public const FLAG_PLAYER_INTERACT = "player_interact";
    public const FLAG_SERVER_PLOT = "server_plot";
    public const FLAG_CHECK_INACTIVE = "check_inactive";

    public const FLAG_PLACE = "place";
    public const FLAG_BREAK = "break";
    public const FLAG_USE = "use";
}