<?php

namespace TrFolwe\manager;

use pocketmine\block\tile\Chest;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use TrFolwe\SkyWars;
use TrFolwe\task\GameRunningTask;
use TrFolwe\utils\LangConverter;

class GameManager
{

    /*** @var array $SkyWarsGame */
    private array $SkyWarsGame;

    public function __construct()
    {
        $this->SkyWarsGame = SkyWars::getInstance()->SkyWarsGame;
    }

    /**
     * @param Player $player
     * @param string|null $selectedGame
     * @return void
     */
    public function joinGame(Player $player, string $selectedGame = null): void
    {
        $config = SkyWars::getInstance()->getConfig();
        $player->sendMessage("§8[§a?§8] §7" . LangConverter::convertLanguage("founded_game"));
        if (!empty($this->SkyWarsGame)) {
            if (!$selectedGame) {
                do {
                    $selectedGame = array_keys($this->SkyWarsGame)[array_rand(array_keys($this->SkyWarsGame))];
                } while (($config->get("SkyWars")[$selectedGame]["MaxPlayer"] * 1) === count(SkyWars::getInstance()->SkyWarsGame[$selectedGame]["Players"]) and $this->SkyWarsGame[$selectedGame]["GameState"] == "Waiting");
            }
            $gamePositionInfo = array_map(fn($i) => $i * 1, explode(":", $config->get("SkyWars")[$selectedGame]["SpawnPosition"][count($this->SkyWarsGame[$selectedGame]["Players"])]));
            $player->teleport(new Position($gamePositionInfo[0], $gamePositionInfo[1], $gamePositionInfo[2], Server::getInstance()->getWorldManager()->getWorldByName($config->get("SkyWars")[$selectedGame]["WorldName"])));
            SkyWars::getInstance()->SkyWarsGame[$selectedGame]["Players"][] = $player->getName();
        } else {
            $game = array_keys($config->get("SkyWars"))[array_rand(array_keys($config->get("SkyWars")))];
            ArenaManager::arenaCreate($game, function () use ($config, $game, $player): void {
                $gamePositionInfo = array_map(fn($i) => $i * 1, explode(":", $config->get("SkyWars")[$game]["SpawnPosition"][0]));
                $worldName = $config->get("SkyWars")[$game]["WorldName"];
                $player->teleport(new Position($gamePositionInfo[0], $gamePositionInfo[1], $gamePositionInfo[2], Server::getInstance()->getWorldManager()->getWorldByName($worldName)));
                SkyWars::getInstance()->getScheduler()->scheduleRepeatingTask(new GameRunningTask($game), 20);
                SkyWars::getInstance()->SkyWarsGame[$game] = [
                    "WorldName" => $worldName,
                    "Players" => [
                        $player->getName()
                    ],
                    "Viewers" => [],
                    "GameState" => "Waiting"
                ];
            });
        }
        $player->setNoClientPredictions();
        $player->setGamemode(GameMode::SPECTATOR());
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
    }

    /**
     * @param Player $player
     * @param string $gameName
     * @return void
     */
    public function joinForViewers(Player $player, string $gameName): void
    {
        $player->setGamemode(GameMode::SPECTATOR());
        $player->sendMessage("§8[§6" . $gameName . "§8] ".LangConverter::convertLanguage("join_viewers"));
        $gameItem = VanillaItems::PAPER()->setCustomName("§a" . $gameName)->setLore([
            "Game settings"
        ]);
        $gameItem->getNamedTag()->setString("gameItem", true);
        $player->getInventory()->setItem(4, $gameItem);
        unset(SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"][array_search($player->getName(), SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"])]);
        SkyWars::getInstance()->SkyWarsGame[$gameName]["Viewers"][] = $player->getName();
    }

    /**
     * @param string $gameName
     * @return void
     */
    public function addChestItems(string $gameName): void
    {
        $gameWorld = Server::getInstance()->getWorldManager()->getWorldByName(SkyWars::getInstance()->SkyWarsGame[$gameName]["WorldName"]);
        $chestItems = SkyWars::getInstance()->getConfig()->get("SkyWars")[$gameName]["ChestItems"];
        foreach (array_merge(...array_map(fn($c) => $c->getTiles(), $gameWorld->getLoadedChunks())) as $chestTiles) {
            if ($chestTiles instanceof Chest) {
                //$chestTiles->getInventory()->clearAll();
                foreach ($chestItems as $itemInfo => $enchantmentsInfo) {
                    $itemExplodeInfo = explode("-", $itemInfo);
                    $item = StringToItemParser::getInstance()->parse($itemExplodeInfo[0])->setCount($itemExplodeInfo[1] * 1);
                    if (!empty($enchantmentsInfo)) {
                        foreach ($enchantmentsInfo as $enchantment) {
                            $enchantmentId = explode("-", $enchantment)[0] * 1;
                            $enchantmentLevel = explode("-", $enchantment)[1] * 1;
                            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId($enchantmentId), $enchantmentLevel));
                        }
                    }
                    $chestTiles->getInventory()->addItem($item);
                }
            }
        }
    }
}