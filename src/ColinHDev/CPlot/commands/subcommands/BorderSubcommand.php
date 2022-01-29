<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\WorldSettings;
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
use SOFe\AwaitGenerator\Await;

/**
 * @phpstan-extends Subcommand<null>
 */
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
            function (Player $player, int $selectedOption) : void {
                Await::g2c($this->onFormSubmit($player, $selectedOption));
            }
        );
    }

    public function execute(CommandSender $sender, array $args) : \Generator {
        0 && yield;
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("border.senderNotOnline"));
            return;
        }

        $sender->sendForm($this->form);
    }

    public function onFormSubmit(Player $player, int $selectedOption) : \Generator {
        if (!$player->hasPermission($this->permissions[$selectedOption])) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.blockPermissionMessage"));
            return;
        }

        $worldSettings = yield from DataProvider::getInstance()->awaitWorld($player->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlotWorld"));
            return;
        }

        $plot = yield from Plot::awaitFromPosition($player->getPosition());
        if (!($plot instanceof Plot)) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlot"));
            return;
        }
        if (!$player->hasPermission("cplot.admin.border")) {
            if (!$plot->hasPlotOwner()) {
                $player->sendMessage($this->getPrefix() . $this->translateString("border.noPlotOwner"));
                return;
            }
            if (!$plot->isPlotOwner($player->getUniqueId()->getBytes())) {
                $player->sendMessage($this->getPrefix() . $this->translateString("border.notPlotOwner"));
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $player->sendMessage($this->getPrefix() . $this->translateString("border.serverPlotFlag", [$flag->getID()]));
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
                    static function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    $plots
                );
                Server::getInstance()->getLogger()->debug(
                    "Changing plot border to " . $block->getName() . " (ID:Meta: " . $block->getId() . ":" . $block->getMeta() . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $player->getUniqueId()->getBytes() . " (" . $player->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if ($player->isConnected()) {
                    $player->sendMessage($this->getPrefix() . $this->translateString("border.finish", [$elapsedTimeString, $block->getName()]));
                }
            }
        );
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }
}