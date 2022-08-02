<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\lock\WallChangeLockID;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotWallChangeAsyncTask;
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

class WallSubcommand extends Subcommand {

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

        /** @phpstan-var array{block: string, permission: string, form: array<string, string>} $wallData */
        foreach (ResourceManager::getInstance()->getWallsConfig()->getAll() as $wallData) {
            $block = ParseUtils::parseBlockFromString($wallData["block"]);
            if ($block !== null) {
                $this->blocks[] = $block;

                $permission = new Permission($wallData["permission"]);
                $permissionManager->addPermission($permission);
                $operatorRoot->addChild($permission->getName(), true);
                $this->permissions[] = $permission->getName();

                $icon = match (strtolower($wallData["form"]["button.icon.type"])) {
                    "url" => new FormIcon($wallData["form"]["button.icon"], FormIcon::IMAGE_TYPE_URL),
                    "path" => new FormIcon($wallData["form"]["button.icon"], FormIcon::IMAGE_TYPE_PATH),
                    default => null,
                };
                $options[] = new MenuOption($wallData["form"]["button.text"], $icon);
            }
        }
        $languageProvider = LanguageManager::getInstance()->getProvider();
        $this->form = new MenuForm(
            $languageProvider->translateString("wall.form.title"),
            $languageProvider->translateString("wall.form.text"),
            $options,
            function (Player $player, int $selectedOption) : void {
                Await::g2c($this->onFormSubmit($player, $selectedOption));
            }
        );
    }

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "wall.senderNotOnline"]);
            return;
        }
        $sender->sendForm($this->form);
    }

    /**
     * @phpstan-return \Generator<mixed, mixed, mixed, mixed>
     */
    public function onFormSubmit(Player $player, int $selectedOption) : \Generator {
        if (!$player->hasPermission($this->permissions[$selectedOption])) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.blockPermissionMessage"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($player->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($player->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.noPlot"]);
            return;
        }
        if (!$player->hasPermission("cplot.admin.wall")) {
            if (!$plot->hasPlotOwner()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($player)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.notPlotOwner"]);
                return;
            }
        }

        $lock = new WallChangeLockID();
        if (!PlotLockManager::getInstance()->lockPlotSilent($plot, $lock)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.plotLocked"]);
            return;
        }

        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "wall.start"]);
        $block = $this->blocks[$selectedOption];
        /** @phpstan-var PlotWallChangeAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->setWallBlock($block, $resolve)
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
            "Changing plot wall to " . $block->getName() . " (ID:Meta: " . $block->getId() . ":" . $block->getMeta() . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $player->getUniqueId()->getBytes() . " (" . $player->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        LanguageManager::getInstance()->getProvider()->sendMessage($player, ["prefix", "wall.finish" => [$elapsedTimeString, $block->getName()]]);
        PlotLockManager::getInstance()->unlockPlot($plot, $lock);
    }
}