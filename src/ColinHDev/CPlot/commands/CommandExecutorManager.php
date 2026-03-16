<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\player\PlayerData;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use WeakMap;

final class CommandExecutorManager {
    use SingletonTrait;

    private WeakMap $playerSessions;

    public function __construct() {
        $this->playerSessions = new WeakMap();
    }
    
    public function registerPlayerSession(Player $player, PlayerData $playerData) : void {
        $this->playerSessions[$player] = new PlayerSession($player, $playerData);
    }
    
    public function getPlayerSession(Player $player) : ?PlayerSession {
        return $this->playerSessions[$player] ?? null;
    }
}