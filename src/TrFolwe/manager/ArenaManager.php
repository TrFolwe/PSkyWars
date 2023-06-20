<?php

namespace TrFolwe\manager;

use Closure;
use FilesystemIterator;
use pocketmine\Server;
use RecursiveDirectoryIterator;
use TrFolwe\SkyWars;
use TrFolwe\threads\ArenaProcessThread;

class ArenaManager
{

    public static function arenaLoad(): void
    {
        $arenaWorldsDir = SkyWars::getInstance()->getServer()->getDataPath()."MgMaps/";
        $config = SkyWars::getInstance()->getConfig();
        $arenaWorlds = [];
        foreach(new RecursiveDirectoryIterator($arenaWorldsDir, FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->isDir() && in_array($file->getFilename(), array_map(fn($c) => $c["WorldName"], $config->get("SkyWars"))))
                $arenaWorlds[] = $file->getFilename();
        }
        Server::getInstance()->getLogger()->notice("§aSkyWars arena maps loaded. [".implode(", ", $arenaWorlds)."]");
    }

    /**
     * @param string $gameName
     * @param Closure $callback
     * @return void
     */
    public static function arenaCreate(string $gameName, Closure $callback): void
    {
        $dataPath = SkyWars::getInstance()->getServer()->getDataPath();
        $worldName = SkyWars::getInstance()->getConfig()->get("SkyWars")[$gameName]["WorldName"];
        SkyWars::getInstance()->getServer()->getAsyncPool()->submitTask(new ArenaProcessThread(
            $dataPath . "MgMaps/" . $worldName,
            $dataPath . "worlds/" . $worldName,
            "copy",
            function() use($worldName, $callback) :void {
				Server::getInstance()->getWorldManager()->loadWorld($worldName);
				$callback();
			}
        ));
    }

    public static function arenaDelete(string $worldName) :void
    {
        $worldManager = Server::getInstance()->getWorldManager();
        if($worldManager->isWorldGenerated($worldName) && $worldManager->isWorldLoaded($worldName) && file_exists(Server::getInstance()->getDataPath()."worlds/".$worldName))
            $worldManager->unloadWorld($worldManager->getWorldByName($worldName));
        SkyWars::getInstance()->getServer()->getAsyncPool()->submitTask(new ArenaProcessThread(
            $worldName,
            "",
            "delete",
            function(){}
        ));
    }
}