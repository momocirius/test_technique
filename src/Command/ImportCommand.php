<?php

declare(strict_types=1);

namespace App\Command;

use App\Importer\JobsImporter;
use App\Lister\JobsLister;

final class ImportCommand
{
    public function __invoke(string $file): void
    {
        self::printMessage('Starting...');

        $importer = new JobsImporter(SQL_HOST, SQL_USER, SQL_PWD, SQL_DB, $file);
        $count = $importer->importJobs($file);

        self::printMessage("> {count} jobs imported.", ['{count}' => $count]);

        $jobsLister = new JobsLister(SQL_HOST, SQL_USER, SQL_PWD, SQL_DB);
        $jobs = $jobsLister->list();

        self::printMessage("> all jobs ({count}):", ['{count}' => count($jobs)]);
        foreach ($jobs as $job) {
            self::printMessage(" {id}: {reference} - {title} - {publication}", [
                '{id}' => $job['id'],
                '{reference}' => $job['reference'],
                '{title}' => $job['title'],
                '{publication}' => $job['publication']
            ]);
        }

        self::printMessage("Terminating...");
    }

    private static function printMessage(string $message, array $messageParameters = []): void
    {
        echo strtr($message."\n", $messageParameters);
    }
}
