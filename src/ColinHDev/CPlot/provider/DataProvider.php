<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingManager;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\MergePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\plots\PlotPlayerContainer;
use ColinHDev\CPlot\plots\PlotRate;
use ColinHDev\CPlot\provider\cache\Cache;
use ColinHDev\CPlot\provider\cache\CacheIDs;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;
use Webmozart\PathUtil\Path;
use function file_exists;
use function is_int;
use function is_string;
use function rename;
use function time;

/**
 * This is an @internal class for handling the storage of data of this plugin in a database.
 * This is NOT an API for other plugins.
 * @phpstan-type PlayerID int
 * @phpstan-type PlayerUUID string
 * @phpstan-type PlayerXUID string
 * @phpstan-type PlayerName string
 */
final class DataProvider {
    use SingletonTrait;

    private const INIT_FOREIGN_KEYS = "cplot.init.foreignKeys";
    private const INIT_PLAYERDATA_TABLE = "cplot.init.playerDataTable";
    private const INIT_ASTERISK_PLAYER = "cplot.init.asteriskPlayer";
    private const INIT_PLAYERSETTINGS_TABLE = "cplot.init.playerSettingsTable";
    private const INIT_WORLDS_TABLE = "cplot.init.worldsTable";
    private const INIT_PLOTALIASES_TABLE = "cplot.init.plotAliasesTable";
    private const INIT_MERGEPLOTS_TABLE = "cplot.init.mergePlotsTable";
    private const INIT_PLOTPLAYERS_TABLE = "cplot.init.plotPlayersTable";
    private const INIT_PLOTFLAGS_TABLE = "cplot.init.plotFlagsTable";
    private const INIT_PLOTRATES_TABLE = "cplot.init.plotRatesTable";

    private const GET_PLAYERDATA_BY_IDENTIFIER = "cplot.get.playerDataByIdentifier";
    private const GET_PLAYERDATA_BY_DATA = "cplot.get.playerDataByData";
    private const GET_PLAYERDATA_BY_UUID = "cplot.get.playerDataByUUID";
    private const GET_PLAYERDATA_BY_XUID = "cplot.get.playerDataByXUID";
    private const GET_PLAYERDATA_BY_NAME = "cplot.get.playerDataByName";
    private const GET_PLAYERSETTINGS = "cplot.get.playerSettings";
    private const GET_WORLD = "cplot.get.world";
    private const GET_PLOTALIASES = "cplot.get.plotAliases";
    private const GET_PLOT_BY_ALIAS = "cplot.get.plotByAlias";
    private const GET_ORIGINPLOT = "cplot.get.originPlot";
    private const GET_MERGEPLOTS = "cplot.get.mergePlots";
    private const GET_OWNED_PLOTS = "cplot.get.ownedPlots";
    private const GET_PLOTPLAYERS = "cplot.get.plotPlayers";
    private const GET_PLOTS_BY_PLOTPLAYER = "cplot.get.plotsByPlotPlayer";
    private const GET_PLOTFLAGS = "cplot.get.plotFlags";
    private const GET_PLOTRATES = "cplot.get.plotRates";

    private const SET_NEW_PLAYERDATA = "cplot.set.newPlayerData";
    private const SET_PLAYERDATA = "cplot.set.playerData";
    private const SET_PLAYERSETTING = "cplot.set.playerSetting";
    private const SET_WORLD = "cplot.set.world";
    // private const SET_PLOTALIAS = "cplot.set.plotAlias";
    private const SET_MERGEPLOT = "cplot.set.mergePlot";
    private const SET_PLOTPLAYER = "cplot.set.plotPlayer";
    private const SET_PLOTFLAG = "cplot.set.plotFlag";
    private const SET_PLOTRATE = "cplot.set.plotRate";

    private const DELETE_PLAYERSETTING = "cplot.delete.playerSetting";
    private const DELETE_PLOTALIASES = "cplot.delete.plotAliases";
    private const DELETE_MERGEPLOTS = "cplot.delete.mergePlots";
    private const DELETE_PLOTPLAYERS = "cplot.delete.plotPlayers";
    private const DELETE_PLOTPLAYER = "cplot.delete.plotPlayer";
    private const DELETE_PLOTFLAGS = "cplot.delete.plotFlags";
    private const DELETE_PLOTFLAG = "cplot.delete.plotFlag";
    private const DELETE_PLOTRATES = "cplot.delete.plotRates";

    private const EXPORT_MYPLOT_PLOTS = "myplot.get.Plots";
    private const EXPORT_MYPLOT_MERGES = "myplot.get.Merges";

    private DataConnector $database;
    private bool $isInitialized = false;

    /** @phpstan-var array{"cache_player": Cache<PlayerID, PlayerData>, "cache_player_uuid": Cache<PlayerUUID, PlayerID>, "cache_player_xuid": Cache<PlayerXUID, PlayerID>, "cache_player_name": Cache<PlayerName, PlayerID>, "cache_worldSetting": Cache<string, WorldSettings|false>, "cache_plot": Cache<string, Plot|MergePlot>} */
    private array $caches;

    /**
     * @throws SqlError
     */
    public function __construct() {
        $this->database = libasynql::create(CPlot::getInstance(), ResourceManager::getInstance()->getConfig()->get("database"), [
            "sqlite" => "sql" . DIRECTORY_SEPARATOR . "sqlite.sql",
            "mysql" => "sql" . DIRECTORY_SEPARATOR . "mysql.sql"
        ]);

        Await::g2c(
            $this->initializeDatabase()
        );

        /** @phpstan-var array{"cache_player": Cache<PlayerID, PlayerData>, "cache_player_uuid": Cache<PlayerUUID, PlayerID>, "cache_player_xuid": Cache<PlayerXUID, PlayerID>, "cache_player_name": Cache<PlayerName, PlayerID>, "cache_worldSetting": Cache<string, WorldSettings|false>, "cache_plot": Cache<string, Plot|MergePlot>} $caches */
        $caches = [
            CacheIDs::CACHE_PLAYER => new Cache(256),
            CacheIDs::CACHE_PLAYER_UUID => new Cache(256),
            CacheIDs::CACHE_PLAYER_XUID => new Cache(256),
            CacheIDs::CACHE_PLAYER_NAME => new Cache(256),
            CacheIDs::CACHE_WORLDSETTING => new Cache(64),
            CacheIDs::CACHE_PLOT => new Cache(256),
        ];
        $this->caches = $caches;
    }

