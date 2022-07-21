<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

interface SettingIDs {

    public const SETTING_INFORM_TRUSTED = "inform_trusted";
    public const SETTING_INFORM_UNTRUSTED = "inform_untrusted";

    public const SETTING_INFORM_ADDED = "inform_added";
    public const SETTING_INFORM_REMOVED = "inform_removed";

    public const SETTING_INFORM_DENIED = "inform_denied";
    public const SETTING_INFORM_UNDENIED = "inform_undenied";

    public const SETTING_INFORM_RATE_ADD = "inform_rate_add";

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