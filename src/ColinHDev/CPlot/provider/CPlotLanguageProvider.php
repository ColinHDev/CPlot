<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\command\CommandSender;
use pocketmine\lang\Language;
use pocketmine\player\Player;

/**
 * @phpstan-type LanguageIdentifier string
 * @phpstan-import-type MessageKey from LanguageProvider
 * @phpstan-import-type MessageParam from LanguageProvider
 */
class CPlotLanguageProvider extends LanguageProvider {

    /** @phpstan-var LanguageIdentifier */
    private string $fallbackLanguage;
    /** @phpstan-var array<LanguageIdentifier, Language> */
    private array $languages;

    public function __construct() {
        /** @phpstan-var string $fallbackLanguage */
        $fallbackLanguage = ResourceManager::getInstance()->getConfig()->get("fallback.language", "de_DE");
        $this->fallbackLanguage = $fallbackLanguage;
        $dir = scandir(CPlot::getInstance()->getDataFolder() . "language");
        if ($dir !== false) {
            foreach ($dir as $file) {
                /** @phpstan-var array{dirname: string, basename: string, extension?: string, filename: string} $fileData */
                $fileData = pathinfo($file);
                if (!isset($fileData["extension"]) || $fileData["extension"] !== "ini") {
                    continue;
                }
                $this->languages[$fileData["filename"]] = new Language(
                    $fileData["filename"],
                    CPlot::getInstance()->getDataFolder() . "language",
                    $this->fallbackLanguage
                );
            }
        }
    }

    public function translateString(array|string $keys) : string {
        return $this->buildMessage($this->languages[$this->fallbackLanguage], $keys);
    }

    public function translateForCommandSender(CommandSender $sender, array|string $keys, \Closure $onSuccess, ?\Closure $onError = null) : void {
        if (!($sender instanceof Player)) {
            $language = $this->languages[$this->fallbackLanguage];
        } else {
            $language = $this->languages[$sender->getLocale()] ?? $this->languages[$this->fallbackLanguage];
        }
        $onSuccess($this->buildMessage($language, $keys));
    }

    public function sendMessage(CommandSender $sender, array|string $keys, ?\Closure $onSuccess = null, ?\Closure $onError = null) : void {
        if (!($sender instanceof Player)) {
            $message = $this->translateString($keys);
        } else {
            if (!$sender->isOnline()) {
                return;
            }
            $message = $this->buildMessage($this->languages[$sender->getLocale()] ?? $this->languages[$this->fallbackLanguage], $keys);
        }
        $sender->sendMessage($message);
        if ($onSuccess !== null) {
            $onSuccess(null);
        }
    }

    public function sendTip(Player $player, array|string $keys, ?\Closure $onSuccess = null, ?\Closure $onError = null) : void {
        if (!$player->isOnline()) {
            return;
        }
        $player->sendTip(
            $this->buildMessage($this->languages[$player->getLocale()] ?? $this->languages[$this->fallbackLanguage], $keys)
        );
        if ($onSuccess !== null) {
            $onSuccess(null);
        }
    }

    /**
     * @phpstan-param array<int|MessageKey, MessageKey|MessageParam|array<MessageParam>>|MessageKey $keys
     */
    private function buildMessage(Language $language, array|string $keys) : string {
        if (is_string($keys)) {
            $keys = [$keys];
        }
        $message = "";
        foreach ($keys as $key => $value) {
            if (is_string($key)) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                $message .= $language->translateString($key, $value);
            } else {
                /** @phpstan-var string $value */
                $message .= $language->get($value);
            }
        }
        return $message;
    }
}