<?php

namespace Database\Factories;

use App\Models\EmployeePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeePermissionFactory extends Factory
{
    protected $model = EmployeePermission::class;

    public function definition()
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'employee_id' => \App\Models\Employee::factory(),
            'date' => $this->faker->date(),
            'start_time' => $this->faker->time('H:i'),
            'end_time' => $this->faker->time('H:i'),
            'raison' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'duration_minutes' => $this->faker->numberBetween(30, 480),
        ];
    }
}