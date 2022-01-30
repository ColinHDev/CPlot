<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

class PlotRate {

    private string $rate;
    private string $playerUUID;
    private int $rateTime;
    private ?string $comment;

    public function __construct(string $rate, string $playerUUID, int $rateTime, ?string $comment = null) {
        $this->rate = $rate;
        $this->playerUUID = $playerUUID;
        $this->rateTime = $rateTime;
        $this->comment = $comment;
    }

    public function getRate() : string {
        return $this->rate;
    }

    public function getPlayerUUID() : string {
        return $this->playerUUID;
    }

    public function getRateTime() : int {
        return $this->rateTime;
    }

    public function getComment() : ?string {
        return $this->comment;
    }

    public function toString() : string {
        return $this->playerUUID . ";" . $this->rateTime;
    }

    /**
     * @phpstan-return array{rate: string, playerUUID: string, rateTime: int, comment: string|null}
     */
    public function __serialize() : array {
        return [
            "rate" => $this->rate,
            "playerUUID" => $this->playerUUID,
            "rateTime" => $this->rateTime,
            "comment" => $this->comment
        ];
    }

    /**
     * @phpstan-param array{rate: string, playerUUID: string, rateTime: int, comment: string|null} $data
     */
    public function __unserialize(array $data) : void {
        $this->rate = $data["rate"];
        $this->playerUUID = $data["playerUUID"];
        $this->rateTime = $data["rateTime"];
        $this->comment = $data["comment"];
    }
}