<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\language\LanguageManager;
use ColinHDev\CPlot\provider\LanguageProvider;
use ColinHDev\CPlot\utils\APIHolder;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\command\CommandSender;

/**
 * @phpstan-import-type MessageKey from LanguageProvider
 * @phpstan-import-type MessageParam from LanguageProvider
 */
abstract class Subcommand {
    use APIHolder;

    private string $key;
    private string $name;
    /** @var array<string> */
    private array $alias;
    private string $permission;

    public function __construct(string $key) {
        $this->key = $key;
        $languageProvider = LanguageManager::getInstance()->getProvider();
        $this->name = $languageProvider->translateString($key . ".name");
        $this->alias = ParseUtils::parseAliasesFromString($languageProvider->translateString($key . ".alias"));
        $this->permission = "cplot.subcommand." . $key;
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

    public function getPermission() : string {
        return $this->permission;
    }

    public function testPermission(CommandSender $sender) : bool {
        if ($sender->hasPermission($this->permission)) {
            return true;
        }
        self::sendMessage($sender, ["prefix", $this->key . ".permissionMessage"]);
        return false;
    }

    /**
     * This method contains the code you want to be executed when the command is run.
     * @param string[] $args
     */
    abstract public function execute(CommandSender $sender, array $args) : void;

    /**
     * Utility method to send a message to a command sender, while also removing some boilerplate code within the
     * subcommand classes.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    final protected static function sendMessage(CommandSender $sender, array|string $keys) : void {
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, $keys);
    }

    /**
     * Utility method to translate a message for a command sender, while also removing some boilerplate code within the
     * subcommand classes.
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    final protected static function translateForCommandSender(CommandSender $sender, array|string $keys) : string {
        return LanguageManager::getInstance()->getProvider()->translateForCommandSender($sender, $keys);
    }
}