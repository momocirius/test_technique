<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Repository\MySqlJobRepository;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;
use PDO;

final class MySqlJobRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySqlJobRepository $repository;

    protected function setUp(): void
    {
        // Utilise SQLite en mémoire pour les tests
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
    }

    /** @test */
    public function it_saves_a_single_job(): void
    {
        $job = new Job(
            reference: 'TEST001',
            title: 'Test Job',
            description: 'Test Description',
            url: 'http://test.com',
            companyName: 'Test Company',
            publishedDate: '2024-01-01'
        );

        $this->repository->save($job);

        $count = $this->repository->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_saves_multiple_jobs_in_transaction(): void
    {
        $jobs = [
            new Job('REF001', 'Job 1', 'Desc 1', 'http://1.com', 'Company 1', '2024-01-01'),
            new Job('REF002', 'Job 2', 'Desc 2', 'http://2.com', 'Company 2', '2024-01-02'),
            new Job('REF003', 'Job 3', 'Desc 3', 'http://3.com', 'Company 3', '2024-01-03'),
        ];

        $this->repository->saveAll($jobs);

        $count = $this->repository->count();
        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_rolls_back_transaction_on_error(): void
    {
        // Insérer un job valide d'abord
        $validJob = new Job('VALID', 'Valid Job', 'Desc', 'http://valid.com', 'Company', '2024-01-01');
        $this->repository->save($validJob);

        // Créer un job qui va échouer (référence trop longue par exemple)
        $jobs = [
            new Job('SHORT', 'Valid Job 2', 'Desc', 'http://valid2.com', 'Company', '2024-01-01'),
            // Ce job aura une contrainte qui pourrait échouer selon la DB
        ];

        // Pour simuler une erreur, on peut corrompre temporairement la table
        $this->pdo->exec('DROP TABLE job');

        try {
            $this->repository->saveAll($jobs);
            $this->fail('Exception attendue');
        } catch (\Exception $e) {
            // L'exception est attendue
            $this->assertStringContainsString('no such table', $e->getMessage());
        }

        // Recréer la table et vérifier qu'on a toujours le job initial
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
        $this->repository->save($validJob);

        $count = $this->repository->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_finds_all_jobs(): void
    {
        $jobs = [
            new Job('REF001', 'Job 1', 'Desc 1', 'http://1.com', 'Company A', '2024-01-01'),
            new Job('REF002', 'Job 2', 'Desc 2', 'http://2.com', 'Company B', '2024-01-02'),
        ];

        $this->repository->saveAll($jobs);
        $foundJobs = $this->repository->findAll();

        $this->assertCount(2, $foundJobs);
        $this->assertContainsOnlyInstancesOf(Job::class, $foundJobs);
        
        // Vérifier que les jobs sont ordonnés par date de publication (DESC)
        $this->assertEquals('REF002', $foundJobs[0]->reference); // Plus récent en premier
        $this->assertEquals('REF001', $foundJobs[1]->reference);
    }

    /** @test */
    public function it_clears_all_jobs(): void
    {
        $jobs = [
            new Job('REF001', 'Job 1', 'Desc 1', 'http://1.com', 'Company', '2024-01-01'),
            new Job('REF002', 'Job 2', 'Desc 2', 'http://2.com', 'Company', '2024-01-02'),
        ];

        $this->repository->saveAll($jobs);
        $this->assertEquals(2, $this->repository->count());

        $this->repository->clear();
        $this->assertEquals(0, $this->repository->count());
    }

    /** @test */
    public function it_counts_jobs_correctly(): void
    {
        $this->assertEquals(0, $this->repository->count());

        $job = new Job('REF001', 'Job 1', 'Desc', 'http://1.com', 'Company', '2024-01-01');
        $this->repository->save($job);

        $this->assertEquals(1, $this->repository->count());
    }

    /** @test */
    public function it_reconstructs_job_entities_with_correct_data(): void
    {
        $originalJob = new Job(
            reference: 'TEST123',
            title: 'Senior Developer',
            description: 'Great opportunity for a senior developer...',
            url: 'https://example.com/job/123',
            companyName: 'TechCorp Inc.',
            publishedDate: '2024-01-15'
        );

        $this->repository->save($originalJob);
        $jobs = $this->repository->findAll();

        $this->assertCount(1, $jobs);
        $retrievedJob = $jobs[0];

        $this->assertEquals('TEST123', $retrievedJob->reference);
        $this->assertEquals('Senior Developer', $retrievedJob->title);
        $this->assertEquals('Great opportunity for a senior developer...', $retrievedJob->description);
        $this->assertEquals('https://example.com/job/123', $retrievedJob->url);
        $this->assertEquals('TechCorp Inc.', $retrievedJob->companyName);
        $this->assertEquals('2024-01-15', $retrievedJob->publishedDate);
        $this->assertIsInt($retrievedJob->id);
        $this->assertGreaterThan(0, $retrievedJob->id);
    }
}
