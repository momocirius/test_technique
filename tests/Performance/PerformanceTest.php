<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Service\JobImportService;
use App\Repository\MySqlJobRepository;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Tests de performance pour valider les exigences de vitesse
 * 
 * @group performance
 */
final class PerformanceTest extends TestCase
{
    private PDO $pdo;
    private MySqlJobRepository $repository;
    private JobImportService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->pdo->exec("
            CREATE TABLE job (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                url TEXT,
                company_name TEXT,
                publication TEXT
            )
        ");

        $this->repository = new MySqlJobRepository($this->pdo);
        $this->service = new JobImportService($this->repository);
    }

    /** @test */
    public function it_imports_1000_jobs_within_time_limit(): void
    {
        $largeDataset = $this->generateLargeJobDataset(1000);
        
        $startTime = microtime(true);
        
        $this->service->import($largeDataset);
        
        $executionTime = microtime(true) - $startTime;
        
        // Doit traiter 1000 jobs en moins de 5 secondes
        $this->assertLessThan(5.0, $executionTime, 
            "Import de 1000 jobs devrait prendre moins de 5 secondes, pris: {$executionTime}s"
        );
        
        $this->assertEquals(1000, $this->service->getTotalJobs());
        
        unlink($largeDataset);
    }

    /** @test */
    public function it_handles_memory_efficiently_with_large_datasets(): void
    {
        $memoryBefore = memory_get_usage(true);
        
        $largeDataset = $this->generateLargeJobDataset(5000);
        
        $this->service->import($largeDataset);
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        
        // Ne devrait pas utiliser plus de 50MB pour 5000 jobs
        $this->assertLessThan(50, $memoryUsed,
            "Import de 5000 jobs ne devrait pas utiliser plus de 50MB, utilisé: {$memoryUsed}MB"
        );
        
        $this->assertEquals(5000, $this->service->getTotalJobs());
        
        unlink($largeDataset);
    }

    /** @test */
    public function it_processes_mixed_format_files_efficiently(): void
    {
        $xmlFile = $this->generateLargeXmlDataset(500);
        $jsonFile = $this->generateLargeJsonDataset(500);
        
        $startTime = microtime(true);
        
        $this->service->import($xmlFile);
        $this->service->append($jsonFile);
        
        $executionTime = microtime(true) - $startTime;
        
        // Import mixte de 1000 jobs (XML + JSON) en moins de 3 secondes
        $this->assertLessThan(3.0, $executionTime,
            "Import mixte de 1000 jobs devrait prendre moins de 3 secondes, pris: {$executionTime}s"
        );
        
        $this->assertEquals(1000, $this->service->getTotalJobs());
        
        unlink($xmlFile);
        unlink($jsonFile);
    }

    /** @test */
    public function it_retrieves_jobs_quickly(): void
    {
        // Préparer un dataset de taille moyenne
        $dataset = $this->generateLargeJobDataset(2000);
        $this->service->import($dataset);
        
        $startTime = microtime(true);
        
        $jobs = $this->service->getAllJobs();
        
        $executionTime = microtime(true) - $startTime;
        
        // Récupération de 2000 jobs en moins de 1 seconde
        $this->assertLessThan(1.0, $executionTime,
            "Récupération de 2000 jobs devrait prendre moins de 1 seconde, pris: {$executionTime}s"
        );
        
        $this->assertCount(2000, $jobs);
        $this->assertContainsOnlyInstancesOf(Job::class, $jobs);
        
        unlink($dataset);
    }

    private function generateLargeJobDataset(int $count): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'large_jobs_') . '.json';
        
        $data = [
            'offerUrlPrefix' => 'http://test.com',
            'offers' => []
        ];
        
        for ($i = 1; $i <= $count; $i++) {
            $data['offers'][] = [
                'urlPath' => "/job-{$i}",
                'title' => "Job Title {$i}",
                'description' => "Description for job {$i} with some additional text to make it realistic. This job offers great opportunities for professional growth and development in a dynamic environment.",
                'reference' => "REF{$i}",
                'publishedDate' => "Mon Jan " . (($i % 30) + 1) . " 10:30:00 CET 2024",
                'companyname' => "Company " . (($i % 100) + 1)
            ];
        }
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filename;
    }

    private function generateLargeXmlDataset(int $count): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'large_xml_jobs_') . '.xml';
        
        $xml = '<?xml version="1.0" encoding="utf-8"?><jobs>';
        
        for ($i = 1; $i <= $count; $i++) {
            $xml .= "<item>";
            $xml .= "<ref>XML{$i}</ref>";
            $xml .= "<title><![CDATA[XML Job Title {$i}]]></title>";
            $xml .= "<description><![CDATA[XML Description for job {$i} with detailed information.]]></description>";
            $xml .= "<url>http://xml-test.com/job-{$i}</url>";
            $xml .= "<company><![CDATA[XML Company " . (($i % 50) + 1) . "]]></company>";
            $xml .= "<pubDate>2024/" . str_pad((string)(($i % 12) + 1), 2, '0', STR_PAD_LEFT) . "/" . str_pad((string)(($i % 28) + 1), 2, '0', STR_PAD_LEFT) . "</pubDate>";
            $xml .= "</item>";
        }
        
        $xml .= '</jobs>';
        
        file_put_contents($filename, $xml);
        
        return $filename;
    }

    private function generateLargeJsonDataset(int $count): string
    {
        return $this->generateLargeJobDataset($count);
    }
}
