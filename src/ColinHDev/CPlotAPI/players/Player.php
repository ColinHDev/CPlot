<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlotAPI\players\settings\BaseSetting;
use ColinHDev\CPlotAPI\players\settings\SettingManager;

class Player implements Cacheable {

    private string $playerUUID;
    private string $playerName;
    private int $lastPlayed;

    /** @var null | BaseSetting[] */
    private ?array $settings = null;

    public function __construct(string $playerUUID, string $playerName, int $lastPlayed) {
        $this->playerUUID = $playerUUID;
        $this->playerName = $playerName;
        $this->lastPlayed = $lastPlayed;
    }

    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    public function getPlayerName() : string {
        return $this->playerName;
    }

    public function getLastPlayed() : int {
        return $this->lastPlayed;
    }


    public function loadSettings() : bool {
        if ($this->settings !== null) return true;
        $this->settings = CPlot::getInstance()->getProvider()->getPlayerSettings($this);
        if ($this->settings === null) return false;
        CPlot::getInstance()->getProvider()->getPlayerCache()->cacheObject($this->playerUUID, $this);
        return true;
    }

    /**
     * @return BaseSetting[] | null
     */
    public function getSettings() : ?array {
        return $this->settings;
    }

    public function getSettingByID(string $settingID) : ?BaseSetting {
        if ($this->settings !== null) {
            if (isset($this->settings[$settingID])) return $this->settings[$settingID];
        }
        return null;
    }

    public function getSettingNonNullByID(string $settingID) : ?BaseSetting {
        if ($this->settings !== null) {
            if (isset($this->settings[$settingID])) return $this->settings[$settingID];
        }
        return SettingManager::getInstance()->getSettingByID($settingID);
    }

    /**
     * @param BaseSetting[] | null $settings
     */
    public function setSettings(?array $settings) : void {
        $this->settings = $settings;
    }

    public function addSetting(BaseSetting $setting) : bool {
        if ($this->settings === null) return false;
        $this->settings[$setting->getID()] = $setting;
        return true;
    }

    public function removeSetting(string $settingID) : bool {
        if ($this->settings === null) return false;
        unset($this->settings[$settingID]);
        return true;
    }
}