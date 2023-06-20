<?php

namespace TrFolwe\utils;

use pocketmine\utils\Config;
use TrFolwe\SkyWars;

final class LangConverter {

    /**
     * @param string $actionText
     * @return string
     */
    public static function convertLanguage(string $actionText) :string {
        $instance = SkyWars::getInstance();
        $defaultLang = $instance->getConfig()->get("defaultLang");
        $langConfig = new Config($instance->getDataFolder()."langs/".$defaultLang."_EN.yml", Config::YAML);
        return $langConfig->get($actionText) ?? "Not found!";
    }
    
    public static function saveLangResources() :void {
        $instance = SkyWars::getInstance();
        foreach($instance->getConfig()->get("Langs") as $language) {
            $instance->saveResource("langs/" . $language . "_EN.yml");
            $instance->getServer()->getLogger()->notice("Language file is ".$language."_EN ready");
        }
        $instance->getServer()->getLogger()->notice("Default lang: ".$instance->getConfig()->get("defaultLang")." selected");
    }
}