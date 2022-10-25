<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

interface SettingIDs {

    public const SETTING_INFORM_ADDED = "inform_added";
    public const SETTING_INFORM_DENIED = "inform_denied";
    public const SETTING_INFORM_RATE_ADD = "inform_rate_add";
    public const SETTING_INFORM_REMOVED = "inform_removed";
    public const SETTING_INFORM_TRUSTED = "inform_trusted";
    public const SETTING_INFORM_UNDENIED = "inform_undenied";
    public const SETTING_INFORM_UNTRUSTED = "inform_untrusted";
    public const SETTING_TELEPORT_FLAG_CHANGE = "teleport_flag_change";
    public const SETTING_WARN_FLAG_CHANGE = "warn_flag_change";
    public const SETTING_WARN_FLAG = "warn_flag";
}