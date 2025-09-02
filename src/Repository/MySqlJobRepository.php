<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Job;

class MySqlJobRepository implements JobRepositoryInterface
{
    public function __construct(private \PDO $db)
    {
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function save(Job $job): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO job (reference, title, description, url, company_name, publication) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $job->reference,
            $job->title,
            $job->description,
            $job->url,
            $job->companyName,
            $job->publishedDate
        ]);
    }

    public function saveAll(array $jobs): void
    {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO job (reference, title, description, url, company_name, publication) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            foreach ($jobs as $job) {
                $stmt->execute([
                    $job->reference,
                    $job->title,
                    $job->description,
                    $job->url,
                    $job->companyName,
                    $job->publishedDate
                ]);
            }
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('
            SELECT id, reference, title, description, url, company_name, publication 
            FROM job 
            ORDER BY publication DESC
        ');
        
        $jobs = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $jobs[] = new Job(
                reference: $row['reference'],
                title: $row['title'],
                description: $row['description'],
                url: $row['url'],
                companyName: $row['company_name'],
                publishedDate: $row['publication'],
                id: (int) $row['id']
            );
        }
        
        return $jobs;
    }

    public function clear(): void
    {
        $this->db->exec('DELETE FROM job');
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM job');
        return (int) $stmt->fetchColumn();
    }
}
