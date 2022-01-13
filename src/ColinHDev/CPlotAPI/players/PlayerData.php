<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use ColinHDev\CPlotAPI\players\utils\PlayerDataException;
use pocketmine\player\OfflinePlayer;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;

class PlayerData implements Cacheable {

    private string $playerUUID;
    private string $playerName;
    private int $lastPlayed;

    /** @var null | array<string, BaseAttribute> */
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
     * returns int as the last played time in seconds
     * returns null if the result couldn't be found
     */
    public function getLastPlayed() : int {
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

    /**
     * @return array<string, BaseAttribute>
     * @throws PlayerDataException
     */
    public function getSettings() : array {
        $this->loadSettings();
        return $this->settings;
    }

    /**
     * @throws PlayerDataException
     */
    public function getSettingByID(string $settingID) : ?BaseAttribute {
        $this->loadSettings();
        if (!isset($this->settings[$settingID])) {
            return null;
        }
        return $this->settings[$settingID];
    }

    /**
     * @throws PlayerDataException
     */
    public function getSettingNonNullByID(string $settingID) : ?BaseAttribute {
        $setting = $this->getSettingByID($settingID);
        if ($setting === null) {
            $setting = SettingManager::getInstance()->getSettingByID($settingID);
        }
        return $setting;
    }

    /**
     * @throws PlayerDataException
     */
    public function addSetting(BaseAttribute $setting) : void {
        $this->loadSettings();
        $this->settings[$setting->getID()] = $setting;
    }

    /**
     * @throws PlayerDataException
     */
    public function removeSetting(string $settingID) : void {
        $this->loadSettings();
        unset($this->settings[$settingID]);
    }

    /**
     * @throws PlayerDataException
     */
    public function loadSettings() : void {
        if ($this->settings !== null) {
            return;
        }
        $this->settings = CPlot::getInstance()->getProvider()->getPlayerSettings($this);
        if ($this->settings === null) {
            throw new PlayerDataException($this,"Couldn't load player settings of player with UUID " . $this->playerUUID . " from provider.");
        }
        CPlot::getInstance()->getProvider()->getPlayerCache()->cacheObject($this->playerUUID, $this);
    }
}