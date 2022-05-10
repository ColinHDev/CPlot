<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\command\CommandSender;
use poggit\libasynql\SqlError;

/**
 * @phpstan-template GeneratorKey
 * @phpstan-template GeneratorValue
 * @phpstan-template GeneratorSend
 * @phpstan-template GeneratorReturn
 */
abstract class Subcommand {

    private string $key;
    private string $name;
    /** @var array<string> */
    private array $alias;
    private string $permission;

    /**
     * @throws \JsonException
     */
    public function __construct(string $key) {
        $this->key = $key;
        $languageProvider = LanguageManager::getInstance()->getProvider();
        $this->name = $languageProvider->translateString($key . ".name");
        $alias = json_decode($languageProvider->translateString($key . ".alias"), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($alias));
        /** @phpstan-var array<string> $alias */
        $this->alias = $alias;
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
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", $this->key . ".permissionMessage"]);
        return false;
    }

    /**
     * This generator function contains the code you want to be executed when the command is run.
     * @param string[] $args
     * @phpstan-return \Generator<GeneratorKey, GeneratorValue, GeneratorSend, GeneratorReturn|null>
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