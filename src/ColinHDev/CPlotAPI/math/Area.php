<?php

namespace ColinHDev\CPlotAPI\math;

class Area {

    private int $xMin;
    private int $zMin;
    private int $xMax;
    private int $zMax;

    /**
     * @param int $x1
     * @param int $z1
     * @param int $x2
     * @param int $z2
     */
    public function __construct(int $x1, int $z1, int $x2, int $z2) {
        $this->xMin = (int) min($x1, $x2);
        $this->zMin = (int) min($z1, $z2);
        $this->xMax = (int) max($x1, $x2);
        $this->zMax = (int) max($z1, $z2);
    }

    /**
     * @return int
     */
    public function getXMin() : int {
        return $this->xMin;
    }

    /**
     * @return int
     */
    public function getZMin() : int {
        return $this->zMin;
    }

    /**
     * @return int
     */
    public function getXMax() : int {
        return $this->xMax;
    }

    /**
     * @return int
     */
    public function getZMax() : int {
        return $this->zMax;
    }

    /**
     * @return string
     */
    public function toString() : string {
        return $this->xMin . ";" . $this->zMin . ";" . $this->xMax . ";" . $this->zMax;
    }
}