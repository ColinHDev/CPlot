<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\players\settings\SettingManager;
use pocketmine\player\OfflinePlayer;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;

class PlayerData implements Cacheable {

    private string $playerUUID;
    private string $playerName;
    private int $lastPlayed;

    /** @var array<string, BaseAttribute> */
    private array $settings;

    /**
     * @param array<string, BaseAttribute> $settings
     */
    public function __construct(string $playerUUID, string $playerName, int $lastPlayed, array $settings) {
        $this->playerUUID = $playerUUID;
        $this->playerName = $playerName;
        $this->lastPlayed = $lastPlayed;
        $this->settings = $settings;
    }

    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    public function getPlayerName() : string {
        return $this->playerName;
    }

    /**
     * Returns the last time a player joined in seconds.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getLastPlayed() : int {
        // player is online and therefore not inactive
        $player = Server::getInstance()->getPlayerByUUID(Uuid::fromBytes($this->playerUUID));
        if ($player !== null) {
            return time();
        }

        // check if the last time the player played should be fetched from the offline data file or the database
        switch (ResourceManager::getInstance()->getConfig()->get("lastPlayed.origin", "database")) {
            case "offline_data":
                // if the player isn't an instance of OfflinePlayer it is one of Player and therefore online on the server
                $offlinePlayer = Server::getInstance()->getOfflinePlayer($this->playerName);
                if (!$offlinePlayer instanceof OfflinePlayer) {
                    return time();
                }

                // check if the player's offline player data even exists
                // if not we try to fetch the last time the player played from the database
                // this could be null if the server admin deleted the player's offline data file
                $lastPlayed = $offlinePlayer->getLastPlayed();
                if ($lastPlayed !== null) {
                    // The last time a player joined is saved in milliseconds by PocketMine-MP. Therefore we divide by
                    // 1000 and cast it to an integer. The float gets rounded up in favour of the player.
                    $lastPlayed = (int) ceil($lastPlayed / 1000);
                    break;
                }

            default:
            case "database":
                // Since CPlot stores the last time a player joined in seconds, we do not need to divide anything here.
                /** @noinspection SuspiciousAssignmentsInspection */
                $lastPlayed = $this->lastPlayed;
                break;
        }
        return $lastPlayed;
    }

    public function getSettings() : array {
        return $this->settings;
    }

    public function getSettingByID(string $settingID) : ?BaseAttribute {
        if (!isset($this->settings[$settingID])) {
            return null;
        }
        return $this->settings[$settingID];
    }

    public function getSettingNonNullByID(string $settingID) : ?BaseAttribute {
        $setting = $this->getSettingByID($settingID);
        if ($setting === null) {
            $setting = SettingManager::getInstance()->getSettingByID($settingID);
        }
        return $setting;
    }

    public function addSetting(BaseAttribute $setting) : void {
        $this->settings[$setting->getID()] = $setting;
    }

    public function removeSetting(string $settingID) : void {
        unset($this->settings[$settingID]);
    }
}