<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\tasks\async\SchematicSaveAsyncTask;
use ColinHDev\CPlot\worlds\generator\SchematicGenerator;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use ColinHDev\CPlot\worlds\schematic\SchematicTypes;
use ColinHDev\CPlot\worlds\WorldSettings;
use JsonException;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;
use RuntimeException;
use function count;
use function date;
use function explode;
use function implode;
use function is_dir;
use function mkdir;
use function pathinfo;
use function scandir;
use const DIRECTORY_SEPARATOR;

class SchematicSubcommand extends Subcommand {

    /**
     * @throws JsonException
     */
    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "schematic.usage"]);
            return;
        }
        switch ($args[0]) {
            case "list":
                if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) {
                    $files = [];
                    $dir = scandir(CPlot::getInstance()->getDataFolder() . "schematics");
                    if ($dir !== false) {
                        foreach ($dir as $file) {
                            /** @phpstan-var array{dirname: string, basename: string, extension?: string, filename: string} $fileData */
                            $fileData = pathinfo($file);
                            if (!isset($fileData["extension"]) || $fileData["extension"] !== Schematic::FILE_EXTENSION) {
                                continue;
                            }
                            $files[] = self::translateForCommandSender($sender, ["format.list.schematic" => $fileData["filename"]]);
                        }
                    }
                    if (count($files) > 0) {
                        self::sendMessage($sender, [
                            "prefix", 
                            "schematic.list.success" => implode(
                                self::translateForCommandSender($sender, "format.list.schematic.separator"), 
                                $files
                            )
                        ]);
                        break;
                    }
                }
                self::sendMessage($sender, ["prefix", "schematic.list.noSchematics"]);
                break;

            case "info":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "schematic.info.usage"]);
                    break;
                }
                $file = CPlot::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR . $args[1] . "." . Schematic::FILE_EXTENSION;
                if (!file_exists($file)) {
                    self::sendMessage($sender, ["prefix", "schematic.info.schematicNotFound" => $args[1]]);
                    break;
                }
                $schematic = new Schematic($file);
                try {
                    $schematic->loadFromFile();
                } catch (RuntimeException) {
                    self::sendMessage($sender, ["prefix", "schematic.info.loadError" => $args[1]]);
                    break;
                }
                self::sendMessage($sender, [
                    "prefix", 
                    "schematic.info.success" => [
                        $args[1],
                        self::translateForCommandSender(
                            $sender, ["format.time" => explode(".", date("Y.m.d.H.i.s", $schematic->getCreationTime()))]
                        ),
                        $schematic->getType(),
                        $schematic->getRoadSize(),
                        $schematic->getPlotSize()
                    ]
                ]);
                break;

            case "save":
                if (!isset($args[1], $args[2])) {
                    self::sendMessage($sender, ["prefix", "schematic.save.usage"]);
                    break;
                }
                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "schematic.save.senderNotOnline"]);
                    break;
                }
                $schematicName = $args[1];
                $dir = CPlot::getInstance()->getDataFolder() . "schematics";
                if (!is_dir($dir) && mkdir($dir) === false) {
                    self::sendMessage($sender, ["prefix", "schematic.save.directoryNotFound"]);
                    break;
                }
                $file = $dir . DIRECTORY_SEPARATOR . $schematicName . "." . Schematic::FILE_EXTENSION;
                if (file_exists($file)) {
                    self::sendMessage($sender, ["prefix", "schematic.save.schematicExists" => $schematicName]);
                    break;
                }
                $schematicType = strtolower($args[2]);
                if ($schematicType !== "road" && $schematicType !== "plot") {
                    self::sendMessage($sender, ["prefix", "schematic.save.invalidType" => $schematicName]);
                    break;
                }
                $worldSettings = WorldSettings::fromConfig();
                if ($schematicType === "road") {
                    $type = SchematicTypes::TYPE_ROAD;
                    $pos1 = new Vector3(0 + $worldSettings->getCoordinateOffset(), 0, 0 + $worldSettings->getCoordinateOffset());
                    $pos2 = new Vector3(($worldSettings->getRoadSize() + $worldSettings->getPlotSize()) + $worldSettings->getCoordinateOffset(), 0, ($worldSettings->getRoadSize() + $worldSettings->getPlotSize()) + $worldSettings->getCoordinateOffset());
                } else {
                    $type = SchematicTypes::TYPE_PLOT;
                    $pos1 = new Vector3(0 + $worldSettings->getCoordinateOffset(), 0, 0 + $worldSettings->getCoordinateOffset());
                    $pos2 = new Vector3($worldSettings->getPlotSize() + $worldSettings->getCoordinateOffset(), 0, $worldSettings->getPlotSize() + $worldSettings->getCoordinateOffset());
                }
                self::sendMessage($sender, ["prefix", "schematic.save.start" => $schematicName]);
                $world = $sender->getWorld();
                $task = new SchematicSaveAsyncTask($world, $pos1, $pos2, $file, $type, $worldSettings->getRoadSize(), $worldSettings->getPlotSize());
                $task->setCallback(
                    static function (SchematicSaveAsyncTask $task) use ($world, $sender, $schematicName, $schematicType) : void {
                        /** @phpstan-var array{0: int, 1: int, 2: string} $result */
                        $result = $task->getResult();
                        [$blocksCount, $fileSize, $fileSizeString] = $result;
                        $elapsedTimeString = $task->getElapsedTimeString();
                        Server::getInstance()->getLogger()->debug(
                            "Saving schematic from world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") \"" . $schematicName . "\" (" . $schematicType . ") with the size of " . $blocksCount . " blocks and a filesize of " . $fileSizeString . " (" . $fileSize . " B) took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ")."
                        );
                        self::sendMessage($sender, ["prefix", "schematic.save.finish" => [$schematicName, $elapsedTimeString]]);
                    },
                    null
                );
                Server::getInstance()->getAsyncPool()->submitTask($task);
                break;

            case "generate":
                if (!isset($args[1], $args[2])) {
                    self::sendMessage($sender, ["prefix", "schematic.generate.usage"]);
                    break;
                }
                if (Server::getInstance()->getWorldManager()->isWorldGenerated($args[1])) {
                    self::sendMessage($sender, ["prefix", "schematic.generate.worldExists" => $args[1]]);
                    break;
                }

                $worldSettings = WorldSettings::fromConfig()->toArray();
                if ($args[2] !== "road" && $args[2] !== "plot") {
                    $dir = CPlot::getInstance()->getDataFolder() . "schematics";
                    if (!is_dir($dir)) {
                        self::sendMessage($sender, ["prefix", "schematic.generate.directoryNotFound"]);
                        break;
                    }
                    $file = $dir . DIRECTORY_SEPARATOR . $args[2] . "." . Schematic::FILE_EXTENSION;
                    if (!file_exists($file)) {
                        self::sendMessage($sender, ["prefix", "schematic.generate.schematicNotFound" => $args[2]]);
                        break;
                    }
                    $schematic = new Schematic($file);
                    try {
                        $schematic->loadFromFile();
                    } catch (RuntimeException) {
                        self::sendMessage($sender, ["prefix", "schematic.generate.loadError" => $args[2]]);
                        break;
                    }
                    $worldSettings["schematicName"] = $schematic->getName();
                    $worldSettings["schematicType"] = $schematic->getType();
                } else if ($args[2] === "road") {
                    $worldSettings["schematicName"] = "default";
                    $worldSettings["schematicType"] = SchematicTypes::TYPE_ROAD;
                } else {
                    $worldSettings["schematicName"] = "default";
                    $worldSettings["schematicType"] = SchematicTypes::TYPE_PLOT;
                }
                $options = new WorldCreationOptions();
                $options->setGeneratorClass(SchematicGenerator::class);
                $worldSettings["worldName"] = $args[1];
                $options->setGeneratorOptions(json_encode($worldSettings, JSON_THROW_ON_ERROR));
                $options->setSpawnPosition(new Vector3(0, $worldSettings["groundSize"] + 1, 0));
                if (!Server::getInstance()->getWorldManager()->generateWorld($args[1], $options)) {
                    self::sendMessage($sender, ["prefix", "schematic.generate.generateError"]);
                    break;
                }
                self::sendMessage($sender, ["prefix", "schematic.generate.success" => $args[1]]);
                break;

            default:
                self::sendMessage($sender, ["prefix", "schematic.usage"]);
                break;
        }
    }
}