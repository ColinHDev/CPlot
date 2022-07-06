<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use pocketmine\plugin\ApiVersion;
use pocketmine\utils\VersionString;

final class CPlotAPI {

    public const API_VERSION = "1.0.0";

    private static ?CPlotAPI $instance = null;

    /**
     * @throws \InvalidArgumentException
     */
    public static function getInstance(string $requestedAPI) : self {
        if (!VersionString::isValidBaseVersion($requestedAPI)) {
            throw new \InvalidArgumentException(
                "Invalid API version \"" . $requestedAPI . "\", should contain at least three version digits in the form MAJOR.MINOR.PATCH"
            );
        }
        if (!ApiVersion::isCompatible(self::API_VERSION, [$requestedAPI])) {
            throw new \InvalidArgumentException(
                "Requested API version \"" . $requestedAPI . "\" is not compatible with this plugin's current API version \"" . self::API_VERSION . "\""
            );
        }
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}