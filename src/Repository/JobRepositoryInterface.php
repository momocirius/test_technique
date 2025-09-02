<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Job;

interface JobRepositoryInterface
{
    /**
     * Sauvegarde un job en base
     */
    public function save(Job $job): void;

    /**
     * Sauvegarde plusieurs jobs en une seule transaction
     * 
     * @param Job[] $jobs
     */
    public function saveAll(array $jobs): void;

    /**
     * Récupère tous les jobs
     * 
     * @return Job[]
     */
    public function findAll(): array;

    /**
     * Supprime tous les jobs
     */
    public function clear(): void;

    /**
     * Compte le nombre total de jobs
     */
    public function count(): int;
}
