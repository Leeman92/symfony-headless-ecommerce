<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Base repository interface for common operations
 * Note: Type hints are minimal to maintain Doctrine compatibility
 */
interface RepositoryInterface
{
    public function find($id, $lockMode = null, $lockVersion = null);
    
    public function findAll();
    
    public function save($entity);
    
    public function remove($entity);
}