<?php

namespace TrFolwe\database;

use SQLite3;
use TrFolwe\SkyWars;

class SQLite
{
    /*** @var SQLite3 $sql */
    private SQLite3 $sql;

    public function __construct()
    {
        $this->sql = new SQLite3(SkyWars::getInstance()->getDataFolder(). "SkyWarsDatabase.db");
        $this->sql->exec("CREATE TABLE IF NOT EXISTS playerTable(
    playerName VARCHAR(25) NOT NULL,
    kill INT(6) NOT NULL DEFAULT 0,
    gameWin INT(6) NOT NULL DEFAULT 0
    )");
    }

    /**
     * @param string $playerName
     * @return void
     */
    public function addPlayer(string $playerName): void
    {
        $data = $this->sql->prepare("INSERT INTO playerTable(playerName) VALUES (:playerName)");
        $data->bindValue("playerName", $playerName);
        $data->execute();
    }

    /**
     * @param string $playerName
     * @return void
     */
    public function updateKillData(string $playerName): void
    {
        $data = $this->sql->prepare("UPDATE playerTable SET kill = kill + 1 WHERE playerName = :playerName");
        $data->bindValue("playerName", $playerName);
        $data->execute();
    }

    /**
     * @param string $playerName
     * @return void
     */
    public function updateWinData(string $playerName) :void
    {
        $data = $this->sql->prepare("UPDATE playerTable SET gameWin = gameWin + 1 WHERE playerName = :playerName");
        $data->bindValue("playerName", $playerName);
        $data->execute();
    }

    /**
     * @param string $playerName
     * @return int
     */
    public function getPlayerKill(string $playerName): int
    {
        return $this->sql->query("SELECT kill from playerTable WHERE playerName = '".$playerName."'")->fetchArray(SQLITE3_NUM)[0];
    }

    /**
     * @param string $playerName
     * @return int
     */
    public function getPlayerWin(string $playerName): int
    {
        return $this->sql->query("SELECT gameWin from playerTable WHERE playerName = '".$playerName."'")->fetchArray(SQLITE3_NUM)[0];
    }
    
    public function getAllData(): array
    {
        $data = $this->sql->prepare("SELECT * FROM playerTable");
        $result = $data->execute();
        $array = [];
        
        while($rows = $result->fetchArray(SQLITE3_ASSOC))
         $array[] = $rows;
        return $array;
    }
}