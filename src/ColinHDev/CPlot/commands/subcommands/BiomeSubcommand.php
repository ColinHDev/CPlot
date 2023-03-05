<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\BiomeChangeLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\tasks\async\PlotBiomeChangeAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class BiomeSubcommand extends AsyncSubcommand {

    /** @phpstan-var array<string, BiomeIds::*> */
    private array $biomes;

    public function __construct(string $key) {
        parent::__construct($key);
        /** @phpstan-var array<string, BiomeIds::*> $biomes */
        $biomes = (new \ReflectionClass(BiomeIds::class))->getConstants();
        $this->biomes = $biomes;
    }

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!($sender instanceof Player)) {
            self::sendMessage($sender, ["prefix", "biome.senderNotOnline"]);
            return;
        }

        $position = $sender->getPosition();
        $world = $position->world;
        assert($world instanceof World);
        if (!((yield DataProvider::getInstance()->awaitWorld($world->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "biome.noPlotWorld"]);
            return;
        }

        if (count($args) === 0) {
            $biomeName = $this->getBiomeNameByID($world->getBiomeId($position->getFloorX(), $position->getFloorY(), $position->getFloorZ()));
            self::sendMessage($sender, ["prefix", "biome.plotBiome" => $biomeName]);
            return;
        }
        $biomeName = strtoupper(implode("_", $args));
        if (!isset($this->biomes[$biomeName])) {
            $biomes = [];
            foreach ($this->biomes as $name => $ID) {
                $biomes[] = self::translateForCommandSender(
                    $sender,
                    ["biome.list" => $name]
                );
            }
            /** @phpstan-var string $separator */
            $separator = self::translateForCommandSender($sender, "biome.list.separator");
            $list = implode($separator, $biomes);
            self::sendMessage(
                $sender,
                [
                    "prefix",
                    "biome.invalidBiome" => [$biomeName, $list]
                ]
            );
            return;
        }
        $biomeID = $this->biomes[$biomeName];

        $plot = yield Plot::awaitFromPosition($position);
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "biome.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.biome")) {
            if (!$plot->hasPlotOwner()) {
                self::sendMessage($sender, ["prefix", "biome.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "biome.notPlotOwner"]);
                return;
            }
        }

        $lock = new BiomeChangeLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "biome.plotLocked"]);
            return;
        }

        self::sendMessage($sender, ["prefix", "biome.start"]);
        /** @phpstan-var PlotBiomeChangeAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->setBiome($biomeID, $resolve)
        );
        $plotCount = count($plot->getMergePlots()) + 1;
        $plots = array_map(
            static function (BasePlot $plot) : string {
                return $plot->toSmallString();
            },
            array_merge([$plot], $plot->getMergePlots())
        );
        $biomeID = $task->getBiomeID();
        $biomeName = $this->getBiomeNameByID($biomeID);
        $elapsedTimeString = $task->getElapsedTimeString();
        Server::getInstance()->getLogger()->debug(
            "Changing plot biome to " . $biomeName . "(ID: " . $biomeID . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($sender, ["prefix", "biome.finish" => [$elapsedTimeString, $biomeName]]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }

    /**
     * This method is used to get the name of a biome by its ID.
     */
    private function getBiomeNameByID(int $biomeID) : string {
        $biomeName = array_search($biomeID, $this->biomes, true);
        if (!is_string($biomeName)) {
            $biomeName = "Unknown (BiomeID: " . $biomeID . ")";
        }
        return $biomeName;
    }
}