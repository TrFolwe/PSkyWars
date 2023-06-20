<?php

namespace TrFolwe\forms;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use pocketmine\player\Player;
use pocketmine\Server;
use TrFolwe\SkyWars;

class SkyWarsPlayerTeleportForm extends CustomForm
{
    public function __construct(string $gameName)
    {
        parent::__construct(
            $gameName . " - Player Teleport",
            [
                new Dropdown("element0", "Select player", array_map(fn($i) => Server::getInstance()->getPlayerExact($i)?->getName(), SkyWars::getInstance()->SkyWarsGame[$gameName]["Players"]))
            ],
            function (Player $player, CustomFormResponse $response): void {
                    Server::getInstance()->getPlayerExact($this->getElement(0)->getOption($response->getString("element0")))?->teleport($selectedPlayer->getPosition()->asVector3());
            });
    }
}