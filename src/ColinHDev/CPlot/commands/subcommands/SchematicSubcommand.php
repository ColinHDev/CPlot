<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\tasks\async\SchematicSaveAsyncTask;
use ColinHDev\CPlot\worlds\generators\SchematicGenerator;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\worlds\generators\Schematic;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\WorldCreationOptions;
use pocketmine\Server;

class SchematicSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }
        switch ($args[0]) {
            case "list":
                if (!is_dir($this->getPlugin()->getDataFolder() . "schematics")) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.list.directoryNotFound"));
                    break;
                }
                $files = [];
                foreach (scandir($this->getPlugin()->getDataFolder() . "schematics") as $file) {
                    $file = explode(".", $file);
                    if ($file === false) {
                        continue;
                    }
                    [$fileName, $fileExtension] = $file;
                    if ($fileExtension !== "cplot") {
                        continue;
                    }
                    $files[] = $fileName;
                }
                if (count($files) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.list.noSchematics"));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.list.success", [implode($this->translateString("schematic.list.successSeparator"), $files)]));
                break;

            case "info":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.info.usage"));
                    break;
                }
                $dir = $this->getPlugin()->getDataFolder() . "schematics";
                if (!is_dir($dir)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.info.directoryNotFound"));
                    break;
                }
                $file = $dir . DIRECTORY_SEPARATOR . $args[1] . ".cplot";
                if (!file_exists($file)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.info.schematicNotFound", [$args[1]]));
                    break;
                }
                $schematic = new Schematic($args[1], $file);
                if (!$schematic->loadFromFile()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.info.couldNotLoadSchematic", [$args[1]]));
                    break;
                }

                $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.info.success.head", [$args[1]]));
                if ($schematic->getType() === Schematic::TYPE_ROAD) {
                    $typeString = "schematic.info.success.typeRoad";
                } else if ($schematic->getType() === Schematic::TYPE_PLOT) {
                    $typeString = "schematic.info.success.typePlot";
                } else {
                    $typeString = "schematic.info.success.typeUnknown";
                }
                $sender->sendMessage(
                    $this->getPrefix() . $this->translateString(
                        "schematic.info.success.body",
                        [
                            $this->translateString("schematic.info.success.timeformat", explode(".", date("d.m.Y.H.i.s", $schematic->getCreationTime()))),
                            $this->translateString($typeString),
                            $schematic->getRoadSize(),
                            $schematic->getPlotSize()
                        ]
                    )
                );
                break;

            case "save":
                if (!isset($args[1], $args[2])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.usage"));
                    break;
                }
                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.senderNotOnline"));
                    break;
                }
                $schematicName = $args[1];
                $dir = $this->getPlugin()->getDataFolder() . "schematics";
                if (!is_dir($dir)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.directoryNotFound"));
                    break;
                }
                $file = $dir . DIRECTORY_SEPARATOR . $schematicName . Schematic::FILE_EXTENSION;
                if (file_exists($file)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.schematicAlreadyExists", [$schematicName]));
                    break;
                }
                $schematicType = strtolower($args[2]);
                if ($schematicType !== "road" && $schematicType !== "plot") {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.invalidType", [$schematicName]));
                    return;
                }
                $worldSettings = WorldSettings::fromConfig();
                if ($schematicType === "road") {
                    $type = Schematic::TYPE_ROAD;
                    $pos1 = new Vector3(0, 0, 0);
                    $pos2 = new Vector3(($worldSettings->getSizeRoad() + $worldSettings->getSizePlot()), 0, ($worldSettings->getSizeRoad() + $worldSettings->getSizePlot()));
                } else {
                    $type = Schematic::TYPE_PLOT;
                    $pos1 = new Vector3(0, 0, 0);
                    $pos2 = new Vector3($worldSettings->getSizePlot(), 0, $worldSettings->getSizePlot());
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.taskStart", [$schematicName]));
                $task = new SchematicSaveAsyncTask($schematicName, $file, $type, $worldSettings->getSizeRoad(), $worldSettings->getSizePlot());
                $task->setWorld($sender->getWorld());
                $task->saveChunks($sender->getWorld(), $pos1, $pos2);
                $task->setClosure(
                    function (int $elapsedTime, string $elapsedTimeString, array $result) use ($sender, $schematicName, $schematicType) {
                        [$blocksCount, $fileSize, $fileSizeString] = $result;
                        Server::getInstance()->getLogger()->debug(
                            "Saving schematic \"" . $schematicName . "\" (" . $schematicType . ") with the size of " . $blocksCount . " blocks and a filesize of " . $fileSizeString . " (" . $fileSize . " B) took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->toString() . " (" . $sender->getName() . ")."
                        );
                        if (!$sender->isConnected()) return;
                        $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.save.taskEnd", [$schematicName, $elapsedTimeString]));
                    }
                );
                $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);
                break;

            case "generate":
                if (!isset($args[1], $args[2])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.usage"));
                    break;
                }
                if ($this->getPlugin()->getServer()->getWorldManager()->isWorldGenerated($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.worldExists", [$args[1]]));
                    break;
                }

                $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
                if ($args[2] !== "road" && $args[2] !== "plot") {
                    $dir = $this->getPlugin()->getDataFolder() . "schematics";
                    if (!is_dir($dir)) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.directoryNotFound"));
                        break;
                    }
                    $file = $dir . DIRECTORY_SEPARATOR . $args[2] . ".cplot";
                    if (!file_exists($file)) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.schematicNotFound", [$args[2]]));
                        break;
                    }
                    $schematic = new Schematic($args[1], $file);
                    if (!$schematic->loadFromFile()) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.couldNotLoadSchematic", [$args[1]]));
                        break;
                    }
                    $settings["schematic"] = $schematic->getName();
                    $settings["schematicType"] = $schematic->getType();
                } else {
                    $settings["schematic"] = "default";
                    $settings["schematicType"] = $args[2];
                }
                $options = new WorldCreationOptions();
                $options = $options->setGeneratorClass(GeneratorManager::getInstance()->getGenerator(SchematicGenerator::GENERATOR_NAME));
                $options = $options->setGeneratorOptions(json_encode($settings));
                if (!$this->getPlugin()->getServer()->getWorldManager()->generateWorld($args[1], $options)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.generateError"));
                    break;
                }
                $world = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($args[1]);
                if ($world !== null) {
                    $world->setSpawnLocation(new Vector3(0, $settings["sizeGround"] + 1, 0));
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("schematic.generate.success", [$args[1]]));
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                break;
        }
    }
}