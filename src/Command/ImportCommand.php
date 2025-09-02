<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\JobImportService;
use App\Repository\MySqlJobRepository;
use App\Factory\DatabaseConnectionFactory;

class ImportCommand
{
    private JobImportService $importService;

    public function __construct()
    {
        // Dependency Injection manuelle (en attendant un DI Container)
        $pdo = DatabaseConnectionFactory::createFromGlobals();
        $repository = new MySqlJobRepository($pdo);
        $this->importService = new JobImportService($repository);
    }

    public function __invoke(string $file): void
    {
        self::printMessage('Starting import...');
        self::printMessage('File: {file}', ['{file}' => $file]);

        try {
            $count = $this->importService->import($file);
            self::printMessage("> {count} jobs imported successfully.", ['{count}' => $count]);

            $this->displayImportedJobs();
        } catch (\Exception $e) {
            self::printMessage("ERROR: {error}", ['{error}' => $e->getMessage()]);
            throw $e;
        }

        self::printMessage("Import completed.");
    }

    private function displayImportedJobs(): void
    {
        $jobs = $this->importService->getAllJobs();
        $totalJobs = count($jobs);

        self::printMessage("> All jobs in database ({count}):", ['{count}' => $totalJobs]);
        
        foreach ($jobs as $job) {
            self::printMessage(" {id}: {reference} - {title} - {publication}", [
                '{id}' => $job->id ?? 'N/A',
                '{reference}' => $job->reference,
                '{title}' => $job->title,
                '{publication}' => $job->publishedDate
            ]);
        }
    }

    private static function printMessage(string $message, array $messageParameters = []): void
    {
        echo strtr($message."\n", $messageParameters);
    }
}
