<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'holiday_date',
        'name',
        'description',
        'is_recurring',
        'is_working_day'
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'is_working_day' => 'boolean'
    ];

    // Scopes
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('holiday_date', $year);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('holiday_date', $date)
                    ->orWhere(function($q) use ($date) {
                        $q->where('is_recurring', true)
                          ->whereMonth('holiday_date', date('m', strtotime($date)))
                          ->whereDay('holiday_date', date('d', strtotime($date)));
                    });
    }

    // Méthode pour vérifier si une date est fériée
    public static function isHoliday($date)
    {
        return static::where(function($q) use ($date) {
                        $q->where('holiday_date', $date)
                          ->orWhere(function($q2) use ($date) {
                              $q2->where('is_recurring', true)
                                 ->whereMonth('holiday_date', date('m', strtotime($date)))
                                 ->whereDay('holiday_date', date('d', strtotime($date)));
                          });
                    })
                    ->exists();
    }
}