<?php

namespace TrFolwe\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\player\GameMode;
use pocketmine\world\sound\ClickSound;
use TrFolwe\manager\ScoreboardManager;
use TrFolwe\SkyWars;

class GameRunningTask extends Task
{

    /*** @var string $gameName */
    private string $gameName;
    /**
     * @var int $gameStartingTick
     * @var int $gameTime
     */
    private int $gameStartingTick = 0, $gameTime = 0;

    /*** @param string $gameName */
    public function __construct(string $gameName)
    {
        $this->gameName = $gameName;
    }

    public function onRun(): void
    {
        if (!isset(SkyWars::getInstance()->SkyWarsGame[$this->gameName])) {
            $this->getHandler()->cancel();
            return;
        }
        $gamePlayersCount = count(SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"]);
        $gamePlayersMaxCount = SkyWars::getInstance()->getConfig()->get("SkyWars")[$this->gameName]["MaxPlayer"] * 1;
        $gameState = SkyWars::getInstance()->SkyWarsGame[$this->gameName]["GameState"];
        if ($gameState == "Waiting") {
            if ($gamePlayersCount == $gamePlayersMaxCount) {
                if ($this->gameStartingTick == 5) {
                    SkyWars::getInstance()->SkyWarsGame[$this->gameName]["GameState"] = "Started";
                    foreach (SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"] as $playersName) {
                        if ($gamePlayers = Server::getInstance()->getPlayerExact($playersName)) {
                            $gamePlayers->setNoClientPredictions(false);
                            $gamePlayers->setGamemode(GameMode::SURVIVAL());
                            $gamePlayers->sendTip("§eGame Started!\nGO GO GO");
                        }
                    }
                    SkyWars::getInstance()->getManager()->addChestItems($this->gameName);
                } else {
                    $this->gameStartingTick++;
                    foreach (SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"] as $playersName) {
                        if ($gamePlayers = Server::getInstance()->getPlayerExact($playersName)) {
                            $gamePlayers->sendTitle(str_repeat("§a◯", $this->gameStartingTick) . str_repeat("§c◯", 5 - $this->gameStartingTick));
                            $gamePlayers->getWorld()->addSound($gamePlayers->getPosition()->asVector3(), new ClickSound(), [$gamePlayers]);
                        }
                    }
                }
            } else {
                foreach (array_merge(SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"], SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Viewers"]) as $playersName) {
                    Server::getInstance()->getPlayerExact($playersName)?->sendTip("§e".($gamePlayersMaxCount - $gamePlayersCount)." §7Players required to start the game");
                }
            }
        } else {
            $this->gameTime++;
            foreach (array_map(fn($i) => Server::getInstance()->getPlayerExact($i), array_merge(SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"], SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Viewers"])) as $gamePlayers) {
                if (!($gamePlayers)) continue;
                $colorArray = array_map(fn($i) => "§" . $i, range(0, 9));
                ScoreboardManager::new($gamePlayers, $colorArray[mt_rand(0, count($colorArray) - 1)] . "SkyWars", $colorArray[mt_rand(0, count($colorArray) - 1)] . $this->gameName);
                ScoreboardManager::setLine($gamePlayers, 1, "§e" . $this->gameName);
                ScoreboardManager::setLine($gamePlayers, 2, "\n");
                ScoreboardManager::setLine($gamePlayers, 3, "§7Players: §a" . count(SkyWars::getInstance()->SkyWarsGame[$this->gameName]["Players"]) . "§7/§c" . $gamePlayersMaxCount);
            }
        }
    }
}