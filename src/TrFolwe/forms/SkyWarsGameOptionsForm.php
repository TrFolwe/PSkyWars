<?php

namespace TrFolwe\forms;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use TrFolwe\SkyWars;

class SkyWarsGameOptionsForm extends MenuForm
{
    public function __construct(string $gameName)
    {

        parent::__construct(
            $gameName,
            "\n",
            [
                new MenuOption("Player Teleport"),
                new MenuOption("Left the game")
            ],
            function (Player $player, int $selected) use($gameName): void{
                if($selected == 0) {
                    $player->sendForm(new SkyWarsPlayerTeleportForm($gameName));
                }elseif($selected == 1) {
                    unset(SkyWars::getInstance()->SkyWarsGame[$gameName]["Viewers"][array_search($player->getName(), SkyWars::getInstance()->SkyWarsGame[$gameName]["Viewers"])]);
                    $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                    $player->getInventory()->clearAll();
                    $player->setGamemode(GameMode::SURVIVAL());
                    $player->sendMessage("§8[§6".$gameName."§8] §7İzleyici moddan ayrıldınız!");
                }
            });
    }
}