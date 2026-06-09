<?php

namespace Tests\Feature;

use App\Mail\CustomerResetPasswordMail;
use App\Mail\EmployeeResetPasswordMail;
use App\Models\CustomerPasswordResetToken;
use App\Models\EmployeePasswordResetToken;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use WithFaker;

    public function test_customer_active_reset_success()
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'nik' => '1234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]);

        $response = $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'nik' => '1234567890123456',
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(CustomerResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->token !== '';
        });

        $mail = Mail::sent(CustomerResetPasswordMail::class)[0];
        $this->assertNotNull($mail->token);

        $tokenResponse = $this->post(route('pelanggan.reset-password'), [
            'email' => $user->email,
            'token' => $mail->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $tokenResponse->assertRedirect(route('pelanggan.login'));
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_pending_customer_reset_blocked()
    {
        $user = User::factory()->create([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'nik' => '2234567890123456',
            'role' => 'pelanggan',
            'is_active' => false,
            'status' => 'pending',
        ]);

        $response = $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'nik' => '2234567890123456',
        ]);

        $response->assertSessionHasErrors('global');
        $this->assertDatabaseCount('customer_password_reset_tokens', 0);
    }

    public function test_rejected_customer_reset_blocked()
    {
        $user = User::factory()->create([
            'name' => 'Agus Prabowo',
            'email' => 'agus@example.com',
            'nik' => '3234567890123456',
            'role' => 'pelanggan',
            'is_active' => false,
            'status' => 'rejected',
        ]);

        $response = $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Agus Prabowo',
            'email' => 'agus@example.com',
            'nik' => '3234567890123456',
        ]);

        $response->assertSessionHasErrors('global');
        $this->assertDatabaseCount('customer_password_reset_tokens', 0);
    }

    public function test_employee_reset_success()
    {
        Mail::fake();

        $employee = Employee::create([
            'name' => 'Rina Susanti',
            'email' => 'rina@example.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post(route('pegawai.forgot-password.store'), [
            'email' => 'rina@example.com',
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(EmployeeResetPasswordMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) && $mail->token !== '';
        });

        $mail = Mail::sent(EmployeeResetPasswordMail::class)[0];
        $this->assertNotNull($mail->token);

        $tokenResponse = $this->post(route('pegawai.reset-password'), [
            'email' => $employee->email,
            'token' => $mail->token,
            'password' => 'supersecure123',
            'password_confirmation' => 'supersecure123',
        ]);

        $tokenResponse->assertRedirect(route('pegawai.login'));
        $this->assertTrue(Hash::check('supersecure123', $employee->refresh()->password));
    }

    public function test_reused_token_blocked()
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi2@example.com',
            'nik' => '4234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Budi Santoso',
            'email' => 'budi2@example.com',
            'nik' => '4234567890123456',
        ]);

        $mail = Mail::sent(CustomerResetPasswordMail::class)[0];

        $this->post(route('pelanggan.reset-password'), [
            'email' => $user->email,
            'token' => $mail->token,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $secondAttempt = $this->post(route('pelanggan.reset-password'), [
            'email' => $user->email,
            'token' => $mail->token,
            'password' => 'password456',
            'password_confirmation' => 'password456',
        ]);

        $secondAttempt->assertSessionHasErrors('token');
    }

    public function test_expired_token_blocked()
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Citra Dewi',
            'email' => 'citra@example.com',
            'nik' => '5234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Citra Dewi',
            'email' => 'citra@example.com',
            'nik' => '5234567890123456',
        ]);

        $mail = Mail::sent(CustomerResetPasswordMail::class)[0];

        $tokenRecord = CustomerPasswordResetToken::latest('created_at')->first();
        $tokenRecord->update(['expires_at' => now()->subMinutes(5)]);

        $response = $this->post(route('pelanggan.reset-password'), [
            'email' => $user->email,
            'token' => $mail->token,
            'password' => 'newpassword789',
            'password_confirmation' => 'newpassword789',
        ]);

        $response->assertSessionHasErrors('token');
    }

    public function test_wrong_guard_blocked()
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Dedi Wijaya',
            'email' => 'dedi@example.com',
            'nik' => '6234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Dedi Wijaya',
            'email' => 'dedi@example.com',
            'nik' => '6234567890123456',
        ]);

        $mail = Mail::sent(CustomerResetPasswordMail::class)[0];

        $response = $this->post(route('pegawai.reset-password'), [
            'email' => 'dedi@example.com',
            'token' => $mail->token,
            'password' => 'badpassword',
            'password_confirmation' => 'badpassword',
        ]);

        $response->assertSessionHasErrors('token');
    }

    public function test_concurrent_session_unaffected()
    {
        $employee = Employee::create([
            'name' => 'Nina Kurnia',
            'email' => 'nina@example.com',
            'password' => Hash::make('pegawaipass'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Auth::guard('employee')->login($employee);

        $user = User::factory()->create([
            'name' => 'Gilang Permata',
            'email' => 'gilang@example.com',
            'nik' => '7234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->post(route('pelanggan.forgot-password.store'), [
            'nama' => 'Gilang Permata',
            'email' => 'gilang@example.com',
            'nik' => '7234567890123456',
        ]);

        $this->assertTrue(Auth::guard('employee')->check());
        $this->assertEquals($employee->id, Auth::guard('employee')->id());
    }

    public function test_employee_session_isolation_unaffected()
    {
        Auth::guard('web')->login(User::factory()->create([
            'name' => 'Toni',
            'email' => 'toni@example.com',
            'nik' => '8234567890123456',
            'role' => 'pelanggan',
            'is_active' => true,
            'status' => 'active',
        ]));

        $employee = Employee::create([
            'name' => 'Wulan Arum',
            'email' => 'wulan@example.com',
            'password' => Hash::make('pegawaipass'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Auth::guard('employee')->login($employee);

        $this->assertTrue(Auth::guard('employee')->check());
        $this->assertTrue(Auth::guard('web')->check());
    }
}
