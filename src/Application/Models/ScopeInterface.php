<?php
namespace Marwa\Application\Models;

interface ScopeInterface
{
    public function apply(QueryBuilder $query, Model $model);
}