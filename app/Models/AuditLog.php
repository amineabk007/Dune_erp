<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audit logs are append-only: never updatable or deletable through the model.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \LogicException('Audit logs are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Audit logs are immutable and cannot be deleted.');
    }
}
