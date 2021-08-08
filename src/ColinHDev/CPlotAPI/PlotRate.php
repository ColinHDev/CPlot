<?php

namespace ColinHDev\CPlotAPI;

class PlotRate {

    private float $rate;
    private string $playerUUID;
    private int $rateTime;
    private ?string $comment;

    /**
     * @param float         $rate
     * @param string        $playerUUID
     * @param int           $rateTime
     * @param string | null $comment
     */
    public function __construct(float $rate, string $playerUUID, int $rateTime, ?string $comment = null) {
        $this->rate = $rate;
        $this->playerUUID = $playerUUID;
        $this->rateTime = $rateTime;
        $this->comment = $comment;
    }

    /**
     * @return float
     */
    public function getRate() : float {
        return $this->rate;
    }

    /**
     * @return string
     */
    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    /**
     * @return int
     */
    public function getRateTime() : int {
        return $this->rateTime;
    }

    /**
     * @return string | null
     */
    public function getComment() : ?string {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function toString() : string {
        return $this->playerUUID . ";" . $this->rateTime;
    }
}