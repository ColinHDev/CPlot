<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use ColinHDev\CPlotAPI\utils\ParseUtils;
use dktapps\pmforms\FormIcon;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\Server;

class BorderSubcommand extends Subcommand {

    private MenuForm $form;
    /** @var array<int, Block> */
    private array $blocks = [];
    /** @var array<int, string> */
    private array $permissions = [];

    public function __construct(array $commandData, string $permission) {
        parent::__construct($commandData, $permission);

        $options = [];
        $permissionManager = PermissionManager::getInstance();
        $operatorRoot = $permissionManager->getPermission(DefaultPermissions::ROOT_OPERATOR);

        foreach (ResourceManager::getInstance()->getBordersConfig()->getAll() as $borderData) {
            $block = ParseUtils::parseBlockFromArray($borderData, "block");
            if ($block !== null) {
                $this->blocks[] = $block;

                $permission = new Permission($borderData["permission"]);
                $permissionManager->addPermission($permission);
                $operatorRoot->addChild($permission->getName(), true);
                $this->permissions[] = $permission->getName();

                $icon = match (strtolower($borderData["form"]["button.icon.type"])) {
                    "url" => new FormIcon($borderData["form"]["button.icon"], FormIcon::IMAGE_TYPE_URL),
                    "path" => new FormIcon($borderData["form"]["button.icon"], FormIcon::IMAGE_TYPE_PATH),
                    default => null,
                };
                $options[] = new MenuOption($borderData["form"]["button.text"], $icon);
            }
        }
        $this->form = new MenuForm(
            $this->translateString("border.form.title"),
            $this->translateString("border.form.text"),
            $options,
            \Closure::fromCallable([$this, "onFormSubmit"])
        );
    }

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("border.senderNotOnline"));
            return;
        }

        $sender->sendForm($this->form);
    }

    public function onFormSubmit(Player $player, int $selectedOption) : void {
        if (!$player->hasPermission($this->permissions[$selectedOption])) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.blockPermissionMessage"));
            return;
        }

        $worldSettings = $this->getPlugin()->getProvider()->getWorld($player->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($player->getPosition());
        if ($plot === null) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlot"));
            return;
        }
        if (!$player->hasPermission("cplot.admin.border")) {
            try {
                if (!$plot->hasPlotOwner()) {
                    $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlotOwner"));
                    return;
                } else if (!$plot->isPlotOwner($player->getUniqueId()->toString())) {
                    $player->sendMessage($this->getPrefix() . $this->translateString("border.notPlotOwner"));
                    return;
                }
            } catch (PlotException) {
                $player->sendMessage($this->getPrefix() . $this->translateString("border.loadPlotPlayersError"));
                return;
            }
        }

        try {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
            if ($flag->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("border.serverPlotFlag", [$flag->getID()]));
                return;
            }
        } catch (PlotException) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.loadFlagsError"));
            return;
        }

        try {
            $plot->loadMergePlots();
        } catch (PlotException) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.loadMergedPlotsError"));
            return;
        }

        $player->sendMessage($this->getPrefix() . $this->translateString("border.start"));
        $block = $this->blocks[$selectedOption];
        $task = new PlotBorderChangeAsyncTask($worldSettings, $plot, $block);
        $world = $player->getWorld();
        $task->setWorld($world);
        $task->setClosure(
            function (int $elapsedTime, string $elapsedTimeString, array $result) use ($world, $player, $block) {
                [$plotCount, $plots] = $result;
                $plots = array_map(
                    function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    $plots
                );
                Server::getInstance()->getLogger()->debug(
                    "Changing plot border to " . $block->getName() . " (ID:Meta: " . $block->getId() . ":" . $block->getMeta() . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $player->getUniqueId()->toString() . " (" . $player->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if ($player->isConnected()) {
                    $player->sendMessage($this->getPrefix() . $this->translateString("border.finish", [$elapsedTimeString, $block->getName()]));
                }
            }
        );
        $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);
    }
}