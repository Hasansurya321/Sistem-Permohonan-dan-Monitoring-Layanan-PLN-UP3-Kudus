<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->name(),
            'email'     => $this->faker->unique()->safeEmail(),
            'password'  => Hash::make('password'),
            'role'      => 'admin_pelayanan',
            'unit'      => 'Admin Pelayanan',
            'jabatan'   => 'Staff',
            'is_active' => true,
        ];
    }

    public function withRole(string $role): static
    {
        return $this->state(['role' => $role]);
    }
}
