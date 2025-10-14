<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramManagerUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'program_manager_name',
        'program_manager_email',
        'organization',
        'phone',
        'notes',
        'file_path',
        'original_filename',
        'file_size',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];
}
