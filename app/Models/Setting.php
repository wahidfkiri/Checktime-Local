<?php
// app/Models/Setting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'email',
        'email_is_active',
        'email_employees_is_active',
        'sms_is_active',
        'sms_credit',
    ];

    protected $casts = [
        'email_is_active' => 'boolean',
        'sms_is_active' => 'boolean',
        'email_employees_is_active' => 'boolean',
        'sms_credit' => 'integer',
    ];

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if ($column === 'sms_credit') {
            $newValue = max(0, $this->sms_credit - $amount);
            $this->sms_credit = $newValue;
            $this->save();
            return $this;
        }
        
        // For other columns, use parent method
        return parent::decrement($column, $amount, $extra);
    }
}