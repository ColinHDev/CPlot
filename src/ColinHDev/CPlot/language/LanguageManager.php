<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\language;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\lang\Language;
use pocketmine\utils\SingletonTrait;
use function pathinfo;
use function scandir;
use function strtolower;

final class LanguageManager {
    use SingletonTrait;

    private string $fallbackLanguage;
    /** @var array<string, Language> */
    private array $languages;

    public function __construct() {
        /** @phpstan-var array{fallback: string, aliases: array<string, string>} $languageSettings */
        $languageSettings = ResourceManager::getInstance()->getConfig()->get("language", []);
        $this->fallbackLanguage = strtolower($languageSettings["fallback"]);
        $languageAliases = $languageSettings["aliases"];

        $dir = scandir(CPlot::getInstance()->getDataFolder() . "language");
        if ($dir !== false) {
            foreach ($dir as $file) {
                /** @phpstan-var array{dirname: string, basename: string, extension?: string, filename: string} $fileData */
                $fileData = pathinfo($file);
                if (!isset($fileData["extension"]) || $fileData["extension"] !== "ini") {
                    continue;
                }
                $languageName = strtolower($fileData["filename"]);
                $language = new Language(
                    $languageName,
                    CPlot::getInstance()->getDataFolder() . "language",
                    $this->fallbackLanguage
                );
                $this->languages[$languageName] = $language;
                foreach ($languageAliases as $languageAlias => $alias) {
                    if (strtolower($alias) === $languageName) {
                        $this->languages[strtolower($languageAlias)] = $language;
                        unset($languageAliases[$languageAlias]);
                    }
                }
            }
        }
    }

    /**
     * Returns the default language.
     * @return Language The default language.
     */
    public function getDefaultLanguage() : Language {
        return $this->languages[$this->fallbackLanguage];
    }

    /**
     * Returns the language for the given identifier. If the language does not exist, the fallback language is returned.
     * @param string $language The language identifier.
     * @return Language The language matching the identifier or the default language.
     */
    public function getLanguage(string $language) : Language {
        return $this->languages[$language] ?? $this->getDefaultLanguage();
    }
}