<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils\exception;

use Exception;
use pocketmine\event\Event;

class EventException extends Exception {

    private Event $event;

    public function __construct(Event $event, string $message) {
        parent::__construct($message);
        $this->event = $event;
    }

    public function getEvent() : Event {
        return $this->event;
    }
}