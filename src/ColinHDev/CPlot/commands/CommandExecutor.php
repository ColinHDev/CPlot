<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands;

use ColinHDev\CPlot\language\LanguageManager;
use pocketmine\command\CommandSender;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;

class CommandExecutor {
    
    protected CommandSender $sender;
    protected Language $language;
    
    public function __construct(CommandSender $sender) {
        $this->sender = $sender;
        $this->language = LanguageManager::getInstance()->getLanguageForCommandSender($sender);
    }

    public function getSender() : CommandSender {
        return $this->sender;
    }

    public function getLanguage() : Language {
        return $this->language;
    }

    public function sendMessage(Translatable|string ...$messages) : void {
        $this->sender->sendMessage($this->translate(...$messages));
    }

    public function translate(Translatable|string ...$messages) : string {
        $result = "";
        foreach ($messages as $message) {
            if ($message instanceof Translatable) {
                $result .= $this->language->translate($message);
            } else {
                $result .= $message;
            }
        }
        return $result;
    }
}