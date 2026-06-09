<?php

namespace Tests\Feature;

use App\Models\CustomerAccountRequest;
use App\Models\User;
use App\Models\Employee;
use App\Models\ActivationToken;
use App\Mail\CustomerActivationMail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_flow()
    {
        // Fake mail sending
        Mail::fake();

        // 1. Register a new customer
        $response = $this->post(route('pelanggan.register.submit'), [
            'name' => 'John Doe',
            'gender' => 'L',
            'phone' => '08123456789',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'address_detail' => 'Jl. Merdeka No. 10',
        ]);

        $response->assertRedirect(route('pelanggan.register.pending'));

        // Assert customer is not in users table
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);

        // Assert customer is in customer_account_requests
        $this->assertDatabaseHas('customer_account_requests', [
            'email' => 'john@example.com',
            'status' => 'pending',
        ]);

        // 2. Attempt login and verify it fails with the correct error
        $loginResponse = $this->post(route('pelanggan.login.submit'), [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $loginResponse->assertSessionHasErrors(['email' => 'Akun belum diverifikasi. Silakan tunggu konfirmasi admin.']);

        // 3. Create an employee to approve the request
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);

        $request = CustomerAccountRequest::where('email', 'john@example.com')->firstOrFail();

        // Approve the request (this generates activation token and sends email)
        $approveResponse = $this->actingAs($employee, 'employee')
            ->post(route('admin.requests.approve', $request->id));

        $approveResponse->assertRedirect(route('admin.requests.index'));

        // Assert request reviewer is updated
        $this->assertEquals($employee->id, $request->fresh()->reviewed_by);
        $this->assertNotNull($request->fresh()->reviewed_at);

        // Assert user is STILL NOT created in users table (since they haven't activated yet)
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);

        // Assert activation token was created in the database
        $this->assertDatabaseHas('activation_tokens', [
            'customer_account_request_id' => $request->id,
            'used_at' => null,
        ]);

        // Assert activation mail was sent
        Mail::assertSent(CustomerActivationMail::class, function ($mail) use ($request) {
            return $mail->hasTo('john@example.com') && $mail->requestData->id === $request->id;
        });

        // Log out employee session so it doesn't trigger guest redirection
        auth('employee')->logout();

        // Get the activation token
        $activationToken = ActivationToken::where('customer_account_request_id', $request->id)->firstOrFail();

        // 4. Hit the activation route with the token to verify email and create account
        $activateResponse = $this->get(route('pelanggan.activate', $activationToken->token));
        $activateResponse->assertRedirect(route('pelanggan.login'));
        $activateResponse->assertSessionHas('success', 'Akun Anda berhasil diaktifkan! Silakan masuk.');

        // Assert user IS NOW created in users table
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'status' => 'active',
            'is_active' => 1,
            'role' => 'pelanggan',
        ]);

        // Assert request status in customer_account_requests is approved
        $this->assertEquals('approved', $request->fresh()->status);

        // Assert token is marked as used
        $this->assertNotNull($activationToken->fresh()->used_at);

        // 5. Try logging in again and verify success
        $loginSuccessResponse = $this->post(route('pelanggan.login.submit'), [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $loginSuccessResponse->assertRedirect(route('landing'));
        $this->assertAuthenticatedAs(User::where('email', 'john@example.com')->first(), 'web');
    }

    public function test_customer_rejection_flow()
    {
        // 1. Register a new customer
        $this->post(route('pelanggan.register.submit'), [
            'name' => 'Jane Doe',
            'gender' => 'P',
            'phone' => '08123456780',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'address_detail' => 'Jl. Merdeka No. 11',
        ]);

        $request = CustomerAccountRequest::where('email', 'jane@example.com')->firstOrFail();

        // 2. Reject the request
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);

        $rejectResponse = $this->actingAs($employee, 'employee')
            ->post(route('admin.requests.reject', $request->id), [
                'rejection_reason' => 'Data tidak valid',
            ]);

        $rejectResponse->assertRedirect(route('admin.requests.index'));

        // Assert customer is not in users table
        $this->assertDatabaseMissing('users', [
            'email' => 'jane@example.com',
        ]);

        // Assert request status is rejected
        $this->assertEquals('rejected', $request->fresh()->status);
        $this->assertEquals($employee->id, $request->fresh()->reviewed_by);

        // Log out employee session so it doesn't trigger guest redirection
        auth('employee')->logout();

        // 3. Attempt login and verify it shows rejected message
        $loginResponse = $this->post(route('pelanggan.login.submit'), [
            'email' => 'jane@example.com',
            'password' => 'Password123!',
        ]);

        $loginResponse->assertSessionHasErrors(['email' => 'Permintaan akun Anda ditolak.']);
    }

    public function test_customer_activation_failures()
    {
        Mail::fake();

        // Register a customer
        $this->post(route('pelanggan.register.submit'), [
            'name' => 'Bob Smith',
            'gender' => 'L',
            'phone' => '08123456781',
            'email' => 'bob@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'address_detail' => 'Jl. Merdeka No. 12',
        ]);

        $request = CustomerAccountRequest::where('email', 'bob@example.com')->firstOrFail();

        // 1. Invalid Token
        $response = $this->get(route('pelanggan.activate', 'invalid-token'));
        $response->assertRedirect(route('pelanggan.login'));
        $response->assertSessionHas('error', 'Token aktivasi tidak valid atau telah kedaluwarsa.');

        // Approve the request
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);

        $this->actingAs($employee, 'employee')
            ->post(route('admin.requests.approve', $request->id));

        $activationToken = ActivationToken::where('customer_account_request_id', $request->id)->firstOrFail();

        // 2. Expired Token
        $activationToken->update([
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->get(route('pelanggan.activate', $activationToken->token));
        $response->assertRedirect(route('pelanggan.login'));
        $response->assertSessionHas('error', 'Token aktivasi tidak valid atau telah kedaluwarsa.');

        // Restore expiration but mark as used
        $activationToken->update([
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
        ]);

        // 3. Already Used Token
        $response = $this->get(route('pelanggan.activate', $activationToken->token));
        $response->assertRedirect(route('pelanggan.login'));
        $response->assertSessionHas('error', 'Token aktivasi tidak valid atau telah kedaluwarsa.');
    }

    public function test_admin_approval_and_rejection()
    {
        Mail::fake();

        // Create an employee and authenticate
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);

        $this->actingAs($employee, 'employee');

        // 1. Create a pending request
        $request = CustomerAccountRequest::create([
            'full_name' => 'Alice Test',
            'email' => 'alice_test@example.com',
            'phone' => '08123456782',
            'gender' => 'P',
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'password_hash' => Hash::make('Password123!'),
            'status' => 'pending',
        ]);

        // 2. Approve the request via HTTP route
        $approveResponse = $this->post(route('admin.requests.approve', $request->id));
        $approveResponse->assertRedirect(route('admin.requests.index'));

        // Assert status updated to approved
        $this->assertEquals('approved', $request->fresh()->status);

        // Assert activation token created
        $this->assertDatabaseHas('activation_tokens', [
            'customer_account_request_id' => $request->id,
            'used_at' => null,
        ]);

        // Assert activation mail sent
        Mail::assertSent(CustomerActivationMail::class);

        // 3. Create another pending request to test rejection
        $request2 = CustomerAccountRequest::create([
            'full_name' => 'Bob Test',
            'email' => 'bob_test@example.com',
            'phone' => '08123456783',
            'gender' => 'L',
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'password_hash' => Hash::make('Password123!'),
            'status' => 'pending',
        ]);

        // Reject the request via HTTP route
        $rejectResponse = $this->post(route('admin.requests.reject', $request2->id), [
            'rejection_reason' => 'Data tidak valid',
        ]);
        $rejectResponse->assertRedirect(route('admin.requests.index'));

        // Assert status updated to rejected
        $this->assertEquals('rejected', $request2->fresh()->status);
        $this->assertDatabaseMissing('users', [
            'email' => 'bob_test@example.com',
        ]);
    }

    public function test_strict_login_validations()
    {
        // 1. Test Customer Login Validations
        // A. Pending User
        $userPending = User::create([
            'name' => 'Pending Customer',
            'email' => 'pending_cust@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'status' => 'pending',
            'is_active' => 0,
        ]);
        $response = $this->post(route('pelanggan.login.submit'), [
            'email' => 'pending_cust@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akun belum diverifikasi. Silakan tunggu konfirmasi admin.']);

        // B. Approved (not yet active) User
        $userApproved = User::create([
            'name' => 'Approved Customer',
            'email' => 'approved_cust@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'status' => 'approved',
            'is_active' => 0,
        ]);
        $response = $this->post(route('pelanggan.login.submit'), [
            'email' => 'approved_cust@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akun belum diaktivasi. Silakan gunakan token aktivasi yang dikirimkan ke email Anda.']);

        // C. Rejected User
        $userRejected = User::create([
            'name' => 'Rejected Customer',
            'email' => 'rejected_cust@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'status' => 'rejected',
            'is_active' => 0,
        ]);
        $response = $this->post(route('pelanggan.login.submit'), [
            'email' => 'rejected_cust@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Permintaan akun Anda ditolak.']);

        // D. Inactive User
        $userInactive = User::create([
            'name' => 'Inactive Customer',
            'email' => 'inactive_cust@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'status' => 'active',
            'is_active' => 0,
        ]);
        $response = $this->post(route('pelanggan.login.submit'), [
            'email' => 'inactive_cust@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akun Anda belum aktif. Silakan hubungi administrator.']);

        // E. Wrong Role in Customer Login (e.g. admin_pelayanan role inside users table)
        $userWrongRole = User::create([
            'name' => 'Wrong Role User',
            'email' => 'wrong_role@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'status' => 'active',
            'is_active' => 1,
        ]);
        $response = $this->post(route('pelanggan.login.submit'), [
            'email' => 'wrong_role@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akses ditolak. Role akun tidak valid.']);

        // 2. Test Employee Login Validations
        // A. Employee incorrect credentials
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin_sec@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);
        $response = $this->post(route('pegawai.login.post'), [
            'email' => 'admin_sec@example.com',
            'password' => 'wrong-pass',
        ]);
        $response->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        // B. Employee inactive
        $employeeInactive = Employee::create([
            'name' => 'Inactive Admin',
            'email' => 'admin_inactive@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => false,
        ]);
        $response = $this->post(route('pegawai.login.post'), [
            'email' => 'admin_inactive@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akun Anda belum aktif. Silakan hubungi administrator.']);

        // C. Employee invalid role
        $employeeInvalidRole = Employee::create([
            'name' => 'Invalid Role Admin',
            'email' => 'admin_invalid_role@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'invalid_role',
            'is_active' => true,
        ]);
        $response = $this->post(route('pegawai.login.post'), [
            'email' => 'admin_invalid_role@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email' => 'Akses ditolak. Role akun tidak valid.']);
    }

    public function test_resend_activation_email_flow()
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        \Illuminate\Support\Facades\Mail::fake();

        // Create an employee first to satisfy foreign key constraint
        $employee = Employee::create([
            'name' => 'Admin Layanan',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin_pelayanan',
            'is_active' => true,
        ]);

        // 1. Create a customer account request and set status to approved
        $request = CustomerAccountRequest::create([
            'full_name' => 'Resend Test',
            'gender' => 'L',
            'phone' => '08123456789',
            'email' => 'resend@example.com',
            'password_hash' => Hash::make('Password123!'),
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $employee->id,
        ]);

        // Create an initial token
        $oldToken = ActivationToken::create([
            'customer_account_request_id' => $request->id,
            'token' => 'old_token_123',
            'expires_at' => now()->addHours(24),
        ]);

        // Create an expired token for another request to test cleanup
        $expiredRequest = CustomerAccountRequest::create([
            'full_name' => 'Expired Test',
            'gender' => 'L',
            'phone' => '08123456781',
            'email' => 'expired@example.com',
            'password_hash' => Hash::make('Password123!'),
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $employee->id,
        ]);

        $expiredToken = ActivationToken::create([
            'customer_account_request_id' => $expiredRequest->id,
            'token' => 'expired_token_abc',
            'expires_at' => now()->subHours(1),
        ]);

        // 2. Perform resend request
        $response = $this->post(route('pelanggan.activation.resend'), [
            'email' => 'resend@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Email aktivasi baru berhasil dikirim. Silakan periksa kotak masuk Anda.');

        // Assert old token was deleted
        $this->assertDatabaseMissing('activation_tokens', [
            'token' => 'old_token_123',
        ]);

        // Assert trying to activate with the old deleted token fails
        $oldTokenResponse = $this->get(route('pelanggan.activate', 'old_token_123'));
        $oldTokenResponse->assertRedirect(route('pelanggan.login'));
        $oldTokenResponse->assertSessionHas('error', 'Token aktivasi tidak valid atau telah kedaluwarsa.');

        // Assert expired token was cleaned up
        $this->assertDatabaseMissing('activation_tokens', [
            'token' => 'expired_token_abc',
        ]);

        // Assert a new token exists
        $newToken = ActivationToken::where('customer_account_request_id', $request->id)->first();
        $this->assertNotNull($newToken);
        $this->assertNotEquals('old_token_123', $newToken->token);

        // Assert mail was sent with new token
        \Illuminate\Support\Facades\Mail::assertSent(CustomerActivationMail::class, function ($mail) use ($request, $newToken) {
            return $mail->hasTo('resend@example.com') && $mail->token === $newToken->token;
        });

        // 3. Test Cooldown
        $cooldownResponse = $this->post(route('pelanggan.activation.resend'), [
            'email' => 'resend@example.com',
        ]);
        $cooldownResponse->assertSessionHas('error');
        $this->assertStringContainsString('Cooldown aktif', session('error'));

        // Clear cooldown cache to test other failures
        $cooldownKey = 'resend_cooldown_' . md5('resend@example.com');
        \Illuminate\Support\Facades\Cache::forget($cooldownKey);

        // 4. Test Not Approved Status
        $pendingRequest = CustomerAccountRequest::create([
            'full_name' => 'Pending Test',
            'gender' => 'L',
            'phone' => '08123456789',
            'email' => 'pending_resend@example.com',
            'password_hash' => Hash::make('Password123!'),
            'province' => 'Jawa Tengah',
            'regency' => 'Kudus',
            'district' => 'Kota',
            'village' => 'Demaan',
            'postal_code' => '59311',
            'status' => 'pending',
        ]);

        $pendingResponse = $this->post(route('pelanggan.activation.resend'), [
            'email' => 'pending_resend@example.com',
        ]);
        $pendingResponse->assertSessionHas('error', 'Permintaan registrasi belum disetujui atau status tidak valid.');

        // 5. Test Active User account
        // Let's activate the test request to create user
        $request->update(['status' => 'approved']);
        $activateResponse = $this->get(route('pelanggan.activate', $newToken->token));
        $activateResponse->assertRedirect(route('pelanggan.login'));
        $activateResponse->assertSessionHas('success');

        // Clear cooldown cache again if any
        \Illuminate\Support\Facades\Cache::forget($cooldownKey);

        $activeResponse = $this->post(route('pelanggan.activation.resend'), [
            'email' => 'resend@example.com',
        ]);

        $activeResponse->assertSessionHas('error', 'Akun sudah aktif. Silakan masuk.');
    }
}
