<?php

declare(strict_types=1);

namespace App\Importer;

final class JobsImporter
{
    private \PDO $db;

    public function __construct(string $host, string $username, string $password, string $databaseName)
    {
        /* connect to DB */
        try {
            $this->db = new \PDO('mysql:host=' . $host . ';dbname=' . $databaseName, $username, $password);
        } catch (\Exception $e) {
            die('DB error: ' . $e->getMessage() . "\n");
        }
    }

    public function importJobs(string $file): int
    {
        /* remove existing items */
        $this->db->exec('DELETE FROM job');

        /* parse XML file */
        $xml = simplexml_load_file($file);

        /* import each item */
        $count = 0;
        foreach ($xml->item as $item) {
            $this->db->exec('INSERT INTO job (reference, title, description, url, company_name, publication) VALUES ('
                . '\'' . addslashes((string) $item->ref) . '\', '
                . '\'' . addslashes((string) $item->title) . '\', '
                . '\'' . addslashes((string) $item->description) . '\', '
                . '\'' . addslashes((string) $item->url) . '\', '
                . '\'' . addslashes((string) $item->company) . '\', '
                . '\'' . addslashes((string) $item->pubDate) . '\')'
            );
            $count++;
        }
        return $count;
    }
}
