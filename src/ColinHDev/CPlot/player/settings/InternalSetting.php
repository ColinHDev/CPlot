<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

/**
 * An {@see InternalSetting} is a setting that is not exposed to the users.
 * It can be used to associate information with a user, without him being able to access the information.
 * These settings are not user assignable, nor do they show up in `/plot setting my`, `/plot flag list`, etc.
 */
interface InternalSetting {
}