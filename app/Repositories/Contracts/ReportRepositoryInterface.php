<?php

namespace App\Repositories\Contracts;

interface ReportRepositoryInterface
{
    public function paginateWithFilters(int $perPage = 15, array $filters = []);
    
    public function findWithRelations(int $id, array $relations = []);
    
    public function create(array $data);
    
    public function update(int $id, array $data);
    
    public function delete(int $id);
    
    public function findByUser(int $userId, int $perPage = 15);
}
