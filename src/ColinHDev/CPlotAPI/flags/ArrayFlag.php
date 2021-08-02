<?php

namespace ColinHDev\CPlotAPI\flags;

class ArrayFlag extends BaseFlag {

    protected array $default;
    protected ?array $value = null;

    /**
     * ArrayFlag constructor.
     * @param string    $ID
     * @param array     $data
     */
    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (array) $data["default"];
    }

    /**
     * @return array
     */
    public function getDefault() : array {
        return $this->default;
    }

    /**
     * @return array | null
     */
    public function getValue() : ?array {
        return $this->value;
    }

    /**
     * @param array | null $value
     */
    public function setValue(?array $value) : void {
        $this->value = $value;
    }
}