<?php

declare(strict_types=1);

namespace Tests\Unit\Parser;

use App\Parser\JobTeaserJsonParser;
use App\Entity\Job;
use PHPUnit\Framework\TestCase;

final class JobTeaserJsonParserTest extends TestCase
{
    private JobTeaserJsonParser $parser;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->parser = new JobTeaserJsonParser();
        $this->fixturesPath = __DIR__ . '/../../fixtures/';
    }

    /** @test */
    public function it_parses_valid_json_file(): void
    {
        $jobs = $this->parser->parse($this->fixturesPath . 'jobteaser_sample.json');

        $this->assertCount(2, $jobs);
        $this->assertContainsOnlyInstancesOf(Job::class, $jobs);

        // Test premier job
        $firstJob = $jobs[0];
        $this->assertEquals('JT001', $firstJob->reference);
        $this->assertEquals('Chef de Projet Digital (H/F)', $firstJob->title);
        $this->assertEquals('WebAgency', $firstJob->companyName);
        $this->assertEquals('http://www.jobteaser.com/test/offer-1', $firstJob->url);
        $this->assertEquals('Mon Jan 15 10:30:00 CET 2024', $firstJob->publishedDate);
        $this->assertStringContainsString('chef de projet digital', $firstJob->description);

        // Test deuxième job
        $secondJob = $jobs[1];
        $this->assertEquals('JT002', $secondJob->reference);
        $this->assertEquals('Data Scientist Senior (H/F)', $secondJob->title);
        $this->assertEquals('FinanceAI', $secondJob->companyName);
    }

    /** @test */
    public function it_throws_exception_for_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Erreur de parsing JSON');

        $this->parser->parse($this->fixturesPath . 'invalid.json');
    }

    /** @test */
    public function it_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de lire le fichier');

        $this->parser->parse($this->fixturesPath . 'nonexistent.json');
    }

    /** @test */
    public function it_handles_missing_offers_array(): void
    {
        $jsonWithoutOffers = $this->fixturesPath . 'no_offers.json';
        file_put_contents($jsonWithoutOffers, '{"offerUrlPrefix": "http://test.com"}');

        try {
            $jobs = $this->parser->parse($jsonWithoutOffers);
            $this->assertEmpty($jobs);
        } finally {
            unlink($jsonWithoutOffers);
        }
    }

    /** @test */
    public function it_handles_missing_url_prefix(): void
    {
        $jsonWithoutPrefix = $this->fixturesPath . 'no_prefix.json';
        file_put_contents($jsonWithoutPrefix, '{"offers": [{"urlPath": "/test", "title": "Test", "reference": "T1", "publishedDate": "2024-01-01", "companyname": "Test"}]}');

        try {
            $jobs = $this->parser->parse($jsonWithoutPrefix);
            $this->assertCount(1, $jobs);
            $this->assertEquals('/test', $jobs[0]->url); // URL sans préfixe
        } finally {
            unlink($jsonWithoutPrefix);
        }
    }

    /** @test */
    public function it_builds_complete_urls(): void
    {
        $jobs = $this->parser->parse($this->fixturesPath . 'jobteaser_sample.json');

        foreach ($jobs as $job) {
            $this->assertStringStartsWith('http://www.jobteaser.com/', $job->url);
        }
    }
}
