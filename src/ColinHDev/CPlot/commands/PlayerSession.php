<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\player\PlayerData;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use function assert;

class PlayerSession extends CommandExecutor {

    protected PlayerData $playerData;

    public function __construct(Player $player, PlayerData $playerData) {
        parent::__construct($player);
        $this->playerData = $playerData;
    }

    public function getSender() : Player {
        assert($this->sender instanceof Player);
        return $this->sender;
    }

    public function getPlayer() : Player {
        return $this->getSender();
    }
    
    public function getPlayerData() : PlayerData {
        return $this->playerData;
    }
    
    public function sendTip(Translatable|string ...$messages) : void {
        $this->getPlayer()->sendTip($this->translate(...$messages));
    }
}