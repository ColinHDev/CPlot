<?php

namespace ColinHDev\CPlotAPI;

class PlotPlayer {

    public const STATE_TRUSTED = "state_trusted";
    public const STATE_HELPER = "state_helper";
    public const STATE_DENIED = "state_denied";

    private string $playerUUID;
    private string $state;
    private int $addTime;

    public function __construct(string $playerUUID, string $state, ?int $addTime = null) {
        $this->playerUUID = $playerUUID;
        $this->state = $state;
        $this->addTime = ($addTime !== null) ? $addTime : (int) (round(microtime(true) * 1000));
    }

    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    public function getState() : string {
        return $this->state;
    }

    public function getAddTime() : int {
        return $this->addTime;
    }

    public function __serialize() : array {
        return [
            "playerUUID" => $this->playerUUID,
            "state" => $this->state,
            "addTime" => $this->addTime
        ];
    }

    public function __unserialize(array $data) : void {
        $this->playerUUID = $data["playerUUID"];
        $this->state = $data["state"];
        $this->addTime = $data["addTime"];
    }
}