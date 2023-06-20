<?php

namespace TrFolwe;

use pocketmine\player\GameMode;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use TrFolwe\database\SQLite;
use TrFolwe\listener\SkyWarsListener;
use TrFolwe\manager\ArenaManager;
use TrFolwe\manager\GameManager;
use TrFolwe\utils\LangConverter;

class SkyWars extends PluginBase
{

    /*** @var SkyWars $instance */
    private static self $instance;

    /*** @var SQLite $sqlite */
    private SQLite $sqlite;

    /*** @var array */
    public array $SkyWarsGame = [];

    public function onLoad(): void
    {
        self::$instance = $this;
        $this->sqlite = new SQLite();
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new SkyWarsListener(), $this);
        $this->saveDefaultConfig();
		@mkdir($this->getDataFolder()."langs/");
        LangConverter::saveLangResources();
        @mkdir($this->getServer()->getDataPath(). "MgMaps/");
        ArenaManager::arenaLoad();
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("kill"));
    }

    public function onDisable(): void
    {
        foreach ($this->SkyWarsGame as $gameOptions) {
            if(!empty(array_merge($gameOptions["Viewers"], $gameOptions["Players"]))) {
                foreach (array_map(fn($i) => Server::getInstance()->getPlayerExact($i), array_merge($gameOptions["Viewers"], $gameOptions["Players"])) as $players) {
                    $players?->getInventory()->clearAll();
                    $players?->getArmorInventory()->clearAll();
                    $players?->setGamemode(GameMode::SURVIVAL());
                }
            }
        }
    }

    /*** @return self */
    public static function getInstance() :self
    {
        return self::$instance;
    }

    /*** @return GameManager */
    public function getManager() :GameManager
    {
        return new GameManager();
    }

    /*** @return SQLite */
    public function getDatabase(): SQLite
    {
        return $this->sqlite;
    }
}