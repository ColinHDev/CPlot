<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use pocketmine\utils\SingletonTrait;

/**
 * This class contains the subcommand
 */
final class SubcommandConfirmationManager {
    use SingletonTrait;

    private array $confirmations = [];
    /** @var array<string, callable> */
    private array $confirmationCallbacks = [];
    /** @var array<string, callable> */
    private array $declinationCallbacks = [];
}