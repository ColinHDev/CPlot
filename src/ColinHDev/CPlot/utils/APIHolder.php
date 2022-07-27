<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils;

use ColinHDev\CPlot\CPlotAPI;
use InvalidArgumentException;

/**
 * To prevent e.g. event listeners from constantly calling {@see CPlotAPI::getInstance()}, which would require parsing
 * the provided, probably same version string over and over again, this trait removes duplicate and pointless calls of
 * this method and only does so if the provided version string changed.
 */
trait APIHolder {

    private ?string $version = null;
    private ?CPlotAPI $api = null;

    /**
     * @param string $version
     * The version string of the CPlotAPI instance to use. The default value is {@see CPlotAPI::API_VERSION}, so that
     * e.g. this plugin's event listeners do not have to import the {@see CPlotAPI} class just for the version constant.
     * When using this method in another plugin, you should always provide the version string, otherwise, it would defeat
     * the point of the API version system entirely if everyone would just use the API_VERSION constant. Using the
     * default value only makes sense in this plugin since it should always work with the current, most recent version
     * of its own API.
     *
     * @throws InvalidArgumentException if the provided version string is either invalid or not compatible with the
     *                                  current API version
     */
    public function getAPI(string $version = CPlotAPI::API_VERSION) : CPlotAPI {
        if ($this->api === null || $this->version !== $version) {
            $this->api = CPlotAPI::getInstance($version);
            $this->version = $version;
        }
        return $this->api;
    }
}