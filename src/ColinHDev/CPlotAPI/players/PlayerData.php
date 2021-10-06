<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\players\settings\BaseSetting;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use pocketmine\player\OfflinePlayer;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;

class PlayerData implements Cacheable {

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

    /**
     * @return int | null
     * returns int as the last played time in seconds
     * returns null if the result couldn't be found
     */
    public function getLastPlayed() : ?int {
        // player is online and therefore not inactive
        $player = Server::getInstance()->getPlayerByRawUUID(Uuid::fromString($this->playerUUID));
        if ($player !== null) {
            return (int) (microtime(true) * 1000);
        }

        // check if the last time the player played should be fetched from the offline data file or the database
        switch (ResourceManager::getInstance()->getConfig()->get("lastPlayed.origin", "database")) {
            case "offline_data":
                // if the player isn't an instance of OfflinePlayer it is one of Player and therefore online on the server
                $offlinePlayer = Server::getInstance()->getOfflinePlayer($this->playerName);
                if (!$offlinePlayer instanceof OfflinePlayer) {
                    return (int) (microtime(true) * 1000);
                }

                // check if the player's offline player data even exists
                // if not we try to fetch the last time the player played from the database
                // this could be null if the server admin deleted the player's offline data file
                $lastPlayed = $offlinePlayer->getLastPlayed();
                if ($lastPlayed !== null) {
                    break;
                }

            default:
            case "database":
                $lastPlayed = $this->lastPlayed;
                break;
        }

        // the last played time is saved in milliseconds therefore we devide by 1000 and cast it to an integer
        // the float gets rounded up in favor of the player
        return (int) ceil($lastPlayed / 1000);
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