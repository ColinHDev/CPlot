<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\BorderChangeLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\WorldSettings;
use dktapps\pmforms\FormIcon;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

class BorderSubcommand extends Subcommand {

    private MenuForm $form;
    /** @var array<int, Block> */
    private array $blocks = [];
    /** @var array<int, string> */
    private array $permissions = [];

    public function __construct(string $key) {
        parent::__construct($key);

        $options = [];
        $permissionManager = PermissionManager::getInstance();
        $operatorRoot = $permissionManager->getPermission(DefaultPermissions::ROOT_OPERATOR);
        assert($operatorRoot instanceof Permission);

        /** @phpstan-var array{block: string, permission: string, form: array<string, string>} $borderData */
        foreach (ResourceManager::getInstance()->getBordersConfig()->getAll() as $borderData) {
            $block = ParseUtils::parseBlockFromString($borderData["block"]);
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
        $languageProvider = LanguageManager::getInstance()->getProvider();
        $this->form = new MenuForm(
            $languageProvider->translateString("border.form.title"),
            $languageProvider->translateString("border.form.text"),
            $options,
            function (Player $player, int $selectedOption) : void {
                Await::g2c($this->onFormSubmit($player, $selectedOption));
            }
        );
    }

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "border.senderNotOnline"]);
            return;
        }
        $sender->sendForm($this->form);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, mixed>
     */
    public function onFormSubmit(Player $player, int $selectedOption) : Generator {
        if (!$player->hasPermission($this->permissions[$selectedOption])) {
            self::sendMessage($player, ["prefix", "border.blockPermissionMessage"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($player->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            self::sendMessage($player, ["prefix", "border.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($player->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($player, ["prefix", "border.noPlot"]);
            return;
        }
        if (!$player->hasPermission("cplot.admin.border")) {
            if (!$plot->hasPlotOwner()) {
                self::sendMessage($player, ["prefix", "border.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($player)) {
                self::sendMessage($player, ["prefix", "border.notPlotOwner"]);
                return;
            }
        }

        $lock = new BorderChangeLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($player, ["prefix", "border.plotLocked"]);
            return;
        }

        self::sendMessage($player, ["prefix", "border.start"]);
        $block = $this->blocks[$selectedOption];
        /** @phpstan-var PlotBorderChangeAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->setBorderBlock($block, $resolve)
        );
        $world = $player->getWorld();
        $plotCount = count($plot->getMergePlots()) + 1;
        $plots = array_map(
            static function (BasePlot $plot) : string {
                return $plot->toSmallString();
            },
            array_merge([$plot], $plot->getMergePlots())
        );
        $elapsedTimeString = $task->getElapsedTimeString();
        Server::getInstance()->getLogger()->debug(
            "Changing plot border to " . $block->getName() . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $player->getUniqueId()->getBytes() . " (" . $player->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($player, ["prefix", "border.finish" => [$block->getName(), $elapsedTimeString]]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }
}