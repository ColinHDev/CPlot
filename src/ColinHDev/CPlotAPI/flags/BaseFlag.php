<?php

namespace ColinHDev\CPlotAPI\flags;

abstract class BaseFlag implements FlagIDs {

    protected string $id;
    protected string $category;
    protected string $valueType;
    protected string $description;

    /**
     * BaseFlag constructor.
     * @param string    $id
     * @param array     $data
     */
    public function __construct(string $id, array $data) {
        $this->id = $id;
        $this->category = $data["category"];
        $this->valueType = $data["type"];
        $this->description = $data["description"];
    }

    /**
     * @return int
     */
    public function getId() : int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCategory() : string {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getValueType() : string {
        return $this->valueType;
    }

    /**
     * @return string
     */
    public function getDescription() : string {
        return $this->description;
    }
}