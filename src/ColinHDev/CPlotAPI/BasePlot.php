<?php

namespace ColinHDev\CPlotAPI;

class BasePlot {

    private string $worldName;
    private int $x;
    private int $z;

    /**
     * BasePlot constructor.
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     */
    public function __construct(string $worldName, int $x, int $z) {
        $this->worldName = $worldName;
        $this->x = $x;
        $this->z = $z;
    }

    /**
     * @return string
     */
    public function getWorldName() : string {
        return $this->worldName;
    }

    /**
     * @return int
     */
    public function getX() : int {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getZ() : int {
        return $this->z;
    }

    /**
     * @return string
     */
    public function toString() : string {
        return $this->worldName . ";" . $this->x . ";" . $this->z;
    }
}