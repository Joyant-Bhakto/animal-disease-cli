<?php

namespace App\Commands;

use SplFileObject;
use RuntimeException;
use App\Service\DataCasesCSVReader;
use App\Service\DataCasesCSVWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ProcessCsv extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'process:csv {cases-path : The data cases file to process} {diseases-path= : The file containing disease list} {output-path= : The json file to store statistics}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'A command to read csv file and generate summary output';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $diseases = $this->mapDiseaseIdAndName();

        /**
         * @var RuntimeException $exception
         */
        $exception = null;

        $this->task("Processing data cases", function () use ($diseases, &$exception) {
            if (empty($diseases)) {
                return false;
            }

            try {
                /** @var string $inputFileName */
                $dataCasesFilePath = $this->argument("cases-path");
                /** @var string $outputFileName */
                $outputFilePath = $this->argument("output-path");

                $reader =  DataCasesCSVReader::make($dataCasesFilePath, $diseases);
                $writer = DataCasesCSVWriter::make($outputFilePath);
                $writer->processReader($reader);
            } catch (\Throwable $th) {
                $exception = $th;
                // Log::error($th);
                return false;
            }
        });

        if ($exception instanceof RuntimeException) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * @return array<int,string>
     */
    private function mapDiseaseIdAndName(): array
    {
        $diseaseMap = [];

        /**
         * @var RuntimeException $exception
         */
        $exception = null;

        // $this->task will output beautiful feedback on console
        // ex: "Processing disease list: ✔" when success 
        // "Processing disease list: failed" when failure
        $this->task("Processing disease list", function () use (&$diseaseMap, &$exception) {
            try {
                // Getting disease list file path argument
                $diseaseListFilePath = $this->argument("diseases-path");

                // Initializing the file processing object
                $diseaseListFile = new SplFileObject($diseaseListFilePath);
                // Setting flags to skip empty lines, unnecessary newlines
                $diseaseListFile->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_CSV);

                // Skip headers of csv
                if (!$diseaseListFile->eof()) {
                    $diseaseListFile->fgetcsv();
                }

                // Read each line by line instead of loading the whole file into memory
                while (!$diseaseListFile->eof() && ($line = $diseaseListFile->fgetcsv()) !== false) {
                    // Each line will contain id of the disease in the first index and name of that disease in second index
                    // ex: [6, "Worm Infestation"]
                    [$diseaseId, $diseaseName] = $line;
                    // Mapping disease id and name into dictionary
                    // ex: [6 => "Worm Infestation"]
                    $diseaseMap[$diseaseId] = $diseaseName;
                }
            } catch (\Throwable $th) {
                $exception  = $th;
                // Log::error($th->getMessage());
                return false;
            }
        });

        if ($exception instanceof RuntimeException) {
            $this->error($exception->getMessage());
        } else if (empty($diseaseMap)) {
            $this->warn("!!Disease list is empty!!");
        }

        return $diseaseMap;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
