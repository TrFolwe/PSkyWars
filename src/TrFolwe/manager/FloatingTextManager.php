<?php

declare(strict_types=1);

namespace TrFolwe\manager;

use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

class FloatingTextManager{

    /** @var array */
    private static array $fts = [];

    /**
     * @return array
     */
    public static function getFloatingTexts(): array{
        return self::$fts;
    }

    /**
     * @param Position $position
     * @param string $text
     * @param string $tag
     */
    public static function createFloatingText(Position $position, string $text, string $tag): void
    {
        $ft = new FloatingTextParticle($text);
        self::$fts[$tag] = [$position, $ft];
        $position->getWorld()->addParticle($position->add(0.5, 1, 0.5), $ft, $position->getWorld()->getPlayers());
    }

    /**
     * @param string $tag
     * @param string $text
     */
    public static function updateTextOfFloatingText(string $tag, string $text): void
    {
        $ft = self::$fts[$tag][1];
        $ft->setText($text);
        self::$fts[$tag][1] = $ft;
        self::$fts[$tag][0]->getWorld()->addParticle(self::$fts[$tag][0], $ft, self::$fts[$tag][0]->getWorld()->getPlayers());
    }

    /**
     * @param string $tag
     */
    public static function removeFloatingText(string $tag): void
    {
        /** @var FloatingTextParticle $ft */
        $ft = self::$fts[$tag][1];
        $ft->setInvisible();
        self::$fts[$tag][1] = $ft;
        self::$fts[$tag][0]->getWorld()->addParticle(self::$fts[$tag][0], $ft, self::$fts[$tag][0]->getWorld()->getPlayers());
        unset(self::$fts[$tag]);
    }

    public static function setVisibleFloatingText(string $tag, bool $invisible = true) :void {
        /*** @var FloatingTextParticle $ft */
        $ft = self::$fts[$tag][1];
        $ft->setInvisible($invisible);
        self::$fts[$tag][1] = $ft;
        self::$fts[$tag][0]->getWorld()->addParticle(self::$fts[$tag][0]->add(0.5, 1, 0.5), $ft, self::$fts[$tag][0]->getWorld()->getPlayers());
    }
}
