<?php

namespace App\Domain;

use App\Constants\AnimalSpecies;
use App\Constants\DataCaseColumns;

class DataCaseCSVRow
{
    public function __construct(private array $line)
    {
    }

    public function getSpeciesColumnValue(): string
    {
        return $this->line[DataCaseColumns::SPECIES_COL];
    }

    // Getting "location" name from csv data
    public function getLocationColumnValue(): string
    {
        return $this->line[DataCaseColumns::LOCATION_COL];
    }

    // Getting "disease_id" name from csv data
    public function getDiseaseIdColumnValue(): int
    {
        return $this->line[DataCaseColumns::DISEASE_ID_COL];
    }

    // Getting "number_mortality" data from csv
    public function getNumberMortalityColumnValue(): int
    {
        return (int)$this->line[DataCaseColumns::NUMBER_MORTALITY_COL];
    }

    // Getting "number_morbidity" data from csv
    public function getNumberMorbidityColumnValue(): int
    {
        return (int)$this->line[DataCaseColumns::NUMBER_MORBIDITY_COL];
    }

    // Getting "total_number_cases" data from csv
    public function getTotalNumberCasesColumnValue(): int
    {
        return (int)$this->line[DataCaseColumns::TOTAL_NUMBER_CASES_COL];
    }

    public function isCatSpecies(): bool
    {
        return $this->getSpeciesColumnValue() === AnimalSpecies::CAT;
    }

    public static function make(array $line): self
    {
        return new DataCaseCSVRow($line);
    }
}
