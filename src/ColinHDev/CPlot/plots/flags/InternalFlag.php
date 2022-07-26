<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

/**
 * An {@see InternalFlag} is a flag that is not exposed to the users.
 * It can be used to associate information with a plot, without users being able to access the information.
 * These flags are not user assignable, nor do they show up in `/plot info`, `/plot flag list`, etc.
 */
interface InternalFlag {
}