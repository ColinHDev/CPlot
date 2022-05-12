<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotBorderChangeAsyncEvent;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\NonWorldSettings;
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
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
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

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "border.senderNotOnline"]);
            return null;
        }

        $sender->sendForm($this->form);
        return null;
    }

    /**
     * @phpstan-return \Generator<mixed, mixed, WorldSettings|NonWorldSettings|Plot|null, void>
     */
    public function onFormSubmit(Player $player, int $selectedOption) : \Generator {
        if (!$player->hasPermission($this->permissions[$selectedOption])) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.blockPermissionMessage"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($player->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($player->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.noPlot"]);
            return;
        }
        if (!$player->hasPermission("cplot.admin.border")) {
            if (!$plot->hasPlotOwner()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($player)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.notPlotOwner"]);
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.serverPlotFlag" => $flag->getID()]);
            return;
        }

        /** @phpstan-var PlotBorderChangeAsyncEvent $event */
        $event = yield from PlotBorderChangeAsyncEvent::create($plot, $this->blocks[$selectedOption], $player);
        if ($event->isCancelled()) {
            return;
        }
        $block = $event->getBlock();

        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($player, ["prefix", "border.start"]);
        $world = $player->getWorld();
        $task = new PlotBorderChangeAsyncTask($world, $worldSettings, $plot, $block);
        $task->setCallback(
            static function (int $elapsedTime, string $elapsedTimeString, mixed $result) use ($world, $plot, $player, $block) : void {
                $plotCount = count($plot->getMergePlots()) + 1;
                $plots = array_map(
                    static function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    array_merge([$plot], $plot->getMergePlots())
                );
                Server::getInstance()->getLogger()->debug(
                    "Changing plot border to " . $block->getName() . " (ID:Meta: " . $block->getId() . ":" . $block->getMeta() . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $player->getUniqueId()->getBytes() . " (" . $player->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                LanguageManager::getInstance()->getProvider()->sendMessage($player, ["prefix", "border.finish" => [$elapsedTimeString, $block->getName()]]);
            }
        );
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }
}