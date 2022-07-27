<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingManager;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use function is_string;

class PlayerData {

    private int $playerID;
    private ?string $playerUUID;
    private ?string $playerXUID;
    private ?string $playerName;
    private int $lastJoin;

    /** @phpstan-var array<string, Setting<mixed>> */
    private array $settings;

    /**
     * @phpstan-param array<string, Setting<mixed>> $settings
     */
    public function __construct(int $playerID, ?string $playerUUID, ?string $playerXUID, ?string $playerName, int $lastJoin, array $settings) {
        $this->playerID = $playerID;
        $this->playerUUID = $playerUUID;
        $this->playerXUID = $playerXUID;
        $this->playerName = $playerName;
        $this->lastJoin = $lastJoin;
        $this->settings = $settings;
    }

    public function getPlayerID() : int {
        return $this->playerID;
    }

    public function getPlayerUUID() : ?string {
        return $this->playerUUID;
    }

    public function getPlayerXUID() : ?string {
        return $this->playerXUID;
    }

    public function getPlayerName() : ?string {
        return $this->playerName;
    }

    /**
     * Check whether  this class instance represents the {@see PlayerData} of the given {@see Player}.
     * @param Player $player The player to check.
     * Returns true if the given player is the same as the player this class instance represents.
     * @return bool
     */
    public function isSame(Player $player) : bool {
        if (is_string($this->playerUUID)) {
            return $this->playerUUID === $player->getUniqueId()->getBytes();
        }
        if (is_string($this->playerXUID)) {
            return $this->playerXUID === $player->getXuid();
        }
        if (is_string($this->playerName)) {
            return $this->playerName === $player->getName();
        }
        return false;
    }

    /**
     * Returns the {@see Player} instance of the player this data is referring to or null if not online.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getPlayer() : ?Player {
        if ($this->playerUUID !== null) {
            return Server::getInstance()->getPlayerByRawUUID($this->playerUUID);
        }
        if ($this->playerXUID !== null) {
            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                if ($onlinePlayer->getXuid() === $this->playerXUID) {
                    return $onlinePlayer;
                }
            }
            return null;
        }
        if ($this->playerName !== null) {
            return Server::getInstance()->getPlayerExact($this->playerName);
        }
        return null;
    }

    /**
     * Returns the last time a player joined in seconds.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getLastJoin() : int {
        // player is online and therefore not inactive
        $player = $this->getPlayer();
        if ($player !== null) {
            return time();
        }

        // check if the last time the player played should be fetched from the offline data file or the database
        switch (ResourceManager::getInstance()->getConfig()->get("lastPlayed.origin", "database")) {
            case "offline_data":
                if ($this->playerName !== null) {
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
                }
            default:
            case "database":
                // Since CPlot stores the last time a player joined in seconds, we do not need to divide anything here.
                $lastPlayed = $this->lastJoin;
                break;
        }
        return $lastPlayed;
    }

    /**
     * @phpstan-return array<string, Setting<mixed>>
     */
    public function getSettings() : array {
        return $this->settings;
    }

    /**
     * @phpstan-template TSetting of Setting<mixed>
     * @phpstan-param TSetting $setting
     * @phpstan-return TSetting
     */
    public function getSetting(Setting $setting) : Setting {
        /** @phpstan-var TSetting $setting */
        $setting = $this->getSettingByID($setting->getID());
        return $setting;
    }

    /**
     * @phpstan-template TSetting of Setting<mixed>
     * @phpstan-param TSetting $setting
     * @phpstan-return TSetting|null
     */
    public function getLocalSetting(Setting $setting) : ?Setting {
        /** @phpstan-var TSetting|null $setting */
        $setting = $this->getLocalSettingByID($setting->getID());
        return $setting;
    }

    /**
     * @phpstan-return Setting<mixed>
     */
    public function getSettingByID(string $settingID) : Setting {
        $setting = $this->getLocalSettingByID($settingID);
        if ($setting === null) {
            $setting = SettingManager::getInstance()->getSettingByID($settingID);
            assert($setting instanceof Setting);
        }
        return $setting;
    }

    /**
     * @phpstan-return Setting<mixed>|null
     */
    public function getLocalSettingByID(string $settingID) : ?Setting {
        if (!isset($this->settings[$settingID])) {
            return null;
        }
        return $this->settings[$settingID];
    }

    /**
     * @template TSetting of Setting<mixed>
     * @param TSetting $setting
     */
    public function addSetting(Setting $setting) : void {
        $this->settings[$setting->getID()] = $setting;
    }

    public function removeSetting(string $settingID) : void {
        unset($this->settings[$settingID]);
    }

    /**
     * @phpstan-return array{playerID: int, playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: int, settings: string}
     */
    public function __serialize() : array {
        return [
            "playerID" => $this->playerID,
            "playerUUID" => $this->playerUUID,
            "playerXUID" => $this->playerXUID,
            "playerName" => $this->playerName,
            "lastJoin" => $this->lastJoin,
            "settings" => serialize($this->settings)
        ];
    }

    /**
     * @phpstan-param array{playerID: int, playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: int, settings: string} $data
     */
    public function __unserialize(array $data) : void {
        $this->playerID = $data["playerID"];
        $this->playerUUID = $data["playerUUID"];
        $this->playerXUID = $data["playerXUID"];
        $this->playerName = $data["playerName"];
        $this->lastJoin = $data["lastJoin"];
        $settings = unserialize($data["settings"], ["allowed_classes" => false]);
        assert(is_array($settings));
        /** @phpstan-var array<string, Setting<mixed>> $settings */
        $this->settings = $settings;
    }
}