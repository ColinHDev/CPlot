<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\commands\subcommands\AddSubcommand;
use ColinHDev\CPlot\commands\subcommands\AutoSubcommand;
use ColinHDev\CPlot\commands\subcommands\BiomeSubcommand;
use ColinHDev\CPlot\commands\subcommands\BorderSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClaimSubcommand;
use ColinHDev\CPlot\commands\subcommands\ClearSubcommand;
use ColinHDev\CPlot\commands\subcommands\DeniedSubcommand;
use ColinHDev\CPlot\commands\subcommands\DenySubcommand;
use ColinHDev\CPlot\commands\subcommands\FlagSubcommand;
use ColinHDev\CPlot\commands\subcommands\GenerateSubcommand;
use ColinHDev\CPlot\commands\subcommands\HelpersSubcommand;
use ColinHDev\CPlot\commands\subcommands\HelpSubcommand;
use ColinHDev\CPlot\commands\subcommands\InfoSubcommand;
use ColinHDev\CPlot\commands\subcommands\MergeSubcommand;
use ColinHDev\CPlot\commands\subcommands\RemoveSubcommand;
use ColinHDev\CPlot\commands\subcommands\ResetSubcommand;
use ColinHDev\CPlot\commands\subcommands\SchematicSubcommand;
use ColinHDev\CPlot\commands\subcommands\SettingSubcommand;
use ColinHDev\CPlot\commands\subcommands\TrustedSubcommand;
use ColinHDev\CPlot\commands\subcommands\TrustSubcommand;
use ColinHDev\CPlot\commands\subcommands\UndenySubcommand;
use ColinHDev\CPlot\commands\subcommands\UntrustSubcommand;
use ColinHDev\CPlot\commands\subcommands\VisitSubcommand;
use ColinHDev\CPlot\commands\subcommands\WallSubcommand;
use ColinHDev\CPlot\commands\subcommands\WarpSubcommand;
use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use SOFe\AwaitGenerator\Await;

class PlotCommand extends Command implements PluginOwned {

    /** @var array<string, Subcommand<mixed, mixed, mixed, mixed>> */
    private array $subcommands = [];

    /**
     * @throws \InvalidArgumentException|\JsonException
     */
    public function __construct() {
        $languageProvider = LanguageManager::getInstance()->getProvider();
        parent::__construct(
            $languageProvider->translateString("plot.name"),
            $languageProvider->translateString("plot.description")
        );
        $alias = json_decode($languageProvider->translateString("plot.alias"), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($alias));
        $this->setAliases($alias);
        $this->setPermission("cplot.command.plot");

        $this->registerSubcommand(new AddSubcommand("add"));
        $this->registerSubcommand(new AutoSubcommand("auto", $this));
        $this->registerSubcommand(new BiomeSubcommand("biome"));
        $this->registerSubcommand(new BorderSubcommand("border"));
        $this->registerSubcommand(new ClaimSubcommand("claim"));
        $this->registerSubcommand(new ClearSubcommand("clear"));
        $this->registerSubcommand(new DeniedSubcommand("denied"));
        $this->registerSubcommand(new DenySubcommand("deny"));
        $this->registerSubcommand(new FlagSubcommand("flag"));
        $this->registerSubcommand(new GenerateSubcommand("generate"));
        $this->registerSubcommand(new HelpersSubcommand("helpers"));
        $this->registerSubcommand(new HelpSubcommand("help", $this));
        $this->registerSubcommand(new InfoSubcommand("info"));
        $this->registerSubcommand(new MergeSubcommand("merge"));
        $this->registerSubcommand(new RemoveSubcommand("remove"));
        $this->registerSubcommand(new ResetSubcommand("reset"));
        $this->registerSubcommand(new SchematicSubcommand("schematic"));
        $this->registerSubcommand(new SettingSubcommand("setting"));
        $this->registerSubcommand(new TrustedSubcommand("trusted"));
        $this->registerSubcommand(new TrustSubcommand("trust"));
        $this->registerSubcommand(new UndenySubcommand("undeny"));
        $this->registerSubcommand(new UntrustSubcommand("untrust"));
        $this->registerSubcommand(new VisitSubcommand("visit"));
        $this->registerSubcommand(new WallSubcommand("wall"));
        $this->registerSubcommand(new WarpSubcommand("warp"));
    }

    /**
     * @phpstan-return array<string, Subcommand<mixed, mixed, mixed, mixed>>
     */
    public function getSubcommands() : array {
        return $this->subcommands;
    }

    /**
     * @phpstan-return null|Subcommand<mixed, mixed, mixed, mixed>
     */
    public function getSubcommandByName(string $name) : ?Subcommand {
        return $this->subcommands[$name] ?? null;
    }

    /**
     * @phpstan-template TSubcommand of Subcommand<mixed, mixed, mixed, mixed>
     * @phpstan-param TSubcommand $subcommand
     */
    public function registerSubcommand(Subcommand $subcommand) : void {
        $this->subcommands[$subcommand->getName()] = $subcommand;
        foreach ($subcommand->getAlias() as $alias) {
            $this->subcommands[$alias] = $subcommand;
        }
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if (!$this->testPermissionSilent($sender)) {
            LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "plot.permissionMessage"]);
            return;
        }

        if (count($args) === 0) {
            LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "plot.usage"]);
            return;
        }

        $subcommand = strtolower(array_shift($args));
        if (!isset($this->subcommands[$subcommand])) {
            LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "plot.unknownSubcommand"]);
            return;
        }

        $command = $this->subcommands[$subcommand];
        if (!$command->testPermission($sender)) {
            return;
        }
        Await::g2c(
            $command->execute($sender, $args),
            static function (mixed $return = null) use ($command, $sender) : void {
                if ($return !== null) {
                    $command->onSuccess($sender, $return);
                }
            },
            static function (?\Throwable $error = null) use ($command, $sender) : void {
                if ($error !== null) {
                    $command->onError($sender, $error);
                }
            }
        );
    }

    public function getOwningPlugin() : Plugin {
        return CPlot::getInstance();
    }
}