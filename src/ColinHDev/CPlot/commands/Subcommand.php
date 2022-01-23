<?php

namespace ColinHDev\CPlot\commands;

use pocketmine\command\CommandSender;
use ColinHDev\CPlot\ResourceManager;

abstract class Subcommand {

    private string $name;
    /** @var string[] */
    private array $alias;
    private string $description;
    private string $usage;
    private string $permission;
    private string $permissionMessage;

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

    abstract public function execute(CommandSender $sender, array $args) : void;
}