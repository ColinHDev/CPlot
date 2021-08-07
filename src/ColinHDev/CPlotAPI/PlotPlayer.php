<?php

namespace ColinHDev\CPlotAPI;

class PlotPlayer {

    public const STATE_TRUSTED = "state_trusted";
    public const STATE_HELPER = "state_helper";
    public const STATE_DENIED = "state_denied";

    private string $playerUUID;
    private string $state;
    private int $addTime;

    /**
     * PlotPlayer constructor.
     * @param string        $playerUUID
     * @param string        $state
     * @param int | null    $addTime
     */
    public function __construct(string $playerUUID, string $state, ?int $addTime = null) {
        $this->playerUUID = $playerUUID;
        $this->state = $state;
        $this->addTime = ($addTime !== null) ? $addTime : time();
    }

    /**
     * @return string
     */
    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    /**
     * @return string
     */
    public function getState() : string {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getAddTime() : int {
        return $this->addTime;
    }
}