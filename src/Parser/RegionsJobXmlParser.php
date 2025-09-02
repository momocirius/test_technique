<?php

declare(strict_types=1);

namespace App\Parser;

use App\Entity\Job;

class RegionsJobXmlParser implements JobParserInterface
{
    public function parse(string $filepath): array
    {
        $xml = simplexml_load_file($filepath);
        if ($xml === false) {
            throw new \InvalidArgumentException("Impossible de parser le fichier XML : {$filepath}");
        }

        $jobs = [];
        foreach ($xml->item as $item) {
            $jobs[] = new Job(
                reference: (string) ($item->ref ?? ''),
                title: (string) ($item->title ?? ''),
                description: (string) ($item->description ?? ''),
                url: (string) ($item->url ?? ''),
                companyName: (string) ($item->company ?? ''),
                publishedDate: (string) ($item->pubDate ?? '')
            );
        }

        return $jobs;
    }
}
