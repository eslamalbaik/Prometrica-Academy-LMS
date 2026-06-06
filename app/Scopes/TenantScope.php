<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Context::get('tenant_id');
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', '=', $tenantId);
        }
    }
}
