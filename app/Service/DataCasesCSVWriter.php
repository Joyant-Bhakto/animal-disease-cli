<?php

namespace App\Service;

use SplFileInfo;
use SplFileObject;

class DataCasesCSVWriter
{
    private string $advancedOutputFilePath;

    public function __construct(private string $outputFilePath)
    {
        $this->makeAdvancedOutputFilePath();
    }

    private function makeAdvancedOutputFilePath()
    {
        $fileInfo =  new SplFileInfo($this->outputFilePath);

        $this->advancedOutputFilePath =  $fileInfo->getPath() . DIRECTORY_SEPARATOR . "advanced_" . $fileInfo->getFilename();
    }

    private function renameOutputFileCorrupted(SplFileObject $file): void
    {
        $fileExtension = "." . $file->getExtension();

        $oldFilePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();
        $newFilePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename($fileExtension) . "_corrupted" . $fileExtension;

        rename($oldFilePath, $newFilePath);
    }

    public function processReader(DataCasesCSVReader $reader)
    {
        // Processing data cases csv file
        $reader->process();
        $isCorrupted = $reader->getIsCorrupted();

        $this->writeToOutputFile($this->outputFilePath, $isCorrupted, $reader->getSummary());
        $this->writeToOutputFile($this->advancedOutputFilePath, $isCorrupted, $reader->getAdvancedSummary());
    }

    private function writeToOutputFile(string $outputFilePath, bool $isCorrupted, array $data)
    {
        // Check if any line contains corruption

        $outputFile = new SplFileObject($outputFilePath, "w");

        // Writing json data to corresponding output file
        $outputFile->fwrite(json_encode($data));

        // Upon having corrupted line in the csv change the output file name to corrupted
        if ($isCorrupted) {
            $this->renameOutputFileCorrupted($outputFile);
        }
    }

    public static function make(string $outputFilePath): self
    {
        return new DataCasesCSVWriter($outputFilePath);
    }
}
