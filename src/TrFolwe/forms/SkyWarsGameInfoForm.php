<?php

namespace TrFolwe\forms;

use dktapps\pmforms\ModalForm;
use pocketmine\player\Player;
use TrFolwe\SkyWars;

class SkyWarsGameInfoForm extends ModalForm
{

    public function __construct(string $gameName)
    {
        $gamePlayerCount = count(SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"]);
        $gameMaxPlayerCount = (int)SkyWars::getInstance()->getConfig()->get("SkyWars")[$gameName]["MaxPlayer"];
        $GameState = SkyWars::getInstance()->SkyWarsGame[$gameName]["GameState"];
        parent::__construct(
            $gameName,
            "§7Game state: ".($GameState == "Started" ? "§cGame started" : ($gamePlayerCount == $gameMaxPlayerCount ? "§cArena is fulled" : "§7For the game to start §e".($gameMaxPlayerCount-$gamePlayerCount)." §7player required")),
            function (Player $player, ?bool $submit) use($gameName) :void{
                if(!$submit || !isset(SkyWars::getInstance()->SkyWarsGame[$gameName])) return;
                $gameState = SkyWars::getInstance()->SkyWarsGame[$gameName]["GameState"];
                $gamePlayerCount = count(SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"]);
                $gameMaxPlayerCount = (int)SkyWars::getInstance()->getConfig()->get("SkyWars")[$gameName]["MaxPlayer"];
                if($gameState == "Started" or $gamePlayerCount == $gameMaxPlayerCount)
                    SkyWars::getInstance()->getManager()->joinForViewers($player, $gameName);
                else SkyWars::getInstance()->getManager()->joinGame($player, $gameName);
            },  $GameState == "Started" ? "İZLE" : ($gamePlayerCount === $gameMaxPlayerCount ? "İZLE" : "KATIL"));
    }
}