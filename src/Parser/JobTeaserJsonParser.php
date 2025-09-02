<?php

declare(strict_types=1);

namespace App\Parser;

use App\Entity\Job;

class JobTeaserJsonParser implements JobParserInterface
{
    public function parse(string $filepath): array
    {
        $jsonContent = file_get_contents($filepath);
        if ($jsonContent === false) {
            throw new \InvalidArgumentException("Impossible de lire le fichier : {$filepath}");
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Erreur de parsing JSON : " . json_last_error_msg());
        }

        $jobs = [];
        $urlPrefix = $data['offerUrlPrefix'] ?? '';

        foreach ($data['offers'] ?? [] as $offer) {
            $jobs[] = new Job(
                reference: $offer['reference'] ?? '',
                title: $offer['title'] ?? '',
                description: $offer['description'] ?? '',
                url: $urlPrefix . ($offer['urlPath'] ?? ''),
                companyName: $offer['companyname'] ?? '',
                publishedDate: $offer['publishedDate'] ?? ''
            );
        }

        return $jobs;
    }
}
