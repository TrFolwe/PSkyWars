<?php

namespace TrFolwe\forms;

use dktapps\pmforms\FormIcon;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use TrFolwe\SkyWars;

class SkyWarsAvailableGamesForm extends MenuForm
{

    public function __construct()
    {
        $gameOptions = [];

        if(!empty(SkyWars::getInstance()->SkyWarsGame)) {
            foreach (array_keys(SkyWars::getInstance()->SkyWarsGame) as $gameName)
                $gameOptions[] = new MenuOption($gameName, new FormIcon("textures/items/book_enchanted.png", FormIcon::IMAGE_TYPE_PATH));
        }

        parent::__construct(
          "§lAvailable matches",
          "§7Total there are §6".count(array_keys(SkyWars::getInstance()->SkyWarsGame))." §7Games",
            $gameOptions,
            function (Player $player, int $selectedOption) :void {
                $selectedGame = explode("\n", $this->getOption($selectedOption)->getText())[0];
                if(!array_key_exists($selectedGame, SkyWars::getInstance()->SkyWarsGame)) return;
                $player->sendForm(new SkyWarsGameInfoForm($selectedGame));
            });
    }
}
