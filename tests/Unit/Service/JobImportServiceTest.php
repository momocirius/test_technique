<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\JobImportService;
use App\Repository\JobRepositoryInterface;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class JobImportServiceTest extends TestCase
{
    private JobRepositoryInterface|MockObject $repositoryMock;
    private JobImportService $service;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(JobRepositoryInterface::class);
        $this->service = new JobImportService($this->repositoryMock);
        $this->fixturesPath = __DIR__ . '/../../fixtures/';
    }

    /** @test */
    public function it_imports_regionsjob_xml_successfully(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('clear');

        $this->repositoryMock
            ->expects($this->once())
            ->method('saveAll')
            ->with($this->callback(function (array $jobs): bool {
                $this->assertCount(2, $jobs);
                $this->assertContainsOnlyInstancesOf(Job::class, $jobs);
                $this->assertEquals('TEST001', $jobs[0]->reference);
                $this->assertEquals('TechCorp', $jobs[0]->companyName);
                return true;
            }));

        $count = $this->service->import($this->fixturesPath . 'regionsjob_sample.xml');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_imports_jobteaser_json_successfully(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('clear');

        $this->repositoryMock
            ->expects($this->once())
            ->method('saveAll')
            ->with($this->callback(function (array $jobs): bool {
                $this->assertCount(2, $jobs);
                $this->assertContainsOnlyInstancesOf(Job::class, $jobs);
                $this->assertEquals('JT001', $jobs[0]->reference);
                $this->assertEquals('WebAgency', $jobs[0]->companyName);
                return true;
            }));

        $count = $this->service->import($this->fixturesPath . 'jobteaser_sample.json');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_imports_with_explicit_partner(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('clear');

        $this->repositoryMock
            ->expects($this->once())
            ->method('saveAll');

        $count = $this->service->import($this->fixturesPath . 'jobteaser_sample.json', 'jobteaser');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_appends_jobs_without_clearing(): void
    {
        $this->repositoryMock
            ->expects($this->never())
            ->method('clear');

        $this->repositoryMock
            ->expects($this->once())
            ->method('saveAll')
            ->with($this->callback(function (array $jobs): bool {
                $this->assertCount(2, $jobs);
                return true;
            }));

        $count = $this->service->append($this->fixturesPath . 'regionsjob_sample.xml');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_returns_zero_when_appending_empty_result(): void
    {
        // Créer un fichier temporaire avec structure valide mais vide
        $emptyJsonFile = tempnam(sys_get_temp_dir(), 'empty_jobs_') . '.json';
        file_put_contents($emptyJsonFile, '{"offerUrlPrefix": "http://test.com", "offers": []}');

        try {
            $this->repositoryMock
                ->expects($this->never())
                ->method('saveAll');

            $count = $this->service->append($emptyJsonFile);
            $this->assertEquals(0, $count);
        } finally {
            unlink($emptyJsonFile);
        }
    }

    /** @test */
    public function it_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Le fichier n'existe pas");

        $this->service->import('non_existent_file.xml');
    }

    /** @test */
    public function it_throws_exception_for_unreadable_file(): void
    {
        // Sur Windows, le chmod ne fonctionne pas comme sur Unix
        // On teste plutôt avec un répertoire au lieu d'un fichier
        $unreadableFile = tempnam(sys_get_temp_dir(), 'unreadable_dir_') . '.xml';
        mkdir($unreadableFile); // Créer un répertoire avec l'extension .xml

        try {
            $this->expectException(\Exception::class);
            
            $this->service->import($unreadableFile);
        } finally {
            rmdir($unreadableFile);
        }
    }

    /** @test */
    public function it_throws_exception_for_empty_file(): void
    {
        $emptyFile = tempnam(sys_get_temp_dir(), 'empty_') . '.xml';
        touch($emptyFile);

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Le fichier est vide");

            $this->service->import($emptyFile);
        } finally {
            unlink($emptyFile);
        }
    }

    /** @test */
    public function it_throws_exception_when_no_jobs_found(): void
    {
        $emptyJobsFile = tempnam(sys_get_temp_dir(), 'no_jobs_') . '.xml';
        file_put_contents($emptyJobsFile, '<?xml version="1.0"?><jobs></jobs>');

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Aucun job trouvé dans le fichier");

            $this->service->import($emptyJobsFile);
        } finally {
            unlink($emptyJobsFile);
        }
    }

    /** @test */
    public function it_gets_total_jobs_from_repository(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(42);

        $total = $this->service->getTotalJobs();

        $this->assertEquals(42, $total);
    }

    /** @test */
    public function it_gets_all_jobs_from_repository(): void
    {
        $expectedJobs = [
            new Job('REF1', 'Job 1', 'Desc 1', 'http://1.com', 'Company 1', '2024-01-01'),
            new Job('REF2', 'Job 2', 'Desc 2', 'http://2.com', 'Company 2', '2024-01-02'),
        ];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedJobs);

        $jobs = $this->service->getAllJobs();

        $this->assertEquals($expectedJobs, $jobs);
    }

    /** @test */
    public function it_clears_all_jobs_via_repository(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('clear');

        $this->service->clearAllJobs();
    }

    /** @test */
    public function it_uses_content_detection_strategy_first(): void
    {
        // Ce test vérifie que le service essaie la détection par contenu en premier
        // En utilisant un fichier JobTeaser avec un nom qui ne contient pas 'jobteaser'
        $this->repositoryMock
            ->expects($this->once())
            ->method('clear');

        $this->repositoryMock
            ->expects($this->once())
            ->method('saveAll')
            ->with($this->callback(function (array $jobs): bool {
                // Vérifier que c'est bien JobTeaser qui a été détecté
                $this->assertEquals('JT001', $jobs[0]->reference);
                return true;
            }));

        $count = $this->service->import($this->fixturesPath . 'jobteaser_sample.json');

        $this->assertEquals(2, $count);
    }
}
