<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkHourType;

class WorkHourTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Heure normale',
                'code' => 'NORMAL',
                'start_time' => '08:00',
                'end_time' => '16:30',
                'break_minutes' => 60,
                'is_overnight' => false,
            ],
            [
                'name' => 'Matinée',
                'code' => 'MORNING',
                'start_time' => '07:00',
                'end_time' => '15:00',
                'break_minutes' => 60,
                'is_overnight' => false,
            ],
            [
                'name' => 'Soirée',
                'code' => 'EVENING',
                'start_time' => '15:00',
                'end_time' => '22:00',
                'break_minutes' => 60,
                'is_overnight' => false,
            ],
            [
                'name' => 'Nuit',
                'code' => 'NIGHT',
                'start_time' => '22:00',
                'end_time' => '07:00',
                'break_minutes' => 60,
                'is_overnight' => true,
            ],
            [
                'name' => 'Rotation 24h/48h',
                'code' => 'ROTATION_24_48',
                'start_time' => '07:00',
                'end_time' => '07:00',
                'break_minutes' => 60,
                'is_overnight' => true,
            ],
        ];

        // Pour chaque client, créer ces types d'horaires
        $clients = \App\Models\Client::all();
        
        foreach ($clients as $client) {
            foreach ($types as $type) {
                WorkHourType::create(array_merge($type, [
                    'client_id' => $client->id,
                    'is_active' => true
                ]));
            }
        }
    }
}