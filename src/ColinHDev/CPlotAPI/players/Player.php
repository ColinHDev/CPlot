<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\provider\Cacheable;

class Player implements Cacheable {

    private string $playerUUID;
    private string $playerName;
    private int $lastPlayed;

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
}