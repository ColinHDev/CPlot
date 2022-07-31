<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

use ColinHDev\CPlot\player\PlayerData;
use pocketmine\player\Player;
use function is_string;
use function serialize;
use function unserialize;

/**
 * @phpstan-type PlayerID int
 * @phpstan-type PlayerUUID string
 * @phpstan-type PlayerXUID string
 * @phpstan-type PlayerName string
 */
final class PlotPlayerContainer {

    /** @phpstan-var array<PlayerID, PlotPlayer>  */
    private array $plotPlayers = [];

    /** @phpstan-var array<PlotPlayer::STATE_*, array<PlayerID, PlotPlayer>>  */
    private array $plotPlayersByState = [];
    /** @phpstan-var array<PlayerUUID, PlotPlayer> */
    private array $plotPlayersByUUID = [];
    /** @phpstan-var array<PlayerXUID, PlotPlayer> */
    private array $plotPlayersByXUID = [];
    /** @phpstan-var array<PlayerName, PlotPlayer> */
    private array $plotPlayersByName = [];

    /**
     * Returns all registered {@see PlotPlayer}s.
     * @phpstan-return array<PlayerID, PlotPlayer>
     */
    public function getPlotPlayers() : array {
        return $this->plotPlayers;
    }

    /**
     * Returns all registered {@see PlotPlayer}s with the given state.
     * @phpstan-param PlotPlayer::STATE_* $state
     * @phpstan-return array<PlayerID, PlotPlayer>
     */
    public function getPlotPlayersByState(string $state) : array {
        return $this->plotPlayersByState[$state] ?? [];
    }

    /**
     * Get the exact corresponding {@see PlotPlayer} to the given {@see Player}, {@see PlayerData} or PlayerID.
     *
     * @param Player|PlayerData|int $player The player information to get the {@see PlotPlayer} for.
     * @phpstan-param Player|PlayerData|PlayerID $player
     *
     * Returns the corresponding {@see PlotPlayer} or null if not found.
     * @return PlotPlayer|null
     */
    public function getPlotPlayerExact(Player|PlayerData|int $player) : ?PlotPlayer {
        if ($player instanceof Player) {
            if (isset($this->plotPlayersByUUID[$player->getUniqueId()->getBytes()])) {
                return $this->plotPlayersByUUID[$player->getUniqueId()->getBytes()];
            }
            if (isset($this->plotPlayersByXUID[$player->getXuid()])) {
                return $this->plotPlayersByXUID[$player->getXuid()];
            }
            return $this->plotPlayersByName[$player->getName()] ?? null;
        }
        if ($player instanceof PlayerData) {
            return $this->plotPlayers[$player->getPlayerID()] ?? null;
        }
        return $this->plotPlayers[$player] ?? null;
    }

    /**
     * Get the exact corresponding {@see PlotPlayer} to the given {@see Player}, {@see PlayerData} or PlayerID or the
     * asterisk (*) plot player if set.
     *
     * @param Player|PlayerData|int $player The player information to get the {@see PlotPlayer} for.
     * @phpstan-param Player|PlayerData|PlayerID $player
     *
     * Returns the corresponding {@see PlotPlayer}, the asterisk (*) plot player if set, or null.
     * @return PlotPlayer|null
     */
    public function getPlotPlayer(Player|PlayerData|int $player) : ?PlotPlayer {
        $plotPlayer = $this->getPlotPlayerExact($player);
        return $plotPlayer ?? $this->plotPlayersByUUID["*"] ?? null;
    }

    /**
     * Add a {@see PlotPlayer} to this container and therefore to its associated {@see Plot}.
     * @param PlotPlayer $plotPlayer The {@see PlotPlayer} to add.
     */
    public function addPlotPlayer(PlotPlayer $plotPlayer) : void {
        $playerData = $plotPlayer->getPlayerData();
        $playerID = $playerData->getPlayerID();
        $this->plotPlayers[$playerID] = $plotPlayer;

        $state = $plotPlayer->getState();
        if (!isset($this->plotPlayersByState[$state])) {
            $this->plotPlayersByState[$state] = [];
        }
        $this->plotPlayersByState[$state][$playerID] = $plotPlayer;

        $playerUUID = $playerData->getPlayerUUID();
        if (is_string($playerUUID)) {
            $this->plotPlayersByUUID[$playerUUID] = $plotPlayer;
        }
        $playerXUID = $playerData->getPlayerXUID();
        if (is_string($playerXUID)) {
            $this->plotPlayersByXUID[$playerXUID] = $plotPlayer;
        }
        $playerName = $playerData->getPlayerName();
        if (is_string($playerName)) {
            $this->plotPlayersByName[$playerName] = $plotPlayer;
        }
    }

    /**
     * Remove a {@see PlotPlayer} from this container and therefore from its associated {@see Plot}.
     * @param PlotPlayer $plotPlayer The {@see PlotPlayer} to remove.
     */
    public function removePlotPlayer(PlotPlayer $plotPlayer) : void {
        $playerData = $plotPlayer->getPlayerData();
        $playerID = $playerData->getPlayerID();
        unset($this->plotPlayers[$playerID]);

        $state = $plotPlayer->getState();
        unset($this->plotPlayersByState[$state][$playerID]);
        if (isset($this->plotPlayersByState[$state]) && count($this->plotPlayersByState[$state]) === 0) {
            unset($this->plotPlayersByState[$state]);
        }

        $playerUUID = $playerData->getPlayerUUID();
        if (is_string($playerUUID)) {
            unset($this->plotPlayersByUUID[$playerUUID]);
        }
        $playerXUID = $playerData->getPlayerXUID();
        if (is_string($playerXUID)) {
            unset($this->plotPlayersByXUID[$playerXUID]);
        }
        $playerName = $playerData->getPlayerName();
        if (is_string($playerName)) {
            unset($this->plotPlayersByName[$playerName]);
        }
    }

    /**
     * @phpstan-return array{plotPlayers: string}
     */
    public function __serialize() : array {
        return [
            "plotPlayers" => serialize($this->plotPlayers),
        ];
    }

    /**
     * @phpstan-param array{plotPlayers: string} $data
     */
    public function __unserialize(array $data) : void {
        /** @phpstan-var array<int, PlotPlayer> $plotPlayers */
        $plotPlayers = unserialize($data["plotPlayers"], ["allowed_classes" => false]);
        foreach($plotPlayers as $plotPlayer) {
            $this->addPlotPlayer($plotPlayer);
        }
    }
}