<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\BooleanFlag;
use ColinHDev\CPlotAPI\flags\FlagManager;
use ColinHDev\CPlotAPI\flags\implementations\SpawnFlag;
use ColinHDev\CPlotAPI\flags\StringFlag;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class FlagSubcommand extends Subcommand {

    /**
     * @param CommandSender $sender
     * @param array         $args
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
                foreach (FlagManager::getInstance()->getFlags() as $flag) {
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
                $sender->sendMessage($this->translateString("flag.info.valueType", [$flag->getValueType()]));
                $sender->sendMessage($this->translateString("flag.info.default", [$flag->serializeValueType($flag->getDefault())]));
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
                        return $this->translateString("flag.here.success.format", [$flag->getID(), $flag->serializeValueType($flag->getValueNonNull())]);
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

                $flag = $plot->getFlagNonNullByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noFlag", [$args[1]]));
                    break;
                }

                array_splice($args, 0, 2);
                if (!$flag->set($plot, $sender, $args)) return;
                if (!$this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.saveError"));
                    break;
                }
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

                $flag = $plot->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flagNotSet", [$args[1]]));
                    break;
                }

                array_splice($args, 0, 2);
                if (!$flag->remove($plot, $sender, $args)) return;
                if ($flag->getValue() === null) {
                    if ($this->getPlugin()->getProvider()->deletePlotFlag($plot, $flag->getID())) break;
                } else {
                    if ($this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.saveError"));
                break;
        }
    }
}