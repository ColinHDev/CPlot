<?php

namespace ColinHDev\CPlotAPI\flags;

use pocketmine\math\Vector3;

class PositionFlag extends BaseFlag {

    protected ?Vector3 $value = null;

    /**
     * @return Vector3 | null
     */
    public function getValue() : ?Vector3 {
        return $this->value;
    }

    /**
     * @param Vector3 | null $value
     */
    public function setValue(?Vector3 $value) : void {
        $this->value = $value;
    }
}