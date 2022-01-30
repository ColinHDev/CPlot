<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

interface SettingIDs {

    public const SETTING_INFORM_TRUSTED_ADD = "inform_trusted_add";
    public const SETTING_INFORM_TRUSTED_REMOVE = "inform_trusted_remove";

    public const SETTING_INFORM_HELPER_ADD = "inform_helper_add";
    public const SETTING_INFORM_HELPER_REMOVE = "inform_helper_remove";

    public const SETTING_INFORM_DENIED_ADD = "inform_denied_add";
    public const SETTING_INFORM_DENIED_REMOVE = "inform_denied_remove";

    public const SETTING_INFORM_PLOT_INACTIVE = "inform_plot_inactive";

    public const SETTING_INFORM_PLOT_RATE_ADD = "inform_plot_rate_add";

    public const BASE_SETTING_WARN_FLAG = "warn_flag_";
    public const BASE_SETTING_WARN_CHANGE_FLAG = "warn_change_flag_";
    public const BASE_SETTING_TELEPORT_CHANGE_FLAG = "teleport_change_flag_";

    public const SETTING_WARN_FLAG_ITEM_DROP = "warn_flag_item_drop";
    public const SETTING_WARN_CHANGE_FLAG_ITEM_DROP = "warn_change_flag_item_drop";
    public const SETTING_TELEPORT_CHANGE_FLAG_ITEM_DROP = "teleport_change_flag_item_drop";

    public const SETTING_WARN_FLAG_ITEM_PICKUP = "warn_flag_item_pickup";
    public const SETTING_WARN_CHANGE_FLAG_ITEM_PICKUP = "warn_change_flag_item_pickup";
    public const SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP = "teleport_change_flag_item_pickup";

    public const SETTING_WARN_FLAG_PVP = "warn_flag_pvp";
    public const SETTING_WARN_CHANGE_FLAG_PVP = "warn_change_flag_pvp";
    public const SETTING_TELEPORT_CHANGE_FLAG_PVP = "teleport_change_flag_pvp";

    public const SETTING_WARN_FLAG_PVE = "warn_flag_pve";
    public const SETTING_WARN_CHANGE_FLAG_PVE = "warn_change_flag_pve";
    public const SETTING_TELEPORT_CHANGE_FLAG_PVE = "teleport_change_flag_pve";

    public const SETTING_WARN_FLAG_EXPLOSION = "warn_flag_explosion";
    public const SETTING_WARN_CHANGE_FLAG_EXPLOSION = "warn_change_flag_explosion";
    public const SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION = "teleport_change_flag_explosion";
}