    /**
     * Returns the {@see PlayerData} cache.
     * @phpstan-return Cache<PlayerID, PlayerData>
     */
    public function getPlayerCache(): Cache {
        return $this->caches[CacheIDs::CACHE_PLAYER];
    }

    /**
     * Returns the {@see WorldSettings} cache.
     * @phpstan-return Cache<string, WorldSettings|false>
     */
    public function getWorldSettingsCache(): Cache {
        return $this->caches[CacheIDs::CACHE_WORLDSETTING];
    }

    /**
     * Returns the {@see Plot} / {@see MergePlot} cache.
     * @phpstan-return Cache<string, Plot|MergePlot>
     */
    public function getPlotCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLOT];
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    private function initializeDatabase() : Generator {
        /** @phpstan-var (Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, null>)[] $generators */
        $generators = [
            $this->database->asyncGeneric(self::INIT_FOREIGN_KEYS),
            $this->database->asyncGeneric(self::INIT_PLAYERDATA_TABLE),
            $this->database->asyncGeneric(self::INIT_PLAYERSETTINGS_TABLE),
            $this->database->asyncGeneric(self::INIT_WORLDS_TABLE),
            $this->database->asyncGeneric(self::INIT_PLOTALIASES_TABLE),
            $this->database->asyncGeneric(self::INIT_MERGEPLOTS_TABLE),
            $this->database->asyncGeneric(self::INIT_PLOTPLAYERS_TABLE),
            $this->database->asyncGeneric(self::INIT_PLOTFLAGS_TABLE),
            $this->database->asyncGeneric(self::INIT_PLOTRATES_TABLE)
        ];
        yield from Await::all($generators);
        yield from $this->database->asyncGeneric(self::INIT_ASTERISK_PLAYER, ["lastJoin" => date("d.m.Y H:i:s")]);
        $this->isInitialized = true;

        if(ResourceManager::getInstance()->getConfig()->getNested("database.import", false) === true) {
            CPlot::getInstance()->getScheduler()->scheduleTask(new ClosureTask(\Closure::fromCallable(fn() => Await::f2c(\Closure::fromCallable([$this, "importData"]))))); // 1 tick delay to ensure worlds are all loaded
        }
    }

    public function isInitialized() : bool {
        return $this->isInitialized;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its UUID asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByPlayer(Player $player) : Generator {
        /** @phpstan-var PlayerData|null $playerData */
        $playerData = yield from $this->awaitPlayerDataByData($player->getUniqueId()->getBytes(), $player->getXuid(), $player->getName());
        return $playerData;
    }

    /**
     * Fetches the {@see PlayerData} of a player by either its UUID, XUID or name asynchronously from the database (or
     * synchronously from the cache if contained) and returns a {@see \Generator}. It can be gotten by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByData(?string $playerUUID, ?string $playerXUID, ?string $playerName) : Generator {
        $playerUUID = ($playerUUID === "" ? null : $playerUUID);
        $playerXUID = ($playerXUID === "" ? null : $playerXUID);
        $playerName = ($playerName === "" ? null : $playerName);
        $cachedPlayerID = null;
        if (is_string($playerUUID)) {
            $cachedPlayerID = $this->caches[CacheIDs::CACHE_PLAYER_UUID]->getObjectFromCache($playerUUID);
        }
        if (is_string($playerXUID) && !is_int($cachedPlayerID)) {
            $cachedPlayerID = $this->caches[CacheIDs::CACHE_PLAYER_XUID]->getObjectFromCache($playerXUID);
        }
        if (is_string($playerName) && !is_int($cachedPlayerID)) {
            $cachedPlayerID = $this->caches[CacheIDs::CACHE_PLAYER_NAME]->getObjectFromCache($playerName);
        }

        if (is_int($cachedPlayerID)) {
            $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($cachedPlayerID);
            if ($playerData instanceof PlayerData) {
                return $playerData;
            }
        }

        /** @phpstan-var array<array{playerID: int, playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: string}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_DATA,
            ["playerUUID" => $playerUUID, "playerXUID" => $playerXUID, "playerName" => $playerName]
        );
        /** @phpstan-var array{playerID: int, playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: string}|null $playerData */
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerID = $playerData["playerID"];
        $playerUUID ??= $playerData["playerUUID"];
        $playerXUID ??= $playerData["playerXUID"];
        $playerName ??= $playerData["playerName"];
        $lastJoin = \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"]);
        $playerData = new PlayerData(
            $playerID,
            $playerUUID, $playerXUID, $playerName,
            $lastJoin instanceof \DateTime ? $lastJoin->getTimestamp() : time(),
            (yield from $this->awaitPlayerSettings($playerID))
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
        if (is_string($playerUUID)) {
            $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        }
        if (is_string($playerXUID)) {
            $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        }
        if (is_string($playerName)) {
            $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
        }
        return $playerData;
    }

    /**
     * Fetches and returns the {@see PlayerData} of a player by its UUID synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called. By providing a callback, the player data can be
     * worked with once it was successfully loaded from the database.
     * @phpstan-param null|\Closure(PlayerData|null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    public function getPlayerDataByUUID(string $playerUUID, ?\Closure $onSuccess = null, ?\Closure $onError = null) : ?PlayerData {
        $playerID = $this->caches[CacheIDs::CACHE_PLAYER_UUID]->getObjectFromCache($playerUUID);
        if (is_int($playerID)) {
            $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($playerID);
            if ($playerData instanceof PlayerData) {
                if ($onSuccess !== null) {
                    $onSuccess($playerData);
                }
                return $playerData;
            }
        }
        Await::g2c(
            $this->awaitPlayerDataByUUID($playerUUID),
            $onSuccess,
            $onError ?? []
        );
        return null;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its identifier asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByID(int $playerID) : Generator {
        $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($playerID);
        if ($playerData instanceof PlayerData) {
            return $playerData;
        }
        /** @phpstan-var array{0?: array{playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: string}} $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_IDENTIFIER,
            ["playerID" => $playerID]
        );
        /** @phpstan-var array{playerUUID: string|null, playerXUID: string|null, playerName: string|null, lastJoin: string}|null $playerData */
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerUUID = $playerData["playerUUID"];
        $playerXUID = $playerData["playerXUID"];
        $playerName = $playerData["playerName"];
        $lastJoin = \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"]);
        $playerData = new PlayerData(
            $playerID,
            $playerUUID, $playerXUID, $playerName,
            $lastJoin instanceof \DateTime ? $lastJoin->getTimestamp() : time(),
            (yield from $this->awaitPlayerSettings($playerID))
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
        if ($playerUUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        }
        if ($playerXUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        }
        if ($playerName !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
        }
        return $playerData;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its UUID asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByUUID(string $playerUUID) : Generator {
        $playerID = $this->caches[CacheIDs::CACHE_PLAYER_UUID]->getObjectFromCache($playerUUID);
        if (is_int($playerID)) {
            $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($playerID);
            if ($playerData instanceof PlayerData) {
                return $playerData;
            }
        }
        /** @phpstan-var array{0?: array{playerID: int, playerXUID: string|null, playerName: string|null, lastJoin: string}} $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_UUID,
            ["playerUUID" => $playerUUID]
        );
        /** @phpstan-var array{playerID: int, playerXUID: string|null, playerName: string|null, lastJoin: string}|null $playerData */
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerID = $playerData["playerID"];
        $playerXUID = $playerData["playerXUID"];
        $playerName = $playerData["playerName"];
        $lastJoin = \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"]);
        $playerData = new PlayerData(
            $playerID,
            $playerUUID, $playerXUID, $playerName,
            $lastJoin instanceof \DateTime ? $lastJoin->getTimestamp() : time(),
            (yield from $this->awaitPlayerSettings($playerID))
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
        $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        if ($playerXUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        }
        if ($playerName !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
        }
        return $playerData;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its XUID asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByXUID(string $playerXUID) : Generator {
        $playerID = $this->caches[CacheIDs::CACHE_PLAYER_XUID]->getObjectFromCache($playerXUID);
        if (is_int($playerID)) {
            $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($playerID);
            if ($playerData instanceof PlayerData) {
                return $playerData;
            }
        }
        /** @phpstan-var array{0?: array{playerID: int, playerUUID: string|null, playerName: string|null, lastJoin: string}} $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_XUID,
            ["playerXUID" => $playerXUID]
        );
        /** @phpstan-var array{playerID: int, playerUUID: string|null, playerName: string|null, lastJoin: string}|null $playerData */
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerID = $playerData["playerID"];
        $playerUUID = $playerData["playerUUID"];
        $playerName = $playerData["playerName"];
        $lastJoin = \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"]);
        $playerData = new PlayerData(
            $playerID,
            $playerUUID, $playerXUID, $playerName,
            $lastJoin instanceof \DateTime ? $lastJoin->getTimestamp() : time(),
            (yield from $this->awaitPlayerSettings($playerID))
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
        if ($playerUUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        }
        $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        if ($playerName !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
        }
        return $playerData;
    }

    /**
     * Fetches the {@see PlayerData} of a player by its name asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlayerData|null>
     */
    public function awaitPlayerDataByName(string $playerName) : Generator {
        $playerID = $this->caches[CacheIDs::CACHE_PLAYER_NAME]->getObjectFromCache($playerName);
        if (is_int($playerID)) {
            $playerData = $this->caches[CacheIDs::CACHE_PLAYER]->getObjectFromCache($playerID);
            if ($playerData instanceof PlayerData) {
                return $playerData;
            }
        }
        /** @phpstan-var array{0?: array{playerID: int, playerUUID: string|null, playerXUID: string|null, lastJoin: string}} $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERDATA_BY_NAME,
            ["playerName" => $playerName]
        );
        /** @phpstan-var array{playerID: int, playerUUID: string|null, playerXUID: string|null, lastJoin: string}|null $playerData */
        $playerData = $rows[array_key_first($rows)] ?? null;
        if ($playerData === null) {
            return null;
        }
        $playerID = $playerData["playerID"];
        $playerUUID = $playerData["playerUUID"];
        $playerXUID = $playerData["playerXUID"];
        $lastJoin = \DateTime::createFromFormat("d.m.Y H:i:s", $playerData["lastJoin"]);
        $playerData = new PlayerData(
            $playerID,
            $playerUUID, $playerXUID, $playerName,
            $lastJoin instanceof \DateTime ? $lastJoin->getTimestamp() : time(),
            (yield from $this->awaitPlayerSettings($playerID))
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
        if ($playerUUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        }
        if ($playerXUID !== null) {
            $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        }
        $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
        return $playerData;
    }

    /**
     * Fetches the {@see Setting}s of a player asynchronously from the database and returns a {@see \Generator}. The
     * player settings can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, array<string, Setting<mixed>>>
     */
    private function awaitPlayerSettings(int $playerID) : Generator {
        /** @phpstan-var array<array{ID: string, value: string}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLAYERSETTINGS,
            ["playerID" => $playerID]
        );
        $settings = [];
        /** @phpstan-var array{ID: string, value: string} $row */
        foreach ($rows as $row) {
            $setting = SettingManager::getInstance()->getSettingByID($row["ID"]);
            if ($setting === null) {
                continue;
            }
            try {
                $settings[$setting->getID()] = $setting->createInstance($setting->parse($row["value"]));
            } catch (AttributeParseException) {
            }
        }
        return $settings;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function updatePlayerData(?string $playerUUID, ?string $playerXUID, ?string $playerName) : Generator {
        $playerUUID = ($playerUUID === "" ? null : $playerUUID);
        $playerXUID = ($playerXUID === "" ? null : $playerXUID);
        $playerName = ($playerName === "" ? null : $playerName);
        $playerData = yield $this->awaitPlayerDataByData($playerUUID, $playerXUID, $playerName);
        if (!($playerData instanceof PlayerData)) {
            yield $this->database->asyncInsert(
                self::SET_NEW_PLAYERDATA,
                [
                    "playerUUID" => $playerUUID,
                    "playerXUID" => $playerXUID,
                    "playerName" => $playerName,
                    "lastJoin" => date("d.m.Y H:i:s")
                ]
            );
            return;
        }
        $playerID = $playerData->getPlayerID();
        yield $this->database->asyncInsert(
            self::SET_PLAYERDATA,
            [
                "playerID" => $playerID,
                "playerUUID" => $playerUUID,
                "playerXUID" => $playerXUID,
                "playerName" => $playerName,
                "lastJoin" => date("d.m.Y H:i:s")
            ]
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject(
            $playerID,
            new PlayerData($playerID, $playerUUID, $playerXUID, $playerName, time(), $playerData->getSettings())
        );
        $this->caches[CacheIDs::CACHE_PLAYER_UUID]->cacheObject($playerUUID, $playerID);
        $this->caches[CacheIDs::CACHE_PLAYER_XUID]->cacheObject($playerXUID, $playerID);
        $this->caches[CacheIDs::CACHE_PLAYER_NAME]->cacheObject($playerName, $playerID);
    }

    /**
     * @phpstan-template TAttributeValue
     * @phpstan-param Setting<TAttributeValue> $setting
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function savePlayerSetting(PlayerData $playerData, Setting $setting) : Generator {
        $playerID = $playerData->getPlayerID();
        yield $this->database->asyncInsert(
            self::SET_PLAYERSETTING,
            [
                "playerID" => $playerID,
                "ID" => $setting->getID(),
                "value" => $setting->toString()
            ]
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function deletePlayerSetting(PlayerData $playerData, string $settingID) : Generator {
        $playerID = $playerData->getPlayerID();
        yield $this->database->asyncInsert(
            self::DELETE_PLAYERSETTING,
            [
                "playerID" => $playerID,
                "ID" => $settingID
            ]
        );
        $this->caches[CacheIDs::CACHE_PLAYER]->cacheObject($playerID, $playerData);
    }

    /**
     * Fetches and returns the {@see WorldSettings} or false of a world by its name synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called. By providing a callback, the result can be
     * worked with once it was successfully loaded from the database.
     * @phpstan-param null|\Closure(WorldSettings|false): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    public function getOrLoadWorldSettings(string $worldName, ?\Closure $onSuccess = null, ?\Closure $onError = null) : WorldSettings|false|null {
        $worldSettings = $this->caches[CacheIDs::CACHE_WORLDSETTING]->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings || $worldSettings === false) {
            if ($onSuccess !== null) {
                $onSuccess($worldSettings);
            }
            return $worldSettings;
        }
        Await::g2c(
            $this->awaitWorld($worldName),
            $onSuccess,
            $onError ?? []
        );
        return null;
    }

    /**
     * Fetches and returns the {@see WorldSettings} of a world synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadWorldIntoCache(string $worldName) : WorldSettings|false|null {
        $worldSettings = $this->caches[CacheIDs::CACHE_WORLDSETTING]->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings || $worldSettings === false) {
            return $worldSettings;
        }
        Await::g2c(
            $this->awaitWorld($worldName),
        );
        return null;
    }

    /**
     * Fetches the {@see WorldSettings} of a world asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, WorldSettings|false>
     */
    public function awaitWorld(string $worldName) : Generator {
        $worldSettings = $this->caches[CacheIDs::CACHE_WORLDSETTING]->getObjectFromCache($worldName);
        if ($worldSettings instanceof WorldSettings || $worldSettings === false) {
            return $worldSettings;
        }
        /** @phpstan-var array{0?: array{worldType?: string, roadSchematic?: string, mergeRoadSchematic?: string, plotSchematic?: string, roadSize?: int, plotSize?: int, groundSize?: int, roadBlock?: string, borderBlock?: string, borderBlockOnClaim?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string}} $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_WORLD,
            ["worldName" => $worldName]
        );
        /** @phpstan-var null|array{worldType?: string, roadSchematic?: string, mergeRoadSchematic?: string, plotSchematic?: string, roadSize?: int, plotSize?: int, groundSize?: int, roadBlock?: string, borderBlock?: string, borderBlockOnClaim?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $worldData */
        $worldData = $rows[array_key_first($rows)] ?? null;
        if ($worldData === null) {
            $worldSettings = false;
        } else {
            $worldSettings = WorldSettings::fromArray($worldData);
        }
        $this->caches[CacheIDs::CACHE_WORLDSETTING]->cacheObject($worldName, $worldSettings);
        return $worldSettings;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function addWorld(string $worldName, WorldSettings $worldSettings) : Generator {
        yield $this->database->asyncInsert(
            self::SET_WORLD,
            [
                "worldName" => $worldName,
                "worldType" => $worldSettings->getWorldType(),
                "biomeID" => $worldSettings->getBiomeID(),
                "roadSchematic" => $worldSettings->getRoadSchematic(),
                "mergeRoadSchematic" => $worldSettings->getMergeRoadSchematic(),
                "plotSchematic" => $worldSettings->getPlotSchematic(),
                "roadSize" => $worldSettings->getRoadSize(),
                "plotSize" => $worldSettings->getPlotSize(),
                "groundSize" => $worldSettings->getGroundSize(),
                "coordinateOffset" => $worldSettings->getCoordinateOffset(),
                "roadBlock" => ParseUtils::parseStringFromBlock($worldSettings->getRoadBlock()),
                "borderBlock" => ParseUtils::parseStringFromBlock($worldSettings->getBorderBlock()),
                "plotFloorBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotFloorBlock()),
                "plotFillBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotFillBlock()),
                "plotBottomBlock" => ParseUtils::parseStringFromBlock($worldSettings->getPlotBottomBlock())
            ]
        );
        $this->caches[CacheIDs::CACHE_WORLDSETTING]->cacheObject($worldName, $worldSettings);
    }

    /**
     * Fetches and returns the {@see WorldSettings} or false of a world by its name synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called. By providing a callback, the result can be
     * worked with once it was successfully loaded from the database.
     * @phpstan-param null|\Closure(Plot|null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    public function getOrLoadPlot(string $worldName, int $x, int $z, ?\Closure $onSuccess = null, ?\Closure $onError = null) : ?Plot {
        $plot = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            $return = $plot instanceof Plot ? $plot : null;
            if ($onSuccess !== null) {
                $onSuccess($return);
            }
            return $return;
        }
        Await::g2c(
            $this->awaitPlot($worldName, $x, $z),
            $onSuccess,
            $onError ?? []
        );
        return null;
    }

    /**
     * Fetches and returns a {@see Plot} synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadPlotIntoCache(string $worldName, int $x, int $z) : ?Plot {
        $plot = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            if ($plot instanceof Plot) {
                return $plot;
            }
            return null;
        }
        Await::g2c(
            $this->awaitPlot($worldName, $x, $z),
        );
        return null;
    }

    /**
     * Fetches a {@see Plot} asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get by
     * using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, Plot|null>
     */
    public function awaitPlot(string $worldName, int $x, int $z) : Generator {
        $plot = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($worldName . ";" . $x . ";" . $z);
        if ($plot instanceof BasePlot) {
            if ($plot instanceof Plot) {
                return $plot;
            }
            return null;
        }
        /** @phpstan-var WorldSettings|false $worldSettings */
        $worldSettings = yield $this->awaitWorld($worldName);
        assert($worldSettings instanceof WorldSettings);
        /** @phpstan-var string|null $plotAliases */
        $plotAliases = yield $this->awaitPlotAliases($worldName, $x, $z);
        /** @phpstan-var array<string, MergePlot> $mergePlots */
        $mergePlots = yield $this->awaitMergePlots($worldName, $worldSettings, $x, $z);
        /** @phpstan-var PlotPlayerContainer $plotPlayerContainer */
        $plotPlayerContainer = yield $this->awaitPlotPlayers($worldName, $x, $z);
        /** @phpstan-var array<string, Flag<mixed>> $plotFlags */
        $plotFlags = yield $this->awaitPlotFlags($worldName, $x, $z);
        /** @phpstan-var array<string, PlotRate> $plotRates */
        $plotRates = yield $this->awaitPlotRates($worldName, $x, $z);
        $plot = new Plot(
            $worldName, $worldSettings, $x, $z,
            $plotAliases,
            $mergePlots, $plotPlayerContainer, $plotFlags, $plotRates
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
        return $plot;
    }

    /**
     * Fetches the aliases of a plot asynchronously from the database and returns a {@see \Generator}. They can be get
     * by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, string|null>
     */
    private function awaitPlotAliases(string $worldName, int $x, int $z) : Generator {
        /** @phpstan-var array<array{alias: string}> $rows */
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTALIASES,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        /** @phpstan-var array{alias: string} $row */
        foreach ($rows as $row) {
            return $row["alias"];
        }
        return null;
    }

    /**
     * Fetches the {@see MergePlot}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * merge plots can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, array<string, MergePlot>>
     */
    private function awaitMergePlots(string $worldName, WorldSettings $worldSettings, int $x, int $z) : Generator {
        /** @phpstan-var array<array{mergeX: int, mergeZ: int}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_MERGEPLOTS,
            [
                "worldName" => $worldName,
                "originX" => $x,
                "originZ" => $z
            ]
        );
        $mergePlots = [];
        /** @phpstan-var array{mergeX: int, mergeZ: int} $row */
        foreach ($rows as $row) {
            $mergePlot = new MergePlot($worldName, $worldSettings, $row["mergeX"], $row["mergeZ"], $x, $z);
            $mergePlotKey = $mergePlot->toString();
            $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($mergePlotKey, $mergePlot);
            $mergePlots[$mergePlotKey] = $mergePlot;
        }
        return $mergePlots;
    }

    /**
     * Fetches the {@see PlotPlayer}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot players can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, PlotPlayerContainer>
     */
    private function awaitPlotPlayers(string $worldName, int $x, int $z) : Generator {
        /** @phpstan-var array<array{playerID: int, state: string, addTime: string}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLOTPLAYERS,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotPlayerContainer = new PlotPlayerContainer();
        /** @phpstan-var array{playerID: int, state: string, addTime: string} $row */
        foreach ($rows as $row) {
            /** @phpstan-var string $state */
            $state = $row["state"];
            if (!isset(PlotPlayer::STATES[$state])) {
                continue;
            }
            /** @phpstan-var PlotPlayer::STATE_* $state */
            /** @phpstan-var PlayerData $playerData */
            $playerData = yield $this->awaitPlayerDataByID($row["playerID"]);
            $addTime = \DateTime::createFromFormat("d.m.Y H:i:s", $row["addTime"]);
            $plotPlayerContainer->addPlotPlayer(new PlotPlayer(
                $playerData,
                $state,
                $addTime instanceof \DateTime ? $addTime->getTimestamp() : time()
            ));
        }
        return $plotPlayerContainer;
    }

    /**
     * Fetches the {@see Flag}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot flags can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, array<string, Flag<mixed>>>
     */
    private function awaitPlotFlags(string $worldName, int $x, int $z) : Generator {
        /** @phpstan-var array<array{flag: string, value: string}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLOTFLAGS,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotFlags = [];
        /** @phpstan-var array{ID: string, value: string} $row */
        foreach ($rows as $row) {
            $plotFlag = FlagManager::getInstance()->getFlagByID($row["ID"]);
            if ($plotFlag === null) {
                continue;
            }
            try {
                $plotFlags[$plotFlag->getID()] = $plotFlag->createInstance($plotFlag->parse($row["value"]));
            } catch (AttributeParseException) {
            }
        }
        return $plotFlags;
    }

    /**
     * Fetches the {@see PlotRate}s of a plot asynchronously from the database and returns a {@see \Generator}. The
     * plot rates can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, array<string, PlotRate>>
     */
    private function awaitPlotRates(string $worldName, int $x, int $z) : Generator {
        /** @phpstan-var array<array{rate: string, playerID: int, rateTime: string, comment: string|null}> $rows */
        $rows = yield from $this->database->asyncSelect(
            self::GET_PLOTRATES,
            [
                "worldName" => $worldName,
                "x" => $x,
                "z" => $z
            ]
        );
        $plotRates = [];
        /** @phpstan-var array{rate: string, playerID: int, rateTime: string, comment: string|null} $row */
        foreach ($rows as $row) {
            /** @phpstan-var PlayerData $playerData */
            $playerData = yield $this->awaitPlayerDataByID($row["playerID"]);
            $rateTime = \DateTime::createFromFormat("d.m.Y H:i:s", $row["rateTime"]);
            $plotRate = new PlotRate(
                $row["rate"],
                $playerData,
                $rateTime instanceof \DateTime ? $rateTime->getTimestamp() : time(),
                $row["comment"] ?? null
            );
            $plotRates[$plotRate->toString()] = $plotRate;
        }
        return $plotRates;
    }

    /**
     * Fetches a {@see Plot} by its alias asynchronously from the database and returns a {@see \Generator}. It can be get
     * by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, Plot|null>
     */
    public function awaitPlotByAlias(string $alias) : Generator {
        /** @phpstan-var array<array<string, mixed>> $rows */
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOT_BY_ALIAS,
            ["alias" => $alias]
        );
        /** @phpstan-var null|array{worldName: string, x: int, z: int} $plotData */
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            return null;
        }
        /** @phpstan-var Plot|null $plot */
        $plot = yield $this->awaitPlot($plotData["worldName"], $plotData["x"], $plotData["z"]);
        return $plot;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function awaitPlotDeletion(Plot $plot) : Generator {
        yield $this->awaitPlotAliasesDeletion($plot);
        yield $this->awaitMergePlotsDeletion($plot);
        yield $this->awaitPlotPlayersDeletion($plot);
        yield $this->awaitPlotFlagsDeletion($plot);
        yield $this->awaitPlotRatesDeletion($plot);
        $this->caches[CacheIDs::CACHE_PLOT]->removeObjectFromCache($plot->toString());
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    private function awaitPlotAliasesDeletion(Plot $plot) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTALIASES,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function awaitMergePlotsDeletion(Plot $plot) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_MERGEPLOTS,
            [
                "worldName" => $plot->getWorldName(),
                "originX" => $plot->getX(),
                "originZ" => $plot->getZ()
            ]
        );
        foreach ($plot->getMergePlots() as $key => $mergePlot) {
            $this->caches[CacheIDs::CACHE_PLOT]->removeObjectFromCache($key);
        }
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function awaitPlotPlayersDeletion(Plot $plot) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTPLAYERS,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function awaitPlotFlagsDeletion(Plot $plot) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTFLAGS,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function awaitPlotRatesDeletion(Plot $plot) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTRATES,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ()
            ]
        );
    }

    /**
     * Fetches and returns the {@see WorldSettings} or false of a world by its name synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called. By providing a callback, the result can be
     * worked with once it was successfully loaded from the database.
     * @phpstan-param null|\Closure(Plot|null): void $onSuccess
     * @phpstan-param null|\Closure(\Throwable): void $onError
     */
    public function getOrLoadMergeOrigin(BasePlot $plot, ?\Closure $onSuccess = null, ?\Closure $onError = null) : ?Plot {
        if ($plot instanceof Plot) {
            if ($onSuccess !== null) {
                $onSuccess($plot);
            }
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            return $this->getOrLoadPlot(
                $plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ(),
                $onSuccess, $onError
            );
        }
        $plotInCache = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($plot->toString());
        if ($plotInCache instanceof Plot) {
            if ($onSuccess !== null) {
                $onSuccess($plotInCache);
            }
            return $plotInCache;
        }
        if ($plotInCache instanceof MergePlot) {
            return $this->getOrLoadPlot(
                $plotInCache->getWorldName(), $plotInCache->getOriginX(), $plotInCache->getOriginZ(),
                $onSuccess, $onError
            );
        }
        Await::g2c(
            $this->awaitMergeOrigin($plot),
            $onSuccess,
            $onError ?? []
        );
        return null;
    }

    /**
     * Fetches and returns the origin plot ({@see Plot}) of another plot synchronously from the cache.
     * If the cache does not contain it, it is loaded asynchronously from the database into the cache, so it
     * is synchronously available the next time this method is called.
     */
    public function loadMergeOriginIntoCache(BasePlot $plot) : ?Plot {
        if ($plot instanceof Plot) {
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            return $this->loadPlotIntoCache($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
        }
        $plotInCache = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($plot->toString());
        if ($plotInCache instanceof Plot) {
            return $plotInCache;
        }
        if ($plotInCache instanceof MergePlot) {
            return $this->loadPlotIntoCache($plotInCache->getWorldName(), $plotInCache->getOriginX(), $plotInCache->getOriginZ());
        }
        Await::g2c(
            $this->awaitMergeOrigin($plot),
        );
        return null;
    }

    /**
     * Fetches the origin plot ({@see Plot}) of another plot asynchronously from the database (or synchronously from the
     * cache if contained) and returns a {@see \Generator}. It can be get
     * by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, Plot|null>
     */
    public function awaitMergeOrigin(BasePlot $plot) : Generator {
        if ($plot instanceof Plot) {
            return $plot;
        }
        if ($plot instanceof MergePlot) {
            /** @phpstan-var Plot|null $origin */
            $origin = yield $this->awaitPlot($plot->getWorldName(), $plot->getOriginX(), $plot->getOriginZ());
            return $origin;
        }
        $plotInCache = $this->caches[CacheIDs::CACHE_PLOT]->getObjectFromCache($plot->toString());
        if ($plotInCache instanceof Plot) {
            return $plotInCache;
        }
        if ($plotInCache instanceof MergePlot) {
            /** @phpstan-var Plot|null $origin */
            $origin = yield $this->awaitPlot($plotInCache->getWorldName(), $plotInCache->getOriginX(), $plotInCache->getOriginZ());
            return $origin;
        }
        /** @phpstan-var array<array<string, mixed>> $rows */
        $rows = yield $this->database->asyncSelect(
            self::GET_ORIGINPLOT,
            [
                "worldName" => $plot->getWorldName(),
                "mergeX" => $plot->getX(),
                "mergeZ" => $plot->getZ()
            ]
        );
        /** @phpstan-var array{originX: int, originZ: int}|null $plotData */
        $plotData = $rows[array_key_first($rows)] ?? null;
        if ($plotData === null) {
            /** @phpstan-var Plot|null $plot */
            $plot = yield $this->awaitPlot($plot->getWorldName(), $plot->getX(), $plot->getZ());
            return $plot;
        }
        /** @phpstan-var Plot|null $plot */
        $plot = yield $this->awaitPlot($plot->getWorldName(), $plotData["originX"], $plotData["originZ"]);
        return $plot;
    }

    /**
     * Fetches asynchronously all {@see Plot}s and {@see MergePlot}s in a plot world in a certain radius around plot
     * worldName;0;0 from the database. Returns a {@see \Generator} that returns a plot that is in the radius, closest
     * to the spawn and has no data in the database or null if no such plot could be found, by using {@see Await}.
     * @param int $limitXZ Limits the radius in which plots are fetched.
     * @phpstan-return Generator<mixed, mixed, mixed, Plot|null>
     */
    public function awaitNextFreePlot(string $worldName, WorldSettings $worldSettings, int $limitXZ = 0) : Generator {
        for ($i = 0; $limitXZ <= 0 || $i < $limitXZ; $i++) {
            $plots = [];
            /** @phpstan-var array<array{x: int, z: int}> $rows */
            $rows = yield $this->database->asyncSelect(
                self::GET_OWNED_PLOTS,
                [
                    "worldName" => $worldName,
                    "number" => $i
                ]
            );
            /** @var array{x: int, z: int} $row */
            foreach ($rows as $row) {
                /** @phpstan-var array<int, non-empty-array<int, true>> $plots */
                $plots[$row["x"]][$row["z"]] = true;
            }
            if (count($plots) === max(1, 8 * $i)) {
                continue;
            }
            if (($coordinates = $this->findEmptyPlotSquared(0, $i, $plots)) !== null) {
                [$x, $z] = $coordinates;
                /** @phpstan-var Plot|null $plot */
                $plot = yield $this->awaitMergeOrigin(new BasePlot($worldName, $worldSettings, $x, $z));
                return $plot;
            }
            for ($a = 1; $a < $i; $a++) {
                if (($coordinates = $this->findEmptyPlotSquared($a, $i, $plots)) !== null) {
                    [$x, $z] = $coordinates;
                    /** @phpstan-var Plot|null $plot */
                    $plot = yield $this->awaitMergeOrigin(new BasePlot($worldName, $worldSettings, $x, $z));
                    return $plot;
                }
            }
            if (($coordinates = $this->findEmptyPlotSquared($i, $i, $plots)) !== null) {
                [$x, $z] = $coordinates;
                /** @phpstan-var Plot|null $plot */
                $plot = yield $this->awaitMergeOrigin(new BasePlot($worldName, $worldSettings, $x, $z));
                return $plot;
            }
        }
        return null;
    }

    /**
     * @phpstan-param array<int, non-empty-array<int, true>> $plots
     * @return int[] | null
     */
    private function findEmptyPlotSquared(int $a, int $b, array $plots) : ?array {
        if (!isset($plots[$a][$b])) {
            return [$a, $b];
        }
        if (!isset($plots[$b][$a])) {
            return [$b, $a];
        }
        if ($a !== 0) {
            if (!isset($plots[-$a][$b])) {
                return [-$a, $b];
            }
            if (!isset($plots[$b][-$a])) {
                return [$b, -$a];
            }
            if ($b !== 0) {
                if (!isset($plots[-$a][-$b])) {
                    return [-$a, -$b];
                }
                if (!isset($plots[-$b][-$a])) {
                    return [-$b, -$a];
                }
            }
        }
        if ($b !== 0) {
            if (!isset($plots[-$b][$a])) {
                return [-$b, $a];
            }
            if (!isset($plots[$a][-$b])) {
                return [$a, -$b];
            }
        }
        return null;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function addMergePlot(Plot $origin, BasePlot $plot) : Generator {
        yield from $this->database->asyncInsert(
            self::SET_MERGEPLOT,
            [
                "worldName" => $origin->getWorldName(),
                "originX" => $origin->getX(), "originZ" => $origin->getZ(),
                "mergeX" => $plot->getX(), "mergeZ" => $plot->getZ()
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($origin->toString(), $origin);
        $mergePlot = MergePlot::fromBasePlot($plot, $origin->getX(), $origin->getZ());
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($mergePlot->toString(), $mergePlot);
    }

    /**
     * Fetches {@see Plot}s by a common {@see PlotPlayer} asynchronously from the database and returns a {@see \Generator}.
     * It can be get by using {@see Await}.
     * @phpstan-return Generator<mixed, mixed, mixed, array<string, Plot>>
     */
    public function awaitPlotsByPlotPlayer(int $playerID, string $state) : Generator {
        /** @phpstan-var array<array<string, mixed>> $rows */
        $rows = yield $this->database->asyncSelect(
            self::GET_PLOTS_BY_PLOTPLAYER,
            [
                "playerID" => $playerID,
                "state" => $state,
            ]
        );
        $plots = [];
        /** @phpstan-var array{worldName: string, x: int, z: int} $row */
        foreach ($rows as $row) {
            /** @phpstan-var Plot|null $plot */
            $plot = yield $this->awaitPlot($row["worldName"], $row["x"], $row["z"]);
            if ($plot instanceof Plot) {
                $plots[$plot->toString()] = $plot;
            }
        }
        return $plots;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : Generator {
        yield from $this->database->asyncInsert(
            self::SET_PLOTPLAYER,
            [
                "worldName" => $plot->getWorldName(), "x" => $plot->getX(), "z" => $plot->getZ(),
                "playerID" => $plotPlayer->getPlayerData()->getPlayerID(),
                "state" => $plotPlayer->getState(),
                "addTime" => date("d.m.Y H:i:s", $plotPlayer->getAddTime())
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function deletePlotPlayer(Plot $plot, int $playerID) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTPLAYER,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "playerID" => $playerID
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
    }

    /**
     * @template TFlag of Flag<mixed>
     * @param TFlag $flag
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function savePlotFlag(Plot $plot, Flag $flag) : Generator {
        yield from $this->database->asyncInsert(
            self::SET_PLOTFLAG,
            [
                "worldName" => $plot->getWorldName(), "x" => $plot->getX(), "z" => $plot->getZ(),
                "ID" => $flag->getID(),
                "value" => $flag->toString()
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function deletePlotFlag(Plot $plot, string $flagID) : Generator {
        yield $this->database->asyncInsert(
            self::DELETE_PLOTFLAG,
            [
                "worldName" => $plot->getWorldName(),
                "x" => $plot->getX(),
                "z" => $plot->getZ(),
                "ID" => $flagID
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function savePlotRate(Plot $plot, PlotRate $plotRate) : Generator {
        yield from $this->database->asyncInsert(
            self::SET_PLOTRATE,
            [
                "worldName" => $plot->getWorldName(), "x" => $plot->getX(), "z" => $plot->getZ(),
                "rate" => $plotRate->getRate(),
                "playerID" => $plotRate->getPlayerData()->getPlayerID(),
                "rateTime" => date("d.m.Y H:i:s", $plotRate->getRateTime()),
                "comment" => $plotRate->getComment()
            ]
        );
        $this->caches[CacheIDs::CACHE_PLOT]->cacheObject($plot->toString(), $plot);
    }

    public function close() : void {
        $this->database->close();
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    private function importData() : Generator {
        if(is_dir(Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot")) &&
            file_exists(Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "config.yml"))) {
            /** @var string[][] $settings */
            $settings = yaml_parse_file(Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "config.yml"));
            switch(mb_strtolower($settings["DataProvider"])) {
                case 'sqlite':
                    $myplotDatabase = libasynql::create(CPlot::getInstance(), [
                        "type" => "sqlite",
                        "sqlite" => [
                            "file" => Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "plots.db")
                        ]
                    ], [
                        "sqlite" => "sql" . DIRECTORY_SEPARATOR . "myplot_sqlite.sql"
                    ]);
                case 'mysql':
                    $sqlSettings = $settings["MySQLSettings"];
                    $myplotDatabase = $myplotDatabase ?? libasynql::create(CPlot::getInstance(), [
                        "type" => "mysql",
                        "mysql" => [
                            "host" => $sqlSettings["Host"],
                            "user" => $sqlSettings["Username"],
                            "password" => $sqlSettings["Password"],
                            "schema" => $sqlSettings["DatabaseName"],
                            "port" => $sqlSettings["Port"]
                        ],
                        "worker-limit" => ResourceManager::getInstance()->getConfig()->getNested("database.worker-limit", 1)
                    ], [
                        "mysql" => "sql" . DIRECTORY_SEPARATOR . "myplot_mysql.sql"
                    ]);
                    $records = yield from $myplotDatabase->asyncSelect(self::EXPORT_MYPLOT_PLOTS);
                    $mergeRecords = yield from $myplotDatabase->asyncSelect(self::EXPORT_MYPLOT_MERGES);
                    break;
                case 'yaml':
                    $filename = "plots.yml";
                case 'json':
                    $data = new Config(Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "Data", $filename ?? "plots.json"), Config::DETECT);
                    $records = array_values($data->get("plots"));
                    $unparsedMergeRecords = $data->get("merges");
                    $mergeRecords = [];
                    foreach($unparsedMergeRecords as $origin => $merges) {
                        $originData = explode(";", $origin);
                        foreach($merges as $merge) {
                            $mergeData = explode(";", $merge);
                            $mergeRecords[] = [
                                "level" => $originData[0],
                                "originX" => $originData[1],
                                "originZ" => $originData[2],
                                "mergedX" => $mergeData[0],
                                "mergedZ" => $mergeData[1]
                            ];
                        }
                    }
                    break;
                default:
                    return; // don't import anything due to invalid data provider
            }
            foreach($records as $record) {
                // validate offline player data
                $offlineData = Server::getInstance()->getOfflinePlayerData($record["owner"]);
				$XUID = $offlineData?->getString("LastKnownXUID", null);

				// register filler player data
                /** @var PlayerData|null $playerData */
                $playerData = yield $this->updatePlayerData(
					null, // doesn't matter what is input at this point. will overwrite on login
					$XUID,
					$record["owner"]
				);
                if (!($playerData instanceof PlayerData)) {
					// retry now that player data updated
					$playerData = yield $this->awaitPlayerDataByData(
						null,
						$XUID,
						$record["owner"]
					);
                }

                // load world
                /** @var WorldSettings|false $world */
                $world = yield $this->awaitWorld($record["level"]);
                if($world === false)
                    continue;

                // load plot
                /** @var Plot|null $plot */
                $plot = yield $this->awaitPlot($record["level"], (int)$record["x"], (int)$record["z"]);
                if($plot === null)
                    continue;

                // claim plot
                $senderData = new PlotPlayer($playerData, PlotPlayer::STATE_OWNER);
                $plot->addPlotPlayer($senderData);
                yield from DataProvider::getInstance()->savePlotPlayer($plot, $senderData);

				// load helpers
				foreach($record["helpers"] as $playerName) {
					// validate offline player data
					$offlineData = Server::getInstance()->getOfflinePlayerData($playerName);
					$XUID = $offlineData?->getString("LastKnownXUID", null);

					// register filler player data
					/** @var PlayerData|null $playerData */
					$playerData = yield $this->updatePlayerData(
						null, // doesn't matter what is input at this point. will overwrite on login
						$XUID,
						$playerName
					);
					if (!($playerData instanceof PlayerData)) {
						// retry now that player data updated
						$playerData = yield $this->awaitPlayerDataByData(
							null,
							$XUID,
							$playerName
						);
					}

					$senderData = new PlotPlayer($playerData, PlotPlayer::STATE_TRUSTED);
					$plot->addPlotPlayer($senderData);
					yield from DataProvider::getInstance()->savePlotPlayer($plot, $senderData);
				}

				// load denied with priority over helpers
				foreach($record["denied"] as $playerName) {
					// validate offline player data
					$offlineData = Server::getInstance()->getOfflinePlayerData($playerName);
					$XUID = $offlineData?->getString("LastKnownXUID", null);

					// register filler player data
					/** @var PlayerData|null $playerData */
					$playerData = yield $this->updatePlayerData(
						null, // doesn't matter what is input at this point. will overwrite on login
						$XUID,
						$playerName
					);
					if (!($playerData instanceof PlayerData)) {
						// retry now that player data updated
						$playerData = yield $this->awaitPlayerDataByData(
							null,
							$XUID,
							$playerName
						);
					}

					$senderData = new PlotPlayer($playerData, PlotPlayer::STATE_DENIED);
					$plot->addPlotPlayer($senderData);
					yield from DataProvider::getInstance()->savePlotPlayer($plot, $senderData);
				}

				//load common flags
				$flag = Flags::PVP()->createInstance($record["pvp"]);
				$flag = $plot->getLocalFlagByID($flag->getID())?->merge($flag->getValue()) ?? $flag;
				$plot->addFlag($flag);
				$this->savePlotFlag($plot, $flag);
            }
            foreach($mergeRecords as $mergeRecord) {
                // load world
                /** @var WorldSettings|false $world */
                $world = yield $this->awaitWorld($mergeRecord["level"]);
                if($world === false)
                    continue;

                // load merge plot 1
                /** @var Plot|null $plot */
                $plot = yield $this->awaitPlot($mergeRecord["level"], (int)$mergeRecord["originX"], (int)$mergeRecord["originZ"]);
                if($plot === null)
                    continue;

                // load merge plot 2
                /** @var Plot|null $plotToMerge */
                $plotToMerge = yield $this->awaitPlot($mergeRecord["level"], (int)$mergeRecord["mergedX"], (int)$mergeRecord["mergedZ"]);
                if($plotToMerge === null)
                    continue;

                // complete merge logic
                yield from DataProvider::getInstance()->awaitPlotDeletion($plotToMerge);
                foreach($plotToMerge->getMergePlots() as $mergePlot){
                    $plot->addMergePlot($mergePlot);
                    yield from $this->addMergePlot($plot, $mergePlot);
                }
                foreach($plotToMerge->getPlotPlayers() as $mergePlotPlayer) {
                    $plot->addPlotPlayer($mergePlotPlayer);
                    yield from $this->savePlotPlayer($plot, $mergePlotPlayer);
                }
                foreach ($plotToMerge->getFlags() as $mergeFlag) {
                    $flag = $plot->getFlagByID($mergeFlag->getID());
                    if ($flag === null) {
                        $flag = $mergeFlag;
                    } else {
                        $flag = $flag->merge($mergeFlag->getValue());
                    }
                    $plot->addFlag($flag);
                    yield from DataProvider::getInstance()->savePlotFlag($plot, $flag);
                }
                foreach ($plotToMerge->getPlotRates() as $mergePlotRate) {
                    $plot->addPlotRate($mergePlotRate);
                    yield from DataProvider::getInstance()->savePlotRate($plot, $mergePlotRate);
                }
            }
            rename( // rename config file to prevent re-import without losing data
                Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "config.yml"),
                Path::join(Server::getInstance()->getDataPath(), "plugin_data", "MyPlot", "config_old.yml")
            );
        }
    }
}