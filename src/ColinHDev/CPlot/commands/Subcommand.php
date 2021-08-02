<?php

namespace ColinHDev\CPlot\commands;

use pocketmine\command\CommandSender;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\CPlot;

abstract class Subcommand {

    private string $name;
    /** @var string[] */
    private array $alias;
    private string $description;
    private string $usage;
    private string $permission;
    private string $permissionMessage;

    /**
     * Subcommand constructor.
     * @param array     $commandData
     * @param string    $permission
     */
    public function __construct(array $commandData, string $permission) {
        $this->name = $commandData["name"];
        $this->alias = $commandData["alias"];
        $this->description = $commandData["description"];
        $this->usage = $commandData["usage"];
        $this->permission = $permission;
        $this->permissionMessage = $commandData["permissionMessage"];
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAlias() : array {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription() : string {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getUsage() : string {
        return $this->usage;
    }

    /**
     * @return string
     */
    public function getPermissionMessage() : string {
        return $this->permissionMessage;
    }

    /**
     * @return CPlot
     */
    public function getPlugin() : CPlot {
        return CPlot::getInstance();
    }

    /**
     * @return string
     */
    protected function getPrefix() : string {
        return ResourceManager::getInstance()->getPrefix();
    }

    /**
     * @param string    $str
     * @param array     $params
     * @return string
     */
    protected function translateString(string $str, array $params = []) : string {
        return ResourceManager::getInstance()->translateString($str, $params);
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function testPermission(CommandSender $sender) : bool {
        if ($sender->hasPermission($this->permission)) {
            return true;
        }
        $sender->sendMessage($this->getPrefix() . $this->permissionMessage);
        return false;
    }

    /**
     * @param CommandSender $sender
     * @param array         $args
     */
    abstract public function execute(CommandSender $sender, array $args) : void;
}