<?php

namespace ColinHDev\CPlotAPI;

class PlotPlayer {

    public const STATE_TRUSTED = 0;
    public const STATE_HELPER = 1;
    public const STATE_DENIED = 2;

    private string $playerUUID;
    private int $state;
    private int $addTime;

    /**
     * PlotPlayer constructor.
     * @param string    $playerUUID
     * @param int       $state
     * @param int       $addTime
     */
    public function __construct(string $playerUUID, int $state, int $addTime) {
        $this->playerUUID = $playerUUID;
        $this->state = $state;
        $this->addTime = $addTime;
    }
}