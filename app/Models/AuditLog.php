<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    /**
     * Attributes that are mass assignable.
     * Ensure fields used in AdminController::class are fillable.
     */
    protected $fillable = [
        'admin_id',
        'action',
        'target_table',
        'target_id',
        // add 'metadata' if you later store details as JSON
    ];

    protected $casts = [
        // 'metadata' => 'array', // uncomment if you add a JSON column later
    ];
}
