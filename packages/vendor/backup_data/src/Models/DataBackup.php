<?php

namespace Vendor\BackupData\Models;

use Illuminate\Database\Eloquent\Model;

class DataBackup extends Model
{
    protected $table = 'data_backups';

    protected $fillable = [
        'filename',
        'path',
        'size_bytes',
        'tables_count',
        'rows_count',
        'status',
        'error',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'size_bytes'   => 'integer',
        'tables_count' => 'integer',
        'rows_count'   => 'integer',
    ];

    /**
     * Taille lisible (Ko, Mo, …).
     */
    public function getSizeHumanAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes <= 0) {
            return '0 o';
        }

        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 2, ',', ' ') . ' ' . $units[$power];
    }
}
