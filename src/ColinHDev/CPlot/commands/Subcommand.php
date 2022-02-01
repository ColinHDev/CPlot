<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use poggit\libasynql\SqlError;

/**
 * @phpstan-template GeneratorReturn
 */
abstract class Subcommand {

    private string $name;
    /** @var string[] */
    private array $alias;
    private string $description;
    private string $usage;
    private string $permission;
    private string $permissionMessage;

    /**
     * @phpstan-param array{name: string, alias: array<string>, description: string, usage: string, permissionMessage: string} $commandData
     */
    public function __construct(array $commandData, string $permission) {
        $this->name = $commandData["name"];
        $this->alias = $commandData["alias"];
        $this->description = $commandData["description"];
        $this->usage = $commandData["usage"];
        $this->permission = $permission;
        $this->permissionMessage = $commandData["permissionMessage"];
    }

    public function getName() : string {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getAlias() : array {
        return $this->alias;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function getUsage() : string {
        return $this->usage;
    }

    public function getPermission() : string {
        return $this->permission;
    }

    public function getPermissionMessage() : string {
        return $this->permissionMessage;
    }

    protected function getPrefix() : string {
        return ResourceManager::getInstance()->getPrefix();
    }

    /**
     * @phpstan-param (float|int|string|Translatable)[] $params
     */
    protected function translateString(string $str, array $params = []) : string {
        return ResourceManager::getInstance()->translateString($str, $params);
    }

    public function testPermission(CommandSender $sender) : bool {
        if ($sender->hasPermission($this->permission)) {
            return true;
        }
        $sender->sendMessage($this->getPrefix() . $this->permissionMessage);
        return false;
    }

    /**
     * This generator function contains the code you want to be executed when the command is run.
     * @param string[] $args
     * @phpstan-return \Generator<mixed, mixed, mixed, GeneratorReturn|null>
     */
    abstract public function execute(CommandSender $sender, array $args) : \Generator;

    /**
     * Overwrite this method to handle the return value of the generator function {@see Subcommand::execute()}.
     * @phpstan-param GeneratorReturn $return
     */
    public function onSuccess(CommandSender $sender, mixed $return) : void {
    }

    /**
     * Overwrite this method to handle any exceptions that were thrown during the executing of
     * {@see Subcommand::execute()}, e.g. {@see SqlError} when interacting with the database.
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
    }
}