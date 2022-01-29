<?php

namespace ColinHDev\CPlot\math;

class Area {

    private int $xMin;
    private int $zMin;
    private int $xMax;
    private int $zMax;

    public function __construct(int $x1, int $z1, int $x2, int $z2) {
        $this->xMin = min($x1, $x2);
        $this->zMin = min($z1, $z2);
        $this->xMax = max($x1, $x2);
        $this->zMax = max($z1, $z2);
    }

    public function getXMin() : int {
        return $this->xMin;
    }

    public function getZMin() : int {
        return $this->zMin;
    }

    public function getXMax() : int {
        return $this->xMax;
    }

    public function getZMax() : int {
        return $this->zMax;
    }

    public function isInside(int $x, int $z) : bool {
        return
            $x >= $this->xMin && $x <= $this->xMax &&
            $z >= $this->zMin && $z <= $this->zMax;
    }

    public function toString() : string {
        return $this->xMin . ";" . $this->zMin . ";" . $this->xMax . ";" . $this->zMax;
    }
}