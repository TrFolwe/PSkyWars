<?php

namespace TrFolwe\listener;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\GameMode;
use pocketmine\world\sound\ArrowHitSound;
use TrFolwe\database\SQLite;
use TrFolwe\forms\SkyWarsGameOptionsForm;
use TrFolwe\forms\SkyWarsMainForm;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use TrFolwe\manager\ArenaManager;
use TrFolwe\manager\FloatingTextManager;
use TrFolwe\manager\ScoreboardManager;
use TrFolwe\SkyWars;
use TrFolwe\utils\LangConverter;

class SkyWarsListener implements Listener
{

    /*** @var SQLite $database */
    private SQLite $database;
    
    public function __construct() {
        $this->database = SkyWars::getInstance()->getDatabase();
    }
	
	public function onChat(PlayerChatEvent $event) :void {
		if($event->getMessage() === "Give me game item")
			$event->getPlayer()->getInventory()->addItem(VanillaItems::CLOCK()->setCustomName("SkyWars Games"));
	}

    /**
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function onQuitPlayer(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        foreach (SkyWars::getInstance()->SkyWarsGame as $gameName => $gameInfo) {
            if (in_array($player->getName(), $gameInfo["Players"])) {
                unset(SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"][array_search($player->getName(), SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"])]);
                foreach (array_merge($gameInfo["Players"], $gameInfo["Viewers"]) as $playersName) {
                        Server::getInstance()->getPlayerExact($playersName)?->sendMessage("§8[§6" . $gameName . "§8] ".str_replace("%player",$player->getName(), LangConverter::convertLanguage("player_left_game")));
                }

                $player->getInventory()->clearAll();
                $player->getArmorInventory()->clearAll();
                $player->setGamemode(GameMode::SURVIVAL());
                $event->setQuitMessage("");
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
		
        $player->getInventory()->addItem(VanillaItems::CLOCK()->setCustomName("SkyWars Games"));
        if(!in_array($player->getName(), array_map(fn($c) => $c["playerName"],$this->database->getAllData())))
            $this->database->addPlayer($player->getName());
    }

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->getCustomName() == "SkyWars Games") {
            $event->cancel();
            $player->sendForm(new SkyWarsMainForm());
            $event->cancel();
        }else if(($item->getLore()[0] ?? "") == "Game settings") {
            $event->cancel();
            $player->sendForm(new SkyWarsGameOptionsForm(C::clean($item->getCustomName())));
        }
    }

    /**
     * @param PlayerDeathEvent $event
     * @return void
     */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getEntity();
        $cause = $player->getLastDamageCause();
        if ($player instanceof Player) {
			foreach(SkyWars::getInstance()->SkyWarsGame as $game) {
				if(!in_array($player->getName(), $game["Players"])) return;
			}
            $event->setDeathMessage("");
            if ($cause instanceof EntityDamageByEntityEvent) {
                $killer = $cause->getDamager();
                if ($killer instanceof Player) {
                    foreach (SkyWars::getInstance()->SkyWarsGame as $gameName => $gameInfo) {
                        if (in_array($player->getName(), $gameInfo["Players"]) && in_array($killer->getName(), $gameInfo["Players"])) {
                            $gamePlayerCount = count($gameInfo["Players"]);
                            $this->database->updateKillData($killer->getName());
                            if (($gamePlayerCount - 1) == 1) {
                                $this->database->updateWinData($killer->getName());
                                foreach (array_map(fn($i) => Server::getInstance()->getPlayerExact($i), array_merge($gameInfo["Players"], $gameInfo["Viewers"])) as $gamePlayers) {
                                    $gamePlayers->sendMessage("§8[§6" . $gameName . "§8] ".str_replace("%player", $killer->getName(), LangConverter::convertLanguage("won_game")));
                                    $gamePlayers->setGamemode(GameMode::SURVIVAL());
                                    $gamePlayers->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                                    $gamePlayers->getInventory()->clearAll();
								    $gamePlayers->getArmorInventory()->clearAll();
									$gamePlayers->getInventory()->addItem(VanillaItems::CLOCK()->setCustomName("SkyWars Games"));
                                    ScoreboardManager::remove($gamePlayers);
                                }
                                ArenaManager::arenaDelete(SkyWars::getInstance()->getConfig()->getNested("SkyWars.".$gameName.".WorldName"));
                                unset(SkyWars::getInstance()->SkyWarsGame[$gameName]);
                            } else {
                                $player->setHealth(20);
                                FloatingTextManager::createFloatingText(
                                    $player->getPosition(),str_replace(["%player","%killer"],
                                    [$player->getName(), $killer->getName()],
                                    LangConverter::convertLanguage("floating_killed_player")),
                                    strtolower($player->getName())
                                );
                                SkyWars::getInstance()->getManager()->joinForViewers($player, $gameName);
                                foreach (array_merge($gameInfo["Players"], $gameInfo["Viewers"]) as $gamePlayers) {
                                    Server::getInstance()->getPlayerExact($gamePlayers)?->sendMessage("§8[§6" . $gameName . "§8] ".str_replace(["%player","%killer"], [$player->getName(), $killer->getName()], LangConverter::convertLanguage("killed_player")));
                                }
                            }
                        }
                    }
                }
            } else if($cause instanceof EntityDamageEvent) {
                foreach (SkyWars::getInstance()->SkyWarsGame as $gameName => $gameInfo) {
                    if (in_array($player->getName(), $gameInfo["Players"])) {
                        $gamePlayerCount = count($gameInfo["Players"]);
                        if (($gamePlayerCount - 1) == 1) {
                            $winnerPlayer = Server::getInstance()->getPlayerExact($gameInfo["Players"][0] ?? "TrFolwe");
                            foreach (array_map(fn($i) => Server::getInstance()->getPlayerExact($i), array_merge($gameInfo["Players"], $gameInfo["Viewers"])) as $gamePlayers) {
                                $gamePlayers->sendMessage("§8[§6" . $gameName . "§8] ".str_replace("%player", $winnerPlayer->getName(), LangConverter::convertLanguage("won_game")));
                                $gamePlayers->setGamemode(GameMode::SURVIVAL());
								$gamePlayers->getInventory()->clearAll();
								$gamePlayers->getArmorInventory()->clearAll();
                                $gamePlayers->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                                ScoreboardManager::remove($gamePlayers);
                            }
                            $this->database->updateWinData($winnerPlayer->getName(), ($this->database->getPlayerWin($winnerPlayer->getName()) + 1));
                            $winnerPlayer->getInventory()->addItem(VanillaItems::CLOCK()->setCustomName("SkyWars gAMES"));
                            ArenaManager::arenaDelete(SkyWars::getInstance()->getConfig()->getNested("SkyWars.".$gameName.".WorldName"));
                            unset(SkyWars::getInstance()->SkyWarsGame[$gameName]);
						} else {
                            $player->setHealth(20);
                            SkyWars::getInstance()->getManager()->joinForViewers($player, $gameName);
                            foreach (array_merge($gameInfo["Players"], $gameInfo["Viewers"]) as $gamePlayers) {
                                if ($players = Server::getInstance()->getPlayerExact($gamePlayers))
                                    $players->sendMessage("§8[§6" . $gameName . "§8] ".str_replace("%player", $player->getName(), LangConverter::convertLanguage("kill_void_player")));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param EntityTeleportEvent $event
     * @return void
     */
    public function onTeleport(EntityTeleportEvent $event): void
    {
        $player = $event->getEntity();
        $toWorld = $event->getTo()->getWorld();

        if ($player instanceof Player) {
            if (!empty(SkyWars::getInstance()->SkyWarsGame)) {
                foreach (SkyWars::getInstance()->SkyWarsGame as $gameName => $gameOptions) {
                    if (in_array($player->getName(), $gameOptions["Players"]) or in_array($player->getName(), $gameOptions["Viewers"])) {
                        $worldName = SkyWars::getInstance()->getConfig()->get("SkyWars")[$gameName]["WorldName"];
                        if ($worldName == $toWorld->getFolderName()) return;
                        unset(SkyWars::getInstance()->SkyWarsGame[$gameName][in_array($player->getName(), $gameOptions["Players"]) ? "Players" : "Viewers"][array_search($player->getName(), SkyWars::getInstance()->SkyWarsGame[$gameName][in_array($player->getName(), $gameOptions["Players"]) ? "Players" : "Viewers"])]);
                        ScoreboardManager::remove($player);
                    }
                }
            }
        }
    }

    /**
     * @param ProjectileHitEvent $event
     * @return void
     */
    public function onProjectileHit(ProjectileHitEvent $event) :void {
        $projectileEntity = $event->getEntity();
        $projectilePlayer = $projectileEntity->getOwningEntity();

        if($projectileEntity instanceof Arrow && $projectilePlayer instanceof Player) {
            //$hitPlayer = array_filter($projectile->getWorld()->getPlayers(), fn($p) => $p->getWorld() === $projectile->getWorld() && $p->getPosition()->asVector3()->equals($event->getRayTraceResult()->hitVector))[0] ?? null;
            $hitPlayer = $projectileEntity->getTargetEntity();
            if(!$hitPlayer instanceof Player) return;
            $projectilePlayer->sendTip("§6".$hitPlayer->getName()." §7(§a".$hitPlayer->getHealth()." §7 / §c".$hitPlayer->getMaxHealth().")");
            $projectilePlayer->getWorld()->addSound($projectilePlayer->getPosition()->asVector3(), new ArrowHitSound(), [$projectilePlayer, $hitPlayer]);
        }
    }
}