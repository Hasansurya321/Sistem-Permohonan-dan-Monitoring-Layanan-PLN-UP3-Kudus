<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a valid active customer
        $this->customer = User::create([
            'name' => 'Customer John',
            'email' => 'john.customer@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'is_active' => 1,
            'status' => 'active',
            'phone' => '0812345678',
            'gender' => 'L',
            'nik' => '1234567890123456',
        ]);

        // Create an internal employee
        $this->employee = Employee::create([
            'name' => 'Employee Admin',
            'email' => 'admin@adminlayanan.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);
    }

    public function test_customer_and_employee_can_login_concurrently()
    {
        // 1. Log in as Customer
        $customerLoginResponse = $this->post(route('pelanggan.login.submit'), [
            'email' => 'john.customer@example.com',
            'password' => 'Password123!',
        ]);

        $customerLoginResponse->assertRedirect(route('landing'));

        // Save session after customer login
        $session = session()->all();

        // 2. Log in as Employee using same session context
        $employeeLoginResponse = $this->withSession($session)
            ->post(route('pegawai.login.post'), [
                'email' => 'admin@adminlayanan.com',
                'password' => 'Password123!',
            ]);

        $employeeLoginResponse->assertRedirect('/internal/admin-layanan');

        // Check auth status inside the response session context
        $finalSession = $employeeLoginResponse->getSession()->all();

        // Check both pages are accessible in this session
        $profileResponse = $this->withSession($finalSession)
            ->get(route('pelanggan.profile'));
        $profileResponse->assertStatus(200);

        $adminResponse = $this->withSession($finalSession)
            ->get(route('admin.requests.index'));
        $adminResponse->assertStatus(200);
    }

    public function test_customer_logout_does_not_terminate_employee_session()
    {
        // Setup both logged in
        $session = $this->getConcurrentSession();

        // Perform customer logout
        $logoutResponse = $this->withSession($session)
            ->post(route('pelanggan.logout'));

        $logoutResponse->assertRedirect(route('landing'));

        // Get session after logout
        $nextSession = $logoutResponse->getSession()->all();

        // Verify customer is logged out by trying to access customer profile
        $profileResponse = $this->withSession($nextSession)
            ->get(route('pelanggan.profile'));
        $profileResponse->assertRedirect('/internal/admin-layanan');

        // Verify employee is STILL logged in by trying to access employee admin requests
        $adminResponse = $this->withSession($nextSession)
            ->get(route('admin.requests.index'));
        $adminResponse->assertStatus(200);
    }

    public function test_employee_logout_does_not_terminate_customer_session()
    {
        // Setup both logged in
        $session = $this->getConcurrentSession();

        // Perform employee logout
        $logoutResponse = $this->withSession($session)
            ->post(route('pegawai.logout'));

        $logoutResponse->assertRedirect(route('landing'));

        // Get session after logout
        $nextSession = $logoutResponse->getSession()->all();

        // Verify employee is logged out by trying to access employee admin requests
        $adminResponse = $this->withSession($nextSession)
            ->get(route('admin.requests.index'));
        $adminResponse->assertRedirect(route('pelanggan.login'));

        // Verify customer is STILL logged in by trying to access customer profile
        $profileResponse = $this->withSession($nextSession)
            ->get(route('pelanggan.profile'));
        $profileResponse->assertStatus(200);
    }

    public function test_customer_cannot_access_employee_protected_routes()
    {
        // Accessing admin requests while logged in as customer redirects to login with error
        $response = $this->actingAs($this->customer, 'web')
            ->get(route('admin.requests.index'));

        // Should be redirected because we are not logged in as employee
        $response->assertRedirect(route('pelanggan.login'));
    }

    public function test_employee_cannot_access_customer_profile_directly()
    {
        // Accessing customer profile while logged in as employee redirects to employee panel
        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('pelanggan.profile'));

        // Should redirect to employee's own panel path
        $response->assertRedirect('/internal/admin-layanan');
    }

    private function getConcurrentSession(): array
    {
        // Log in customer first
        $this->post(route('pelanggan.login.submit'), [
            'email' => 'john.customer@example.com',
            'password' => 'Password123!',
        ]);

        $session = session()->all();

        // Log in employee with the same session context
        $employeeLoginResponse = $this->withSession($session)
            ->post(route('pegawai.login.post'), [
                'email' => 'admin@adminlayanan.com',
                'password' => 'Password123!',
            ]);

        return $employeeLoginResponse->getSession()->all();
    }
}
