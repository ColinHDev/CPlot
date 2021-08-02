<?php

namespace ColinHDev\CPlotAPI\flags;

class BooleanFlag extends BaseFlag {

    protected bool $default;
    protected ?bool $value = null;

    /**
     * BooleanFlag constructor.
     * @param string    $ID
     * @param array     $data
     */
    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (bool) $data["default"];
    }

    /**
     * @return bool
     */
    public function getDefault() : bool {
        return $this->default;
    }

    /**
     * @return bool | null
     */
    public function getValue() : ?bool {
        return $this->value;
    }

    /**
     * @param bool | null $value
     */
    public function setValue(?bool $value) : void {
        $this->value = $value;
    }
}