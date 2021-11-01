<?php

namespace ColinHDev\CPlotAPI\plots;

use ColinHDev\CPlot\CPlot;

class MergePlot extends BasePlot {

    protected int $originX;
    protected int $originZ;

    public function __construct(string $worldName, int $x, int $z, int $originX, int $originZ) {
        parent::__construct($worldName, $x, $z);
        $this->originX = $originX;
        $this->originZ = $originZ;
    }

    public function getOriginX() : int {
        return $this->originX;
    }

    public function getOriginZ() : int {
        return $this->originZ;
    }

    public function toBasePlot() : BasePlot {
        return new BasePlot($this->worldName, $this->x, $this->z);
    }

    public function toPlot() : ?Plot {
        return CPlot::getInstance()->getProvider()->getPlot($this->worldName, $this->originX, $this->originZ);
    }

    public static function fromBasePlot(BasePlot $basePlot, int $originX, int $originZ) : self {
        return new self(
            $basePlot->getWorldName(), $basePlot->getX(), $basePlot->getZ(),
            $originX, $originZ
        );
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["originX"] = $this->originX;
        $data["originZ"] = $this->originZ;
        return $data;
    }

    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->originX = $data["originX"];
        $this->originZ = $data["originZ"];
    }
}