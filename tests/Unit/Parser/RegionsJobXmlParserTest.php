<?php

declare(strict_types=1);

namespace Tests\Unit\Parser;

use App\Parser\RegionsJobXmlParser;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;

final class RegionsJobXmlParserTest extends TestCase
{
    private RegionsJobXmlParser $parser;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->parser = new RegionsJobXmlParser();
        $this->fixturesPath = __DIR__ . '/../../fixtures/';
    }

    /** @test */
    public function it_parses_valid_xml_file(): void
    {
        $jobs = $this->parser->parse($this->fixturesPath . 'regionsjob_sample.xml');

        $this->assertCount(2, $jobs);
        $this->assertContainsOnlyInstancesOf(Job::class, $jobs);

        // Test premier job
        $firstJob = $jobs[0];
        $this->assertEquals('TEST001', $firstJob->reference);
        $this->assertEquals('Développeur PHP Senior (H/F)', $firstJob->title);
        $this->assertEquals('TechCorp', $firstJob->companyName);
        $this->assertEquals('http://www.regionsjob.com/test/TEST001', $firstJob->url);
        $this->assertEquals('2024/01/15', $firstJob->publishedDate);
        $this->assertStringContainsString('développeur PHP Senior', $firstJob->description);

        // Test deuxième job
        $secondJob = $jobs[1];
        $this->assertEquals('TEST002', $secondJob->reference);
        $this->assertEquals('Analyste Fonctionnel (H/F)', $secondJob->title);
        $this->assertEquals('DataSoft', $secondJob->companyName);
    }

    /** @test */
    public function it_throws_exception_for_invalid_xml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de parser le fichier XML');

        $this->parser->parse($this->fixturesPath . 'invalid.xml');
    }

    /** @test */
    public function it_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parse($this->fixturesPath . 'nonexistent.xml');
    }

    /** @test */
    public function it_handles_empty_xml_file(): void
    {
        $emptyXmlPath = $this->fixturesPath . 'empty.xml';
        file_put_contents($emptyXmlPath, '<?xml version="1.0"?><jobs></jobs>');

        try {
            $jobs = $this->parser->parse($emptyXmlPath);
            $this->assertEmpty($jobs);
        } finally {
            unlink($emptyXmlPath);
        }
    }

    /** @test */
    public function it_handles_special_characters_in_cdata(): void
    {
        $jobs = $this->parser->parse($this->fixturesPath . 'regionsjob_sample.xml');
        
        // Vérifier que les caractères spéciaux sont correctement décodés
        $firstJob = $jobs[0];
        $this->assertStringContainsString('<p>', $firstJob->description);
        $this->assertStringContainsString('(H/F)', $firstJob->title);
    }
}
