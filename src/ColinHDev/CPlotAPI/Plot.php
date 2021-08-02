<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlotAPI\flags\BaseFlag;
use pocketmine\data\bedrock\BiomeIds;

class Plot extends BasePlot {

    private int $biomeID;
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
     * @param int               $biomeID
     * @param null | string     $ownerUUID
     * @param null | int        $claimTime
     * @param null | string     $alias
     */
    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $ownerUUID = null, ?int $claimTime = null, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
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