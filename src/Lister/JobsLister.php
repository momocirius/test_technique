<?php

declare(strict_types=1);

namespace App\Lister;

use App\Repository\JobRepositoryInterface;
use App\Entity\Job;

final class JobsLister
{
    public function __construct(private JobRepositoryInterface $repository)
    {
    }

    /**
     * Liste tous les jobs (retourne les entités)
     * 
     * @return Job[]
     */
    public function list(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Liste tous les jobs (format array pour compatibilité)
     * 
     * @return array
     */
    public function listAsArray(): array
    {
        $jobs = $this->repository->findAll();
        
        return array_map(function(Job $job): array {
            return $job->toArray();
        }, $jobs);
    }

    /**
     * Compte le nombre total de jobs
     */
    public function count(): int
    {
        return $this->repository->count();
    }
}
