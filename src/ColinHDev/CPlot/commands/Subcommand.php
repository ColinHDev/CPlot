<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\language\KnownTranslationFactory;
use ColinHDev\CPlot\language\LanguageManager;
use ColinHDev\CPlot\provider\LanguageProvider;
use ColinHDev\CPlot\utils\APIHolder;
use ColinHDev\CPlot\utils\ParseUtils;

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

    public function testPermission(CommandExecutor $sender) : bool {
        if ($sender->getSender()->hasPermission($this->permission)) {
            return true;
        }
        $sender->sendMessage(KnownTranslationFactory::prefix());
        self::sendMessage($sender, ["prefix", $this->key . ".permissionMessage"]);
        return false;
    }

    /**
     * This method contains the code you want to be executed when the command is run.
     * @param string[] $args
     */
    abstract public function execute(CommandExecutor $sender, array $args) : void;
}