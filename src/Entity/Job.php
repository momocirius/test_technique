<?php

declare(strict_types=1);

namespace App\Entity;

class Job
{
    public function __construct(
        public string $reference,
        public string $title,
        public string $description,
        public string $url,
        public string $companyName,
        public string $publishedDate,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'company_name' => $this->companyName,
            'publication' => $this->publishedDate
        ];
    }
}
