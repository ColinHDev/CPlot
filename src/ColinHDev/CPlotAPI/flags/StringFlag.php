<?php

namespace ColinHDev\CPlotAPI\flags;

class StringFlag extends BaseFlag {

    protected string $default;
    protected ?string $value = null;

    /**
     * StringFlag constructor.
     * @param string    $id
     * @param array     $data
     */
    public function __construct(string $id, array $data) {
        parent::__construct($id, $data);
        $this->default = (string) $data["default"];
    }

    /**
     * @return string
     */
    public function getDefault() : string {
        return $this->default;
    }

    /**
     * @return string | null
     */
    public function getValue() : ?string {
        return $this->value;
    }

    /**
     * @param string | null $value
     */
    public function setValue(?string $value) : void {
        $this->value = $value;
    }
}