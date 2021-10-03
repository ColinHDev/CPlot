<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SettingSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }

        switch ($args[0]) {
            case "list":
                $sender->sendMessage($this->getPrefix() . $this->translateString("setting.list.success"));
                $settingsByCategory = [];
                foreach (SettingManager::getInstance()->getSettings() as $setting) {
                    if (!isset($settingsByCategory[$setting->getCategory()])) {
                        $settingsByCategory[$setting->getCategory()] = $setting->getID();
                    } else {
                        $settingsByCategory[$setting->getCategory()] .= $this->translateString("setting.list.success.separator") . $setting->getID();
                    }
                }
                foreach ($settingsByCategory as $category => $settings) {
                    $sender->sendMessage($this->translateString("setting.list.success.format", [$category, $settings]));
                }
                break;

            case "info":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.info.usage"));
                    break;
                }
                $flag = SettingManager::getInstance()->getSettingByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.info.noFlag", [$args[1]]));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("setting.info.setting", [$flag->getID()]));
                $sender->sendMessage($this->translateString("setting.info.ID", [$flag->getID()]));
                $sender->sendMessage($this->translateString("setting.info.category", [$flag->getCategory()]));
                $sender->sendMessage($this->translateString("setting.info.description", [$flag->getDescription()]));
                $sender->sendMessage($this->translateString("setting.info.valueType", [$flag->getValueType()]));
                $sender->sendMessage($this->translateString("setting.info.default", [$flag->serializeValueType($flag->getDefault())]));
                break;

            case "set":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.senderNotOnline"));
                    break;
                }
                $player = $this->getPlugin()->getProvider()->getPlayerByUUID($sender->getUniqueId()->toString());
                if ($player === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.loadPlayerDataError"));
                    break;
                }
                if (!$player->loadSettings()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.loadPlayerSettingsError"));
                    break;
                }

                $setting = $player->getSettingNonNullByID($args[1]);
                if ($setting === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.noSetting", [$args[1]]));
                    break;
                }

                array_splice($args, 0, 2);
                if (!$setting->set($sender, $player, $args)) return;
                if (!$this->getPlugin()->getProvider()->savePlayerSetting($player, $setting)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.saveError"));
                    break;
                }
                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.senderNotOnline"));
                    break;
                }
                $player = $this->getPlugin()->getProvider()->getPlayerByUUID($sender->getUniqueId()->toString());
                if ($player === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.loadPlayerDataError"));
                    break;
                }
                if (!$player->loadSettings()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.loadPlayerSettingsError"));
                    break;
                }

                $setting = $player->getSettingNonNullByID($args[1]);
                if ($setting === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("setting.set.noSetting", [$args[1]]));
                    break;
                }

                array_splice($args, 0, 2);
                if (!$setting->remove($sender, $player, $args)) return;
                if ($setting->getValue() === null) {
                    if ($this->getPlugin()->getProvider()->deletePlayerSetting($player, $setting->getID())) break;
                } else {
                    if ($this->getPlugin()->getProvider()->savePlayerSetting($player, $setting)) break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("setting.remove.saveError"));
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                break;
        }
    }
}