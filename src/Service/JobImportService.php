<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\JobRepositoryInterface;
use App\Factory\JobParserFactory;

class JobImportService
{
    public function __construct(
        private JobRepositoryInterface $repository
    ) {}

    /**
     * Importe des jobs depuis un fichier
     * 
     * @param string $filepath Chemin vers le fichier à importer
     * @param string|null $partner Partenaire explicite (regionsjob, jobteaser, indeed, etc.) ou null pour détection auto
     * @return int Nombre de jobs importés
     * @throws \Exception Si l'import échoue
     */
    public function import(string $filepath, ?string $partner = null): int
    {
        $this->validateFile($filepath);
        
        // Choix de la stratégie de détection
        $parser = $this->createParser($filepath, $partner);
        
        // Parse des jobs
        $jobs = $parser->parse($filepath);
        
        if (empty($jobs)) {
            throw new \Exception("Aucun job trouvé dans le fichier: {$filepath}");
        }
        
        // Import en base (remplace tous les jobs existants)
        $this->repository->clear();
        $this->repository->saveAll($jobs);
        
        return count($jobs);
    }

    /**
     * Importe des jobs en mode ajout (sans supprimer les existants)
     * 
     * @param string $filepath Chemin vers le fichier à importer
     * @param string|null $partner Partenaire explicite (regionsjob, jobteaser, indeed, etc.) ou null pour détection auto
     * @return int Nombre de jobs importés
     */
    public function append(string $filepath, ?string $partner = null): int
    {
        $this->validateFile($filepath);
        
        $parser = $this->createParser($filepath, $partner);
        
        $jobs = $parser->parse($filepath);
        
        if (empty($jobs)) {
            return 0;
        }
        
        $this->repository->saveAll($jobs);
        
        return count($jobs);
    }

    /**
     * Retourne le nombre total de jobs en base
     */
    public function getTotalJobs(): int
    {
        return $this->repository->count();
    }

    /**
     * Retourne tous les jobs
     * 
     * @return \App\Entity\Job[]
     */
    public function getAllJobs(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Supprime tous les jobs
     */
    public function clearAllJobs(): void
    {
        $this->repository->clear();
    }

    /**
     * Crée un parser avec différentes stratégies de détection
     * 
     * @param string $filepath Chemin du fichier
     * @param string|null $partner Partenaire explicite ou null pour détection
     * @return \App\Parser\JobParserInterface
     */
    private function createParser(string $filepath, ?string $partner): \App\Parser\JobParserInterface
    {
        if ($partner !== null) {
            // 1. Partenaire explicite (le plus fiable)
            return JobParserFactory::create($partner);
        }
        
        try {
            // 2. Détection par contenu (analyse la structure)
            return JobParserFactory::createFromContent($filepath);
        } catch (\Exception $e) {
            // 3. Fallback : détection par nom de fichier
            return JobParserFactory::createFromFile($filepath);
        }
    }

    /**
     * Valide qu'un fichier existe et est lisible
     * 
     * @param string $filepath Chemin du fichier
     * @throws \Exception Si le fichier est invalide
     */
    private function validateFile(string $filepath): void
    {
        if (!file_exists($filepath)) {
            throw new \Exception("Le fichier n'existe pas: {$filepath}");
        }
        
        if (!is_readable($filepath)) {
            throw new \Exception("Le fichier n'est pas lisible: {$filepath}");
        }
        
        if (filesize($filepath) === 0) {
            throw new \Exception("Le fichier est vide: {$filepath}");
        }
    }
}
