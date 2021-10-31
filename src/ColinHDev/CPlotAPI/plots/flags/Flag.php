<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use ColinHDev\CPlotAPI\attributes\utils\AttributeTypeException;

interface Flag extends FlagIDs {

    public const PERMISSION_BASE = "cplot.flag.";

    public function getID() : string;

    public function getPermission() : string;

    public function getDefault() : string;

    /**
     * @throws AttributeTypeException
     * @throws AttributeParseException
     */
    public function getParsedDefault() : mixed;

    public function getValue() : mixed;

    /**
     * @return BaseAttribute & Flag
     * @throws AttributeTypeException
     */
    public function newInstance(mixed $value = null) : BaseAttribute;

    /**
     * @return BaseAttribute & Flag
     */
    public function merge(mixed $value) : BaseAttribute;

    public function toString(mixed $value = null) : string;

    /**
     * @throws AttributeParseException
     */
    public function parse(string $value) : mixed;
}