<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$roles = [
    'adminlayanan.com' => '/internal/admin-pelayanan',
    'supervisor.com' => '/internal/supervisor',
    'unitsurvey.com' => '/internal/unit-survey',
    'unitperencanaan.com' => '/internal/unit-perencanaan',
    'unitkonstruksi.com' => '/internal/unit-konstruksi',
    'unitte.com' => '/internal/unit-te',
];

foreach ($roles as $domain => $path) {
    $email = explode('.', $domain)[0] . '@' . $domain;
    if ($email === 'adminlayanan@adminlayanan.com') $email = 'affan@adminlayanan.com';
    if ($email === 'supervisor@supervisor.com') $email = 'hasan@supervisor.com';
    if ($email === 'unitsurvey@unitsurvey.com') $email = 'budi@unitsurvey.com';
    if ($email === 'unitperencanaan@unitperencanaan.com') $email = 'citra@unitperencanaan.com';
    if ($email === 'unitkonstruksi@unitkonstruksi.com') $email = 'dedi@unitkonstruksi.com';
    if ($email === 'unitte@unitte.com') $email = 'eka@unitte.com';

    Auth::guard('employee')->attempt(['email' => $email, 'password' => 'Password123!']);
    $request = Illuminate\Http\Request::create($path, 'GET');
    $request->setLaravelSession(app('session')->driver());
    $response = $kernel->handle($request);
    
    echo "Testing $email to $path: Status " . $response->getStatusCode() . "\n";
    Auth::guard('employee')->logout();
}
