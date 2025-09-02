<?php

declare(strict_types=1);

namespace App\Parser;

use App\Entity\Job;

interface JobParserInterface
{
    /**
     * Parse un fichier et retourne un tableau d'objets Job
     * 
     * @param string $filepath Chemin vers le fichier à parser
     * @return Job[] Tableau d'objets Job
     */
    public function parse(string $filepath): array;
}
