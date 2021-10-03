<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\flags\ArrayFlag;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\flags\FlagManager;
use ColinHDev\CPlotAPI\flags\implementations\ServerPlotFlag;
use ColinHDev\CPlotAPI\flags\implementations\SpawnFlag;
use ColinHDev\CPlotAPI\flags\utils\FlagParseException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

class FlagSubcommand extends Subcommand {

    /**
     * @throws FlagParseException
     */
    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }

        switch ($args[0]) {
            case "list":
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.list.success"));
                $flagsByCategory = [];
                /** @var class-string<BaseFlag> $flagClass */
                foreach (FlagManager::getInstance()->getFlags() as $flagClass) {
                    $flag = new $flagClass;
                    if (!isset($flagsByCategory[$flag->getCategory()])) {
                        $flagsByCategory[$flag->getCategory()] = $flag->getID();
                    } else {
                        $flagsByCategory[$flag->getCategory()] .= $this->translateString("flag.list.success.separator") . $flag->getID();
                    }
                }
                foreach ($flagsByCategory as $category => $flags) {
                    $sender->sendMessage($this->translateString("flag.list.success.format", [$category, $flags]));
                }
                break;

            case "info":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.usage"));
                    break;
                }
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.noFlag", [$args[1]]));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.flag", [$flag->getID()]));
                $sender->sendMessage($this->translateString("flag.info.ID", [$flag->getID()]));
                $sender->sendMessage($this->translateString("flag.info.category", [$flag->getCategory()]));
                $sender->sendMessage($this->translateString("flag.info.description", [$flag->getDescription()]));
                $sender->sendMessage($this->translateString("flag.info.type", [$flag->getType()]));
                $sender->sendMessage($this->translateString("flag.info.default", [$flag->getDefault()]));
                break;

            case "here":
                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.senderNotOnline"));
                    break;
                }
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlot"));
                    break;
                }
                if (!$plot->loadFlags()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.loadFlagsError"));
                    break;
                }
                if (count($plot->getFlags()) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noFlags"));
                    break;
                }
                $flags = array_map(
                    function (BaseFlag $flag) : string {
                        return $this->translateString("flag.here.success.format", [$flag->getID(), $flag->toString()]);
                    },
                    $plot->getFlags()
                );
                $sender->sendMessage(
                    $this->getPrefix() .
                    $this->translateString(
                        "flag.here.success",
                        [implode($this->translateString("flag.here.success.separator"), $flags)]
                    )
                );
                break;

            case "set":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.senderNotOnline"));
                    break;
                }
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlot"));
                    break;
                }

                if ($plot->getOwnerUUID() === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotOwner"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                        break;
                    }
                }
                if (!$plot->loadFlags()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.loadFlagsError"));
                    break;
                }

                /** @var BaseFlag | null $flag */
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noFlag", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPermissionForFlag", [$flag->getID()]));
                    break;
                }

                if (!$flag instanceof ServerPlotFlag) {
                    $oldFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($oldFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.serverPlotFlag", [FlagIDs::FLAG_SERVER_PLOT]));
                        break;
                    }
                }

                if ($flag instanceof SpawnFlag) {
                    $location = $sender->getLocation();
                    $arg = $flag->toString(
                        Location::fromObject(
                            $location->subtractVector($plot->getPosition()),
                            $location->getWorld(),
                            $location->getYaw(),
                            $location->getPitch()
                        )
                    );
                } else {
                    array_splice($args, 0, 2);
                    $arg = implode(" ", $args);
                }
                try {
                    $parsedValue = $flag->parse($arg);
                } catch (FlagParseException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.parseError", [$arg, $flag->getID()]));
                    break;
                }

                $flag = $flag->flagOf($parsedValue);
                $oldFlag = $plot->getFlagByID($flag->getID());
                if ($oldFlag !== null) {
                    $flag = $flag->merge($oldFlag->getValue());
                }
                $plot->addFlag(
                    $flag
                );

                if (!$this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.saveError"));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.success", [$flag->getID(), $flag->toString($parsedValue)]));
                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.senderNotOnline"));
                    break;
                }
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlot"));
                    break;
                }

                if ($plot->getOwnerUUID() === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotOwner"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                        break;
                    }
                }
                if (!$plot->loadFlags()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.loadFlagsError"));
                    break;
                }

                /** @var BaseFlag | null $flag */
                $flag = $plot->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flagNotSet", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPermissionForFlag", [$flag->getID()]));
                    break;
                }

                if (!$flag instanceof ServerPlotFlag) {
                    $oldFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($oldFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.serverPlotFlag", [FlagIDs::FLAG_SERVER_PLOT]));
                        break;
                    }
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && $flag instanceof ArrayFlag) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $flag->parse($arg);
                    } catch (FlagParseException) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.parseError", [$arg, $flag->getID()]));
                        break;
                    }

                    $values = $flag->getValue();
                    foreach ($parsedValues as $parsedValue) {
                        $key = array_search($parsedValue, $values, true);
                        if ($key === false) {
                            continue;
                        }
                        unset($values[$key]);
                    }

                    if (count($values) > 0) {
                        $flag = $flag->flagOf($values);
                        if ($this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.value.success", [$flag->getID(), $flag->toString()]));
                            break;
                        }
                    } else {
                        if ($this->getPlugin()->getProvider()->deletePlotFlag($plot, $flag->getID())) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flag.success", [$flag->getID()]));
                            break;
                        }
                    }

                } else {
                    if ($this->getPlugin()->getProvider()->deletePlotFlag($plot, $flag->getID())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flag.success", [$flag->getID()]));
                        break;
                    }
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.saveError"));
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                break;
        }
    }
}