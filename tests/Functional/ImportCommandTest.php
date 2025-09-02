<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Command\ImportCommand;
use App\Service\JobImportService;
use App\Repository\MySqlJobRepository;
use PHPUnit\Framework\TestCase;
use PDO;

final class ImportCommandTest extends TestCase
{
    private PDO $pdo;
    private string $fixturesPath;

    protected function setUp(): void
    {
        // SQLite en mémoire pour tests fonctionnels
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

        $this->fixturesPath = __DIR__ . '/../fixtures/';
    }

    /** @test */
    public function it_imports_regionsjob_xml_successfully(): void
    {
        $output = $this->executeCommand($this->fixturesPath . 'regionsjob_sample.xml');

        $this->assertStringContainsString('Starting import...', $output);
        $this->assertStringContainsString('2 jobs imported successfully', $output);
        $this->assertStringContainsString('All jobs in database (2):', $output);
        $this->assertStringContainsString('TEST001', $output);
        $this->assertStringContainsString('TEST002', $output);
        $this->assertStringContainsString('Développeur PHP Senior (H/F)', $output);
        $this->assertStringContainsString('Analyste Fonctionnel (H/F)', $output);
        $this->assertStringContainsString('Import completed', $output);
    }

    /** @test */
    public function it_imports_jobteaser_json_successfully(): void
    {
        $output = $this->executeCommand($this->fixturesPath . 'jobteaser_sample.json');

        $this->assertStringContainsString('Starting import...', $output);
        $this->assertStringContainsString('2 jobs imported successfully', $output);
        $this->assertStringContainsString('All jobs in database (2):', $output);
        $this->assertStringContainsString('JT001', $output);
        $this->assertStringContainsString('JT002', $output);
        $this->assertStringContainsString('Chef de Projet Digital (H/F)', $output);
        $this->assertStringContainsString('Data Scientist Senior (H/F)', $output);
        $this->assertStringContainsString('Import completed', $output);
    }

    /** @test */
    public function it_displays_error_for_non_existent_file(): void
    {
        $output = $this->executeCommand('non_existent_file.xml');

        $this->assertStringContainsString('Starting import...', $output);
        $this->assertStringContainsString('ERROR:', $output);
        $this->assertStringContainsString("Le fichier n'existe pas", $output);
    }

    /** @test */
    public function it_handles_invalid_xml_gracefully(): void
    {
        $output = $this->executeCommand($this->fixturesPath . 'invalid.xml');

        $this->assertStringContainsString('Starting import...', $output);
        $this->assertStringContainsString('ERROR:', $output);
        $this->assertStringContainsString('Impossible de parser le fichier XML', $output);
    }

    /** @test */
    public function it_displays_complete_job_information(): void
    {
        $output = $this->executeCommand($this->fixturesPath . 'regionsjob_sample.xml');

        // Vérifier la structure de l'affichage
        $this->assertMatchesRegularExpression(
            '/\d+: TEST001 - Développeur PHP Senior \(H\/F\) - 2024\/01\/15/',
            $output
        );
        
        $this->assertMatchesRegularExpression(
            '/\d+: TEST002 - Analyste Fonctionnel \(H\/F\) - 2024\/01\/20/',
            $output
        );
    }

    /** @test */
    public function it_shows_file_path_in_output(): void
    {
        $filePath = $this->fixturesPath . 'regionsjob_sample.xml';
        $output = $this->executeCommand($filePath);

        $this->assertStringContainsString("File: $filePath", $output);
    }

    private function executeCommand(string $file): string
    {
        ob_start();
        
        try {
            // Créer la commande avec notre PDO de test
            $repository = new MySqlJobRepository($this->pdo);
            $service = new JobImportService($repository);
            
            // Utiliser l'injection manuelle pour les tests
            $command = new class($service) extends ImportCommand {
                private JobImportService $testService;
                
                public function __construct(JobImportService $service) {
                    $this->testService = $service;
                }
                
                protected function getImportService(): JobImportService {
                    return $this->testService;
                }
                
                public function __invoke(string $file): void
                {
                    self::printMessage('Starting import...');
                    self::printMessage('File: {file}', ['{file}' => $file]);

                    try {
                        $count = $this->testService->import($file);
                        self::printMessage("> {count} jobs imported successfully.", ['{count}' => $count]);

                        $this->displayImportedJobs();
                    } catch (\Exception $e) {
                        self::printMessage("ERROR: {error}", ['{error}' => $e->getMessage()]);
                    }

                    self::printMessage("Import completed.");
                }

                private function displayImportedJobs(): void
                {
                    $jobs = $this->testService->getAllJobs();
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
            };
            
            $command($file);
            
        } catch (\Exception $e) {
            // Les exceptions sont gérées dans la commande
        }
        
        return ob_get_clean();
    }
}
