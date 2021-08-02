<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlotAPI\flags\BaseFlag;

class Plot extends BasePlot {

    private ?string $ownerUUID;
    private ?int $claimTime;
    private ?string $alias;

    /** @var null | BasePlot[] */
    private ?array $mergedPlotIDs = null;

    /** @var null | PlotPlayer[] */
    private ?array $plotPlayers = null;

    /** @var null | BaseFlag[] */
    private ?array $flags = null;

    /**
     * Plot constructor.
     * @param string            $worldName
     * @param int               $x
     * @param int               $z
     * @param null | string     $ownerUUID
     * @param null | int        $claimTime
     * @param null | string     $alias
     */
    public function __construct(string $worldName, int $x, int $z, ?string $ownerUUID = null, ?int $claimTime = null, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->ownerUUID = $ownerUUID;
        $this->claimTime = $claimTime;
        $this->alias = $alias;
    }

    /**
     * @return string | null
     */
    public function getOwnerUUID() : ?string {
        return $this->ownerUUID;
    }

    /**
     * @return int | null
     */
    public function getClaimTime() : ?int {
        return $this->claimTime;
    }

    /**
     * @return string | null
     */
    public function getAlias() : ?string {
        return $this->alias;
    }
}