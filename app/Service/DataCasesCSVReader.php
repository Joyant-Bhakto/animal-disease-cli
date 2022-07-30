<?php

namespace App\Service;

use SplFileObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Domain\DataCaseCSVRow;
use Illuminate\Support\Collection;

class DataCasesCSVReader
{
    private bool $isCorrupted = false;

    private int $totalCatNumber = 0;

    private int $totalCatMorbidity = 0;

    /**
     * @var array<int,string>
     */
    private array $diseases = [];

    private array $summary = [
        "total number of reported cases is" => 0,
        "total number of deaths reported at each location" => []
    ];

    private array $advancedSummary = [
        "Average number of sick cats reported in reports from villages up to two decimal points" => "0.00",
        "total number of deaths from each disease" => []
    ];

    public function __construct(private string $filePath)
    {
    }

    public function getIsCorrupted()
    {
        return $this->isCorrupted;
    }

    public function setDiseases(array $diseases): void
    {
        $this->diseases = $diseases;
    }

    public function process(): void
    {
        $inputFile = new SplFileObject($this->filePath);
        // Setting flags to skip empty lines, unnecessary newlines
        $inputFile->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_CSV);

        $headersCount = 0;
        // Skip headers
        if (!$inputFile->eof()) {
            $headers = $inputFile->fgetcsv();

            if ($headers !== false) {
                $headersCount = count($headers);
            }
        }

        // Read each line by line instead of loading the whole file into memory
        while (!$inputFile->eof() && ($line = $inputFile->fgetcsv()) !== false) {
            // If lines data count is less or greater than headers count then this line is corrupted
            if (count($line) < $headersCount) {
                $this->isCorrupted = true;
                continue;
            }

            // Encountered invalid line
            if (count($line) > $headersCount) {
                // Generate combination of each $headersCount elements and find one that matches valid format
                $line = collect($line)
                    ->sliding($headersCount)
                    ->first(function (Collection $row) {
                        return isValidDataCaseRow($row->values()->all());
                    }, null);


                $this->isCorrupted = true;

                if (is_null($line)) {
                    continue;
                }

                $line = $line
                    ->values()
                    ->toArray();
            }

            // making the current csv line into a wrapper object to easily interact with the data
            $dataCaseRow = DataCaseCSVRow::make($line);

            $this->processRowForSummary($dataCaseRow);

            $this->processRowForMortalityByDisease($dataCaseRow);

            $this->processRowForCatMorbidity($dataCaseRow);
        }

        $this->calculateAvgCatMorbidity();

        ksort($this->summary["total number of deaths reported at each location"]);
        ksort($this->advancedSummary["total number of deaths from each disease"]);
    }

    private function calculateAvgCatMorbidity()
    {
        if ($this->totalCatNumber > 0) {
            $avgCatMorbidity = ($this->totalCatMorbidity) / $this->totalCatNumber;
            $avgCatMorbidity = (string)$avgCatMorbidity;
            $avgCatMorbidity = number_format($avgCatMorbidity, 2);
            $this->advancedSummary["Average number of sick cats reported in reports from villages up to two decimal points"] = $avgCatMorbidity;
        }
    }

    private function processRowForCatMorbidity(DataCaseCSVRow $dataCaseRow): void
    {
        // Getting "location" name from csv data
        $location = $dataCaseRow->getLocationColumnValue();
        // Getting "number_morbidity" data from csv
        $numberMorbidity = $dataCaseRow->getNumberMorbidityColumnValue();

        if ($dataCaseRow->isCatSpecies() && Str::of($location)->test('/.*village.*/i')) {
            $this->totalCatMorbidity += $numberMorbidity;
            $this->totalCatNumber++;
        }
    }

    private function processRowForMortalityByDisease(DataCaseCSVRow $dataCaseRow): void
    {
        // Getting "disease_id" name from csv data
        $diseaseId = $dataCaseRow->getDiseaseIdColumnValue();
        // Getting "number_mortality" data from csv
        $numberMortality = $dataCaseRow->getNumberMortalityColumnValue();

        // Getting name of disease agianst the diseaseId
        $diseaseName = $this->diseases[$diseaseId];
        $deathRecordsByEachDisease = &$this->advancedSummary["total number of deaths from each disease"];

        // Checking to see if we had set this diseaseName to our summary. If not then add now
        if (!Arr::has($deathRecordsByEachDisease, $diseaseName)) {
            $deathRecordsByEachDisease[$diseaseName] = 0;
        }

        $deathRecordsByEachDisease[$diseaseName] += $numberMortality;
    }

    private function processRowForSummary(DataCaseCSVRow $dataCaseRow): void
    {
        // Getting "location" name from csv data
        $location = $dataCaseRow->getLocationColumnValue();
        // Getting "number_mortality" data from csv
        $numberMortality = $dataCaseRow->getNumberMortalityColumnValue();
        // Getting "total_number_cases" data from csv
        $totalNumberCases = $dataCaseRow->getTotalNumberCasesColumnValue();

        // Adding "total_number_cases" to summary
        $this->summary["total number of reported cases is"] += $totalNumberCases;
        $deathRecordsByLocation = &$this->summary["total number of deaths reported at each location"];

        // Checking to see if we had set this location in our summary. If not then add now
        if (!Arr::has($deathRecordsByLocation, $location)) {
            $deathRecordsByLocation[$location] = 0;
        }

        // Adding "number_mortality" to summary
        $deathRecordsByLocation[$location] += $numberMortality;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getAdvancedSummary()
    {
        return $this->advancedSummary;
    }

    public static function make(string $filePath, array $diseases): self
    {
        $reader = new DataCasesCSVReader($filePath);
        $reader->setDiseases($diseases);
        return $reader;
    }
}
