<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds;

use ColinHDev\CPlot\provider\cache\Cache;
use ColinHDev\CPlot\provider\cache\Cacheable;

/**
 * Dummy class to tell that a specific world in the @see Cache is not a plot world.
 */
class NonWorldSettings implements Cacheable {
}