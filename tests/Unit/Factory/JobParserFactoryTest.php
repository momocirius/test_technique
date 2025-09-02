<?php

declare(strict_types=1);

namespace Tests\Unit\Factory;

use App\Factory\JobParserFactory;
use App\Parser\RegionsJobXmlParser;
use App\Parser\JobTeaserJsonParser;
use PHPUnit\Framework\TestCase;

final class JobParserFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_regionsjob_parser(): void
    {
        $parser = JobParserFactory::create('regionsjob');

        $this->assertInstanceOf(RegionsJobXmlParser::class, $parser);
    }

    /** @test */
    public function it_creates_jobteaser_parser(): void
    {
        $parser = JobParserFactory::create('jobteaser');

        $this->assertInstanceOf(JobTeaserJsonParser::class, $parser);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_partner(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Partenaire non supporté: unknown');

        JobParserFactory::create('unknown');
    }

    /** @test */
    public function it_creates_parser_by_extension(): void
    {
        $xmlParser = JobParserFactory::createByExtension('xml');
        $jsonParser = JobParserFactory::createByExtension('json');

        $this->assertInstanceOf(RegionsJobXmlParser::class, $xmlParser);
        $this->assertInstanceOf(JobTeaserJsonParser::class, $jsonParser);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_extension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Extension non supportée: csv');

        JobParserFactory::createByExtension('csv');
    }

    /** @test */
    public function it_detects_partner_from_filename(): void
    {
        $regionsjobParser = JobParserFactory::createFromFile('regionsjob-export.xml');
        $jobteaserParser = JobParserFactory::createFromFile('jobteaser-data.json');

        $this->assertInstanceOf(RegionsJobXmlParser::class, $regionsjobParser);
        $this->assertInstanceOf(JobTeaserJsonParser::class, $jobteaserParser);
    }

    /** @test */
    public function it_falls_back_to_extension_when_partner_not_detected(): void
    {
        $xmlParser = JobParserFactory::createFromFile('unknown-file.xml');
        $jsonParser = JobParserFactory::createFromFile('unknown-file.json');

        $this->assertInstanceOf(RegionsJobXmlParser::class, $xmlParser);
        $this->assertInstanceOf(JobTeaserJsonParser::class, $jsonParser);
    }

    /** @test */
    public function it_detects_jobteaser_from_content(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_jobteaser_') . '.json';
        file_put_contents($tempFile, '{"offerUrlPrefix": "http://test.com", "offers": []}');

        try {
            $parser = JobParserFactory::createFromContent($tempFile);
            $this->assertInstanceOf(JobTeaserJsonParser::class, $parser);
        } finally {
            unlink($tempFile);
        }
    }

    /** @test */
    public function it_detects_regionsjob_from_content(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_regionsjob_') . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><jobs><item><ref>TEST</ref></item></jobs>');

        try {
            $parser = JobParserFactory::createFromContent($tempFile);
            $this->assertInstanceOf(RegionsJobXmlParser::class, $parser);
        } finally {
            unlink($tempFile);
        }
    }

    /** @test */
    public function it_returns_supported_partners(): void
    {
        $partners = JobParserFactory::getSupportedPartners();

        $this->assertIsArray($partners);
        $this->assertContains('regionsjob', $partners);
        $this->assertContains('jobteaser', $partners);
    }

    /** @test */
    public function it_returns_supported_extensions(): void
    {
        $extensions = JobParserFactory::getSupportedExtensions();

        $this->assertIsArray($extensions);
        $this->assertContains('xml', $extensions);
        $this->assertContains('json', $extensions);
    }
}
