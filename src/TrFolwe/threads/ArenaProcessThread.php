<?php

namespace TrFolwe\threads;

use Closure;
use FilesystemIterator;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use RecursiveDirectoryIterator;

class ArenaProcessThread extends AsyncTask {

    /**
     * @var string $sourceDir
     * @var string $targetDirPath
     * @var string $processType
     */
    private string $sourceDirPath, $targetDirPath, $processType;

    /*** @var Closure $processFn */
    private Closure $processFn;

    public function __construct(string $sourceDirPath, string $targetDirPath, string $processType, Closure $processFn) {
        $this->sourceDirPath = $sourceDirPath;
        $this->targetDirPath = $targetDirPath;
        $this->processType = $processType;
        $this->processFn = $processFn;
    }
    public function onRun(): void
    {
        if($this->processType === "copy") $this->copyDir($this->sourceDirPath, $this->targetDirPath);
        else if($this->processType === "delete") $this->deleteDir($this->sourceDirPath);
    }

    /**
     * @param string $sourceDirPath
     * @param string $targetDirPath
     * @return void
     */
    private function copyDir(string $sourceDirPath, string $targetDirPath) :void {
        @mkdir($targetDirPath);
        $sourceDir = new RecursiveDirectoryIterator($sourceDirPath, FilesystemIterator::SKIP_DOTS);
        foreach($sourceDir as $file) {
            $targetSubPathName = $targetDirPath."/".$sourceDir->getSubPathName();
            if($file->isDir()) {
                @mkdir((string)$file);
                $this->copyDir((string)$file, $targetSubPathName);
                continue;
            }
            copy($file, $targetSubPathName);
        }
        $this->setResult(true);
    }

    /**
     * @param string $dirName
     * @return void
     */
    private function deleteDir(string $dirName) :void {
        $files = new \RecursiveIteratorIterator(new RecursiveDirectoryIterator($worldPath = Server::getInstance()->getDataPath() . "/worlds/$name", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /*** @var \SplFileInfo $fileInfo */
        foreach($files as $fileInfo) {
            if($filePath = $fileInfo->getRealPath()) {
                if($fileInfo->isFile()) unlink($filePath);
                else rmdir($filePath);
            }
        }
        rmdir($worldPath);
        $this->setResult(true);
    }

    public function onCompletion(): void
    {
        ($this->processFn)();
    }
}