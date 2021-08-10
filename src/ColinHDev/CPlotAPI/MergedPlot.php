<?php

namespace ColinHDev\CPlotAPI;

class MergedPlot extends BasePlot {

    protected int $originX;
    protected int $originZ;

    /**
     * BasePlot constructor.
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     * @param int       $originX
     * @param int       $originZ
     */
    public function __construct(string $worldName, int $x, int $z, int $originX, int $originZ) {
        parent::__construct($worldName, $x, $z);
        $this->originX = $originX;
        $this->originZ = $originZ;
    }

    /**
     * @return int
     */
    public function getOriginX() : int {
        return $this->originX;
    }

    /**
     * @return int
     */
    public function getOriginZ() : int {
        return $this->originZ;
    }

    /**
     * @param BasePlot  $basePlot
     * @param int       $originX
     * @param int       $originZ
     * @return self
     */
    public static function fromBasePlot(BasePlot $basePlot, int $originX, int $originZ) : self {
        return new self(
            $basePlot->getWorldName(), $basePlot->getX(), $basePlot->getZ(),
            $originX, $originZ
        );
    }


    /**
     * @return array
     */
    public function __serialize() : array {
        $data = parent::__serialize();
        $data["originX"] = $this->originX;
        $data["originZ"] = $this->originZ;
        return $data;
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->originX = $data["originX"];
        $this->originZ = $data["originZ"];
    }
}