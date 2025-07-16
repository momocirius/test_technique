<?php

declare(strict_types=1);

namespace App\Lister;

final class JobsLister
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

    public function list(): array
    {
        return $this->db->query('SELECT id, reference, title, description, url, company_name, publication FROM job')->fetchAll(\PDO::FETCH_ASSOC);
    }
}
