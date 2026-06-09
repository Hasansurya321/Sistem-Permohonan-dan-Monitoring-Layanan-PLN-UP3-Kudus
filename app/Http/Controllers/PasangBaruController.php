<?php

namespace App\Http\Controllers;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Models\ApplicantIdentity;
use App\Models\MasterPelanggan;
use App\Models\ServiceRequest;
use App\Models\MasterSlo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PasangBaruController extends Controller
{
    protected function getWizardSession()
    {
        $session = Session::get('pasang_baru', []);

        // 🔄 AUTO-RESTORE: Jika session kosong, cari draft aktif dari database
        if (empty($session) && Auth::guard('web')->check()) {
            $draft = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
                ->where('jenis_layanan', 'PASANG_BARU')
                ->where('is_draft', true)
                ->latest()
                ->first();

            if ($draft && !empty($draft->payload_json)) {
                $session = $draft->payload_json;
                $session['service_request_id'] = $draft->id;
                Session::put('pasang_baru', $session);
                Log::info('Auto-restore wizard session from draft', [
                    'service_request_id' => $draft->id,
                    'user_id' => Auth::guard('web')->id(),
                ]);
            }
        }

        return $session;
    }

    // --- STEP 1: Pilih Pemohon (TANPA ID Pelanggan untuk Pasang Baru) ---
    public function step1(Request $request)
    {
        $wizard = $this->getWizardSession();
        $user = Auth::guard('web')->user();
        $userProfile = $user ? $user->profile : null;

        return view('pelanggan.pasang-baru.step1', compact('wizard', 'user', 'userProfile'));
    }

    public function storeStep1(Request $request)
    {
        $request->validate([
            'for_whom' => 'required|in:self,other',
        ]);
        
        $wizard = $this->getWizardSession();
        $wizard['for_whom'] = $request->for_whom;

        // --- Force set applicant data at Step 1 ---
        if ($request->for_whom === 'self') {
            // Get from user profile (NIK dari akun yang sudah terverifikasi)
            $user = Auth::guard('web')->user();
            if (!$user || empty($user->nik)) {
                return back()->withErrors(['global' => 'NIK belum tersedia di akun Anda. Mohon lengkapi profil terlebih dahulu.'])->withInput();
            }
            
            // Get from master data
            $master = MasterPelanggan::where('nik', $user->nik)->first();
            if (!$master) {
                return back()->withErrors(['global' => 'Data master pelanggan tidak ditemukan untuk NIK ini.'])->withInput();
            }
            
            $wizard['applicant_nik'] = $master->nik;
            $wizard['applicant_name'] = $master->nama_lengkap;
            
            // NOTE: Untuk Pasang Baru, TIDAK ADA id_pelanggan_val karena pelanggan baru
            unset($wizard['id_pelanggan_val']);
            unset($wizard['id_pelanggan_meter']);
            unset($wizard['customer_name']);
            unset($wizard['tarif_lama']);
            unset($wizard['daya_lama']);
        } else {
            // Must have been checked via checkNik AJAX
            if (empty($wizard['applicant_nik'])) {
                return back()->withErrors(['nik_pemohon' => 'Silahkan cek NIK pemohon terlebih dahulu.'])->withInput();
            }
        }

        Session::put('pasang_baru', $wizard);

        // Redirect ke Step 2 (Detail Lokasi)
        return redirect()->route('pasang-baru.step2');
    }

    // --- STEP 2: Detail Lokasi ---
    public function step2()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['applicant_nik'])) {
            return redirect()->route('pasang-baru.step1')->withErrors(['global' => 'Silahkan isi data pemohon terlebih dahulu.']);
        }
        
        $prefill = $wizard['lokasi'] ?? [];
        return view('pelanggan.pasang-baru.step2', compact('wizard', 'prefill'));
    }

    public function storeStep2(Request $request)
    {
        $request->validate([
            'koordinat' => 'nullable|string',
            'provinsi' => 'required',
            'kab_kota' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'rt' => 'required|digits_between:1,3',
            'rw' => 'required|digits_between:1,3',
            'alamat_detail' => 'nullable|max:200',
        ]);

        $wizard = $this->getWizardSession();
        $wizard['lokasi'] = $request->only(['koordinat', 'provinsi', 'kab_kota', 'kecamatan', 'kelurahan', 'rt', 'rw', 'alamat_detail']);
        
        Session::put('pasang_baru', $wizard);

        // Sync to DB if draft already exists
        if (!empty($wizard['service_request_id'])) {
            ServiceRequest::where('id', $wizard['service_request_id'])
                ->where('submitter_user_id', Auth::guard('web')->id())
                ->update(['payload_json' => $wizard]);
        }

        return redirect()->route('pasang-baru.step3');
    }

    // --- STEP 3: Detail Layanan ---
    public function step3()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['applicant_nik'])) {
            return redirect()->route('pasang-baru.step1')->withErrors(['global' => 'Data pemohon belum lengkap.']);
        }
        
        $dayaOptions = [450, 900, 1300, 2200, 3500, 4400, 5500, 6600, 7700, 11000, 13200, 16500, 23000, 33000, 41500, 53000, 66000];
        
        return view('pelanggan.pasang-baru.step3', compact('wizard', 'dayaOptions'));
    }

    public function storeStep3(Request $request)
    {
        $request->validate([
            'daya_baru' => 'required|numeric',
            'jenis_produk' => 'required|in:PASCABAYAR,PRABAYAR',
            'peruntukan_koneksi' => 'required|in:RUMAH_TANGGA,BISNIS,INDUSTRI,SOSIAL,PEMERINTAH,RUMAH_IBADAH',
        ]);

        $wizard = $this->getWizardSession();
        $wizard['daya_baru'] = $request->daya_baru;
        $wizard['jenis_produk'] = $request->jenis_produk;
        $wizard['peruntukan_koneksi'] = $request->peruntukan_koneksi;

        // --- GUARD: Prevent applicant_nik NULL error ---
        if (empty($wizard['applicant_nik'])) {
            return redirect()->route('pasang-baru.step1')->withErrors(['global' => 'NIK pemohon belum tersimpan. Ulangi Step 1 dan verifikasi NIK.']);
        }

        // Initialize Service Request Draft if not exists
        if (empty($wizard['service_request_id'])) {
             $sr = ServiceRequest::create([
                 'submitter_user_id' => Auth::guard('web')->id(),
                 'applicant_nik' => $wizard['applicant_nik'],
                 'daya_baru' => $request->daya_baru,
                 'peruntukan_koneksi' => $request->peruntukan_koneksi,
                 'jenis_layanan' => 'PASANG_BARU', // Perbedaan utama dengan Tambah Daya
                 'status' => PermohonanStatus::VERIFIKASI_DATA,
                 'is_draft' => true,
                 'payload_json' => $wizard,
             ]);
             $wizard['service_request_id'] = $sr->id;
             $sr->syncLocationFromPayload();
             $sr->ensureInitialEvent();
        } else {
             $sr = ServiceRequest::find($wizard['service_request_id']);
             $sr->update([
                 'daya_baru' => $request->daya_baru,
                 'peruntukan_koneksi' => $request->peruntukan_koneksi,
                 'payload_json' => $wizard,
             ]);
             $sr->syncLocationFromPayload();
        }

        // Hapus dummy_billing dari session jika ada dari wizard sebelumnya.
        unset($wizard['dummy_billing']);

        Session::put('pasang_baru', $wizard);

        // Langsung lanjut ke Step 4 (Data SLO)
        return redirect()->route('pasang-baru.step4');
    }

    // --- STEP 4: Data SLO ---
    public function step4()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['daya_baru'])) {
            return redirect()->route('pasang-baru.step3');
        }
        return view('pelanggan.pasang-baru.step4', compact('wizard'));
    }

    public function storeStep4(Request $request)
    {
        $request->validate([
            'slo_no_registrasi' => 'required',
            'slo_no_sertifikat' => 'required',
        ]);

        $wizard = $this->getWizardSession();
        $wizard['slo_no_registrasi'] = $request->slo_no_registrasi;
        $wizard['slo_no_sertifikat'] = $request->slo_no_sertifikat;
        
        Session::put('pasang_baru', $wizard);

        // Sync to DB if draft already exists
        if (!empty($wizard['service_request_id'])) {
            ServiceRequest::where('id', $wizard['service_request_id'])
                ->where('submitter_user_id', Auth::guard('web')->id())
                ->update(['payload_json' => $wizard]);
        }

        return redirect()->route('pasang-baru.step5');
    }

    // --- STEP 5: Finalisasi ---
    public function step5()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['slo_no_registrasi'])) {
            return redirect()->route('pasang-baru.step4');
        }
        
        $user = Auth::guard('web')->user();
        $master = MasterPelanggan::where('nik', $wizard['applicant_nik'])->first();

        // Check if NPWP is mandatory
        $sr = ServiceRequest::find($wizard['service_request_id']);
        $wajibNPWP = ($sr && ($sr->daya_baru >= 7700 || in_array($sr->peruntukan_koneksi, ['BISNIS', 'INDUSTRI'])));

        return view('pelanggan.pasang-baru.step5', compact('wizard', 'user', 'master', 'wajibNPWP'));
    }

    public function storeStep5(Request $request)
    {
        Log::info('PasangBaruController::storeStep5 - Starting', [
            'session_keys' => array_keys(Session::all()),
            'has_wizard' => Session::has('pasang_baru'),
        ]);

        $wizard = $this->getWizardSession();
        $applicantNik = data_get($wizard, 'applicant_nik');

        if (!$applicantNik) {
            Log::error('PasangBaruController::storeStep5 - No applicant_nik in session');
            return redirect()->route('pasang-baru.step1')
                ->withErrors(['global' => 'Sesi habis atau data tidak lengkap. Silakan ulangi dari awal.']);
        }

        $master = MasterPelanggan::where('nik', $applicantNik)->first();
        if (!$master) {
            // Untuk Pasang Baru, master mungkin belum ada, jadi buat baru
            // Atau bisa juga dari data wizard
            Log::info('PasangBaruController::storeStep5 - Master not found (expected for Pasang Baru)', [
                'nik' => $applicantNik,
            ]);
        }

        $request->validate([
            'no_kk' => ['required', 'digits:16'],
            'no_hp' => ['required', 'regex:/^62[0-9]{10}$/'],
            'foto_bangunan' => 'required|image|max:2048',
            'foto_ktp_selfie' => 'required|image|max:2048',
        ]);

        $applicantData = [
            'nama_lengkap' => data_get($wizard, 'applicant_name') ?? ($master->nama_lengkap ?? null),
            'no_kk' => $request->no_kk,
            'no_hp' => $request->no_hp,
            'npwp' => data_get($wizard, 'npwp'), 
            'nik' => $applicantNik,
            // NOTE: id_pelanggan_12 dan no_meter KOSONG untuk Pasang Baru
            'id_pelanggan_12' => null,
            'no_meter' => null,
        ];

        if (data_get($wizard, 'for_whom') === 'self') {
            $applicantData['user_id'] = Auth::guard('web')->id();
        }

        if ($request->hasFile('foto_bangunan')) {
            $applicantData['foto_bangunan'] = $request->file('foto_bangunan')->store('uploads/bangunan', 'public');
        }
        if ($request->hasFile('foto_ktp_selfie')) {
            $applicantData['foto_ktp_selfie'] = $request->file('foto_ktp_selfie')->store('uploads/ktp_selfie', 'public');
        }

        $sr = DB::transaction(function () use ($wizard, $applicantData, $applicantNik) {
            $applicant = ApplicantIdentity::updateOrCreate(
                ['nik' => $applicantNik],
                $applicantData
            );

            $ser = ServiceRequest::where('id', $wizard['service_request_id'])
                        ->where('submitter_user_id', Auth::guard('web')->id())
                        ->first();

            $ser->update([
                'applicant_id' => $applicant->id,
                'payload_json' => $wizard,
                'nomor_permohonan' => $ser->nomor_permohonan ?? ('PB-' . str_pad((string) $ser->id, 6, '0', STR_PAD_LEFT)),
            ]);

            $ser->syncApplicantFromPayloadSafely();
            $ser->syncLocationFromPayload();

            $ser->transitionTo(
                PermohonanStatus::VERIFIKASI_DATA,
                PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                now()
            );

            $ser->update([
                'is_draft' => false,
                'submitted_at' => now(),
            ]);

            return $ser;
        });

        Session::forget('pasang_baru');
        return redirect()->route('monitoring.show', $sr->id)
            ->with('success', 'Permohonan berhasil dikirim. Nomor: ' . $sr->nomor_permohonan);
    }

    // --- AJAX Helpers ---
    
    public function checkNik(Request $request)
    {
        $request->validate([
            'nik' => ['required', 'digits:16'],
        ]);

        $nik = $request->input('nik');
        $master = MasterPelanggan::where('nik', $nik)->first();

        if (!$master) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'NIK tidak ditemukan di data master.',
            ], 422);
        }

        $wizard = $this->getWizardSession();
        
        // If NIK changes, clear previous verification
        if (isset($wizard['applicant_nik']) && $wizard['applicant_nik'] !== $nik) {
            // Clear for self mode
        }
        
        $wizard['applicant_nik'] = $nik;
        $wizard['applicant_name'] = $master->nama_lengkap ?? null;
        Session::put('pasang_baru', $wizard);

        return response()->json([
            'status' => 'ok',
            'message' => 'NIK valid.',
            'data' => [
                'nama' => $wizard['applicant_name'],
            ],
        ]);
    }

    public function checkSlo(Request $request)
    {
        $type = $request->type;
        $val = $request->value;

        // Validasi SLO harus memeriksa kepemilikan.
        $wizard = $this->getWizardSession();
        $user = Auth::guard('web')->user();
        $nikPemilik = $wizard['applicant_nik'] ?? $user->masterPelanggan?->nik ?? $user->nik;

        if ($type === 'reg') {
            $found = MasterSlo::where('no_registrasi_slo', $val)->exists();
            return response()->json(['status' => $found ? 'found' : 'not_found']);
        }

        if ($type === 'cert') {
            $found = MasterSlo::where('no_sertifikat_slo', $val)->exists();
            return response()->json(['status' => $found ? 'found' : 'not_found']);
        }

        if ($type === 'pair') {
            $slo = MasterSlo::where('no_registrasi_slo', $request->reg)
                             ->where('no_sertifikat_slo', $request->cert)
                             ->where('nik_pemilik', $nikPemilik)
                             ->first();
            if ($slo) {
                return response()->json([
                    'status' => 'valid',
                    'data' => [
                        'nama_pemilik' => 'TERVERIFIKASI',
                        'nik_pemilik' => '***',
                        'nama_lembaga' => $slo->nama_lembaga
                    ]
                ]);
            }
            return response()->json(['status' => 'not_found']);
        }
        
        return response()->json(['status' => 'invalid']);
    }

    public function verifyKK(Request $request)
    {
        $request->validate(['no_kk' => 'required|digits:16']);

        $wizard = $this->getWizardSession();
        $nik = data_get($wizard, 'applicant_nik');

        if (!$nik) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Data pemohon tidak ditemukan.',
            ], 422);
        }

        $master = MasterPelanggan::where('nik', $nik)->first();

        if (!$master || (string)$master->no_kk !== (string)$request->no_kk) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'KK tidak sesuai data kami',
            ], 422);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'No KK terverifikasi.',
        ]);
    }

    public function verifyNPWP(Request $request)
    {
        $request->validate(['npwp' => 'required|digits:16']);
        $wizard = $this->getWizardSession();
        $nik = data_get($wizard, 'applicant_nik');
        
        $master = MasterPelanggan::where('nik', $nik)->first();
        if (!$master || (string)$master->npwp !== (string)$request->npwp) {
            return response()->json(['status' => 'invalid', 'message' => 'NPWP tidak sesuai data pemohon.'], 422);
        }

        $wizard['npwp'] = $request->npwp;
        Session::put('pasang_baru', $wizard);

        return response()->json(['status' => 'valid', 'message' => 'NPWP terverifikasi.']);
    }

    // --- DRAFT MANAGEMENT ---
    public function autosave(Request $request, $id)
    {
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::guard('web')->id())->first();
        if ($sr && $sr->is_draft) {
             $sr->update(['payload_json' => array_merge($sr->payload_json ?? [], $request->all())]);
        }
        return response()->json(['status' => 'ok']);
    }

    public function cancel($id)
    {
        $sr = ServiceRequest::where('id', $id)
            ->where('submitter_user_id', Auth::guard('web')->id())
            ->where('jenis_layanan', 'PASANG_BARU')
            ->first();
        if ($sr && $sr->is_draft) {
            $sr->delete();
        }
        Session::forget('pasang_baru');
        return redirect()->route('monitoring');
    }

    public function resume($id)
    {
        $sr = ServiceRequest::where('id', $id)
            ->where('submitter_user_id', Auth::guard('web')->id())
            ->where('jenis_layanan', 'PASANG_BARU')
            ->first();
        if (!$sr || !$sr->is_draft) return redirect()->route('monitoring');
        
        Session::put('pasang_baru', $sr->payload_json ?? ['service_request_id' => $sr->id]);
        return redirect()->route('pasang-baru.step1');
    }
}
