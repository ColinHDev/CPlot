<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;
use Generator;
use pocketmine\command\CommandSender;
use SOFe\AwaitGenerator\Await;

abstract class AsyncSubcommand extends Subcommand {

    final public function execute(CommandSender $sender, array $args) : void {
        Await::g2c(
            $this->executeAsync($sender, $args)
        );
    }

    /**
     * This generator function contains the code you want to be executed when the command is run.
     * Asynchronous code can be used in this function with {@see Await::g2c()}.
     * @param string[] $args
     * @phpstan-return Generator<mixed, mixed, mixed, mixed>
     */
    abstract public function executeAsync(CommandSender $sender, array $args) : Generator;
}