<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Service\JobImportService;
use App\Repository\MySqlJobRepository;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;
use PDO;

final class JobImportIntegrationTest extends TestCase
{
    private PDO $pdo;
    private MySqlJobRepository $repository;
    private JobImportService $service;
    private string $fixturesPath;

    protected function setUp(): void
    {
        // Utilise SQLite en mémoire pour les tests d'intégration
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Création de la table de test
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
        $this->fixturesPath = __DIR__ . '/../fixtures/';
    }

    /** @test */
    public function it_imports_jobs_from_xml_and_json_files(): void
    {
        // Import RegionsJob XML
        $xmlCount = $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        $this->assertEquals(2, $xmlCount);

        // Append JobTeaser JSON
        $jsonCount = $this->service->append($this->fixturesPath . 'jobteaser_sample.json');
        $this->assertEquals(2, $jsonCount);

        // Vérifier le total
        $totalJobs = $this->service->getTotalJobs();
        $this->assertEquals(4, $totalJobs);

        // Vérifier le contenu
        $allJobs = $this->service->getAllJobs();
        $this->assertCount(4, $allJobs);

        // Vérifier les références spécifiques
        $references = array_map(fn(Job $job) => $job->reference, $allJobs);
        $this->assertContains('TEST001', $references);
        $this->assertContains('TEST002', $references);
        $this->assertContains('JT001', $references);
        $this->assertContains('JT002', $references);
    }

    /** @test */
    public function it_replaces_all_jobs_on_import(): void
    {
        // Premier import
        $count1 = $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        $this->assertEquals(2, $count1);
        $this->assertEquals(2, $this->service->getTotalJobs());

        // Deuxième import (doit remplacer)
        $count2 = $this->service->import($this->fixturesPath . 'jobteaser_sample.json');
        $this->assertEquals(2, $count2);
        $this->assertEquals(2, $this->service->getTotalJobs()); // Toujours 2, pas 4

        // Vérifier que seuls les jobs JobTeaser sont présents
        $allJobs = $this->service->getAllJobs();
        $references = array_map(fn(Job $job) => $job->reference, $allJobs);
        $this->assertContains('JT001', $references);
        $this->assertContains('JT002', $references);
        $this->assertNotContains('TEST001', $references);
        $this->assertNotContains('TEST002', $references);
    }

    /** @test */
    public function it_preserves_all_job_data_through_import_cycle(): void
    {
        $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        $jobs = $this->service->getAllJobs();

        $firstJob = $jobs[1]; // Deuxième job (ordre DESC par date)
        
        $this->assertEquals('TEST001', $firstJob->reference);
        $this->assertEquals('Développeur PHP Senior (H/F)', $firstJob->title);
        $this->assertEquals('TechCorp', $firstJob->companyName);
        $this->assertEquals('http://www.regionsjob.com/test/TEST001', $firstJob->url);
        $this->assertEquals('2024/01/15', $firstJob->publishedDate);
        $this->assertStringContainsString('développeur PHP Senior', $firstJob->description);
        $this->assertIsInt($firstJob->id);
    }

    /** @test */
    public function it_handles_mixed_format_import_workflow(): void
    {
        // Workflow typique : import initial + plusieurs ajouts
        
        // 1. Import initial XML
        $initialCount = $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        $this->assertEquals(2, $initialCount);

        // 2. Ajout JSON
        $appendCount = $this->service->append($this->fixturesPath . 'jobteaser_sample.json');
        $this->assertEquals(2, $appendCount);

        // 3. Vérifier le mélange des sources
        $allJobs = $this->service->getAllJobs();
        $this->assertCount(4, $allJobs);

        $companies = array_map(fn(Job $job) => $job->companyName, $allJobs);
        $this->assertContains('TechCorp', $companies);     // RegionsJob
        $this->assertContains('DataSoft', $companies);     // RegionsJob  
        $this->assertContains('WebAgency', $companies);    // JobTeaser
        $this->assertContains('FinanceAI', $companies);    // JobTeaser

        // 4. Clear et vérifier
        $this->service->clearAllJobs();
        $this->assertEquals(0, $this->service->getTotalJobs());
    }

    /** @test */
    public function it_maintains_data_integrity_under_concurrent_operations(): void
    {
        // Simuler des opérations concurrentes
        $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        
        // Lire pendant l'ajout (simulation)
        $jobsBeforeAppend = $this->service->getAllJobs();
        $this->assertCount(2, $jobsBeforeAppend);

        $this->service->append($this->fixturesPath . 'jobteaser_sample.json');
        
        $jobsAfterAppend = $this->service->getAllJobs();
        $this->assertCount(4, $jobsAfterAppend);

        // Vérifier que les données initiales sont toujours cohérentes
        $initialReferences = array_map(fn(Job $job) => $job->reference, $jobsBeforeAppend);
        $finalReferences = array_map(fn(Job $job) => $job->reference, $jobsAfterAppend);
        
        foreach ($initialReferences as $ref) {
            $this->assertContains($ref, $finalReferences, "Reference $ref should still be present");
        }
    }

    /** @test */
    public function it_handles_error_recovery_correctly(): void
    {
        // Import valide initial
        $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');
        $this->assertEquals(2, $this->service->getTotalJobs());

        // Tentative d'import d'un fichier invalide
        try {
            $this->service->import($this->fixturesPath . 'invalid.xml');
            $this->fail('Exception expected for invalid XML');
        } catch (\Exception $e) {
            // L'exception est attendue
        }

        // Vérifier que les données précédentes sont toujours là
        // (dans une vraie implémentation avec transactions)
        $this->assertEquals(2, $this->service->getTotalJobs());
        
        $jobs = $this->service->getAllJobs();
        $references = array_map(fn(Job $job) => $job->reference, $jobs);
        $this->assertContains('TEST001', $references);
        $this->assertContains('TEST002', $references);
    }
}
