<?php

namespace ColinHDev\CPlotAPI\players\utils;

use ColinHDev\CPlotAPI\players\PlayerData;
use Throwable;

class PlayerDataException extends \Exception {

    private PlayerData $playerData;

    public function __construct(PlayerData $playerData, $message = "", $code = 0, Throwable $previous = null) {
        $this->playerData = $playerData;
        parent::__construct($message, $code, $previous);
    }

    public function getPlayerData() : PlayerData {
        return $this->playerData;
    }
}