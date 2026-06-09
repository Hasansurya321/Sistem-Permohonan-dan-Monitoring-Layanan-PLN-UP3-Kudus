<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    public function test_admin_layanan_can_access_panel_without_redirect_loop()
    {
        $employee = Employee::firstOrCreate(
            ['email' => 'affan@adminlayanan.com'],
            ['name' => 'Affan', 'password' => Hash::make('Password123!'), 'role' => 'admin_pelayanan', 'is_active' => true]
        );
        $this->actingAs($employee, 'employee');
        $response = $this->get('/internal/admin-layanan');
        $response->assertStatus(200);
    }

    public function test_supervisor_can_access_panel()
    {
        $employee = Employee::firstOrCreate(
            ['email' => 'hasan@supervisor.com'],
            ['name' => 'Hasan', 'password' => Hash::make('Password123!'), 'role' => 'supervisor', 'is_active' => true]
        );
        $this->actingAs($employee, 'employee');
        $response = $this->get('/internal/supervisor');
        $response->assertStatus(200);
    }

    public function test_unit_survey_can_access_panel()
    {
        $employee = Employee::firstOrCreate(
            ['email' => 'budi@unitsurvey.com'],
            ['name' => 'Budi', 'password' => Hash::make('Password123!'), 'role' => 'unit_survey', 'is_active' => true]
        );
        $this->actingAs($employee, 'employee');
        $response = $this->get('/internal/unit-survey');
        $response->assertStatus(200);
    }
}
