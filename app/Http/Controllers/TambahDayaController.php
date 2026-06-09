<?php

namespace App\Http\Controllers;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Models\ApplicantIdentity;
use App\Models\MasterPelanggan;
use App\Models\ServiceRequest;
use App\Models\MasterSlo;
use App\Services\DummyTambahDayaBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TambahDayaController extends Controller
{
    protected function getWizardSession()
    {
        $session = Session::get('tambah_daya', []);

        // 🔄 AUTO-RESTORE: Jika session kosong, cari draft aktif dari database
        if (empty($session) && Auth::guard('web')->check()) {
            $draft = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
                ->where('is_draft', true)
                ->latest()
                ->first();

            if ($draft && !empty($draft->payload_json)) {
                $session = $draft->payload_json;
                $session['service_request_id'] = $draft->id;
                Session::put('tambah_daya', $session);
                Log::info('Auto-restore wizard session from draft', [
                    'service_request_id' => $draft->id,
                    'user_id' => Auth::guard('web')->id(),
                ]);
            }
        }

        return $session;
    }

    // --- STEP 1: Pilih Pemohon ---
    public function step1(Request $request)
    {
        $wizard = $this->getWizardSession();
        $user = Auth::guard('web')->user();
        $userProfile = $user ? $user->profile : null;

        return view('pelanggan.tambah-daya.step1', compact('wizard', 'user', 'userProfile'));
    }

    public function storeStep1(Request $request)
    {
        $request->validate([
            'for_whom' => 'required|in:self,other',
        ]);
        
        $wizard = $this->getWizardSession();
        $wizard['for_whom'] = $request->for_whom;

        if (empty($wizard['id_pelanggan_val'])) {
            return back()->withErrors(['id_pelanggan' => 'Silahkan verifikasi ID Pelanggan terlebih dahulu.'])->withInput();
        }

        // --- NEW: Force set applicant data at Step 1 ---
        if ($request->for_whom === 'self') {
            // Get from verified ID Pelanggan master data
            $master = MasterPelanggan::where('id_pelanggan_12', $wizard['id_pelanggan_val'])->first();
            if (!$master) {
                return back()->withErrors(['id_pelanggan' => 'Data master pelanggan tidak ditemukan.'])->withInput();
            }
            $wizard['applicant_nik'] = $master->nik;
            $wizard['applicant_name'] = $master->nama_lengkap;
        } else {
            // Must have been checked via checkNik AJAX
            if (empty($wizard['applicant_nik'])) {
                return back()->withErrors(['nik_pemohon' => 'Silahkan cek NIK pemohon terlebih dahulu.'])->withInput();
            }
        }

        Session::put('tambah_daya', $wizard);

        // Redirect ke Step 2 (Detail Lokasi)
        return redirect()->route('tambah-daya.step2');
    }

    // --- STEP 2: Detail Lokasi ---
    public function step2()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['id_pelanggan_val'])) {
            return redirect()->route('tambah-daya.step1')->withErrors(['global' => 'Silahkan verifikasi ID Pelanggan terlebih dahulu.']);
        }
        
        $prefill = $wizard['lokasi'] ?? [];
        return view('pelanggan.tambah-daya.step2', compact('wizard', 'prefill'));
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
        
        Session::put('tambah_daya', $wizard);

        // Sync to DB if draft already exists
        if (!empty($wizard['service_request_id'])) {
            ServiceRequest::where('id', $wizard['service_request_id'])
                ->where('submitter_user_id', Auth::guard('web')->id())
                ->update(['payload_json' => $wizard]);
        }

        return redirect()->route('tambah-daya.step3');
    }

    // --- STEP 3: Detail Layanan ---
    public function step3()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['id_pelanggan_val'])) {
            return redirect()->route('tambah-daya.step1')->withErrors(['global' => 'Verifikasi ID Pelanggan dulu.']);
        }
        
        $dayaOptions = [450, 900, 1300, 2200, 3500, 4400, 5500, 6600, 7700, 11000, 13200, 16500, 23000, 33000, 41500, 53000, 66000];
        
        return view('pelanggan.tambah-daya.step3', compact('wizard', 'dayaOptions'));
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
            return redirect()->route('tambah-daya.step1')->withErrors(['global' => 'NIK pemohon belum tersimpan. Ulangi Step 1 dan verifikasi NIK.']);
        }

        // Initialize Service Request Draft if not exists
        if (empty($wizard['service_request_id'])) {
             $sr = ServiceRequest::create([
                 'submitter_user_id' => Auth::guard('web')->id(),
                 'applicant_nik' => $wizard['applicant_nik'],
                 'daya_baru' => $request->daya_baru,
                 'peruntukan_koneksi' => $request->peruntukan_koneksi,
                 'jenis_layanan' => 'TAMBAH_DAYA',
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

        // 🔴 GUARD: Billing TIDAK boleh digenerate di sini.
        // Tagihan hanya boleh lahir setelah Admin ACC (di ServiceRequest::adminAccept()).
        // Hapus dummy_billing dari session jika ada dari wizard sebelumnya.
        unset($wizard['dummy_billing']);

        Session::put('tambah_daya', $wizard);

        // Langsung lanjut ke Step 4 (Data SLO) — tanpa tagihan
        return redirect()->route('tambah-daya.step4');
    }

    /**
     * Menampilkan halaman invoice/tagihan dummy setelah Step 3.
     */
    public function invoice()
    {
        $wizard = $this->getWizardSession();

        if (empty($wizard['daya_baru']) || empty($wizard['peruntukan_koneksi']) || empty($wizard['dummy_billing'])) {
            return redirect()->route('tambah-daya.step3')->withErrors(['global' => 'Data layanan belum lengkap.']);
        }

        $billing = $wizard['dummy_billing'];

        // Mapping peruntukan ke label yang lebih mudah dibaca
        $peruntukanLabels = [
            'RUMAH_TANGGA' => 'Rumah Tangga',
            'BISNIS'       => 'Bisnis',
            'INDUSTRI'     => 'Industri',
            'SOSIAL'       => 'Sosial',
            'PEMERINTAH'   => 'Pemerintah',
            'RUMAH_IBADAH' => 'Rumah Ibadah',
        ];

        // Data invoice
        $invoice = [
            'nomor_permohonan'    => $wizard['service_request_id']
                ? ('TD-' . str_pad((string) $wizard['service_request_id'], 6, '0', STR_PAD_LEFT))
                : 'TD-XXXXXX',
            'tanggal_permohonan'  => now()->format('d-m-Y'),
            'nama_pelanggan'      => $wizard['customer_name'] ?? $wizard['applicant_name'] ?? '-',
            'no_pelanggan'        => $wizard['id_pelanggan_val'] ?? '-',
            'peruntukan'          => $wizard['peruntukan_koneksi'],
            'peruntukan_label'    => $peruntukanLabels[$wizard['peruntukan_koneksi']] ?? $wizard['peruntukan_koneksi'],
            'daya'                => (int) $wizard['daya_baru'],
            'nomor_invoice'       => 'INV-TD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'tanggal_terbit'      => now()->format('d-m-Y'),
            'tanggal_jatuh_tempo' => now()->addDays(7)->format('d-m-Y'),
        ];

        return view('pelanggan.tambah-daya.invoice', compact('wizard', 'billing', 'invoice'));
    }

    // --- STEP 4: Data SLO ---
    public function step4()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['daya_baru'])) {
            return redirect()->route('tambah-daya.step3');
        }
        return view('pelanggan.tambah-daya.step4', compact('wizard'));
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
        
        Session::put('tambah_daya', $wizard);

        // Sync to DB if draft already exists
        if (!empty($wizard['service_request_id'])) {
            ServiceRequest::where('id', $wizard['service_request_id'])
                ->where('submitter_user_id', Auth::guard('web')->id())
                ->update(['payload_json' => $wizard]);
        }

        return redirect()->route('tambah-daya.step5');
    }

    // --- STEP 5: Finalisasi ---
    public function step5()
    {
        $wizard = $this->getWizardSession();
        if (empty($wizard['slo_no_registrasi'])) {
            return redirect()->route('tambah-daya.step4');
        }
        
        $user = Auth::guard('web')->user();
        $master = MasterPelanggan::where('id_pelanggan_12', $wizard['id_pelanggan_val'])->first();

        // Check if NPWP is mandatory
        $sr = ServiceRequest::find($wizard['service_request_id']);
        $wajibNPWP = ($sr && ($sr->daya_baru >= 7700 || in_array($sr->peruntukan_koneksi, ['BISNIS', 'INDUSTRI'])));

        return view('pelanggan.tambah-daya.step5', compact('wizard', 'user', 'master', 'wajibNPWP'));
    }

    public function storeStep5(Request $request)
    {
        $wizard = $this->getWizardSession();
        $applicantNik = data_get($wizard, 'applicant_nik');

        if (!$applicantNik) {
            return redirect()->route('tambah-daya.step1')->withErrors(['global' => 'Data Pemohon tidak lengkap.']);
        }

        $master = MasterPelanggan::where('nik', $applicantNik)->first();
        if (!$master) {
            return back()->withErrors(['global' => 'Data Master Pemohon tidak ditemukan.']);
        }

        $request->validate([
            'no_kk' => ['required', 'digits:16'],
            'no_hp' => ['required', 'regex:/^62[0-9]{10}$/'],
            'foto_bangunan' => 'required|image|max:2048',
            'foto_ktp_selfie' => 'required|image|max:2048',
        ]);

        $applicantData = [
            'nama_lengkap' => data_get($wizard, 'applicant_name') ?? $master->nama_lengkap,
            'no_kk' => $request->no_kk,
            'no_hp' => $request->no_hp,
            'npwp' => data_get($wizard, 'npwp'), 
            'id_pelanggan_12' => data_get($wizard, 'id_pelanggan_val'),
            'no_meter' => data_get($wizard, 'id_pelanggan_meter'),
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
                'nomor_permohonan' => $ser->nomor_permohonan ?? ('TD-' . str_pad((string) $ser->id, 6, '0', STR_PAD_LEFT)),
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

        Session::forget('tambah_daya');
        return redirect()->route('monitoring.show', $sr->id)
            ->with('success', 'Permohonan berhasil dikirim. Nomor: ' . $sr->nomor_permohonan);
    }

    // --- AJAX Helpers ---
    // --- AJAX Helpers ---
    public function verifyIdPelanggan(Request $request)
    {
        $request->validate([
            'id_pelanggan' => ['required', 'regex:/^\d{11,12}$/'],
            'for_whom' => ['required', 'in:self,other'],
        ]);

        $forWhom = $request->input('for_whom');
        $idPel = $request->input('id_pelanggan');
        
        $wizard = $this->getWizardSession();
        $master = null;

        // ONE SOURCE OF TRUTH: mode 'self' prioritaskan user_id
        // (hasil sinkronisasi aktivasi). Fallback ke NIK untuk akun lama.
        if ($forWhom === 'self') {
            // PRIORITAS 1: Cari via user_id (SOURCE OF TRUTH hasil aktivasi)
            $master = MasterPelanggan::where('user_id', Auth::guard('web')->id())
                ->where(function ($q) use ($idPel) {
                    $q->where('id_pelanggan_12', $idPel)
                      ->orWhere('no_meter', $idPel);
                })
                ->first();

            // FALLBACK: Jika tidak ditemukan via user_id, cari via NIK dari master_pelanggan
            // atau users.nik (untuk akun lama yang dibuat sebelum migration user_id)
            if (!$master) {
                $user = Auth::guard('web')->user();
                $nikLocked = $user->masterPelanggan?->nik ?? $user->nik;
                if ($nikLocked) {
                    $master = MasterPelanggan::where('nik', $nikLocked)
                        ->where(function ($q) use ($idPel) {
                            $q->where('id_pelanggan_12', $idPel)
                              ->orWhere('no_meter', $idPel);
                        })
                        ->first();
                }
            }
        } else {
            // MODE 'other': cari via NIK dari wizard (data pemohon lain)
            $nikLocked = $wizard['applicant_nik'] ?? null;
            if (!$nikLocked) {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Silahkan verifikasi NIK pemohon terlebih dahulu.',
                ], 422);
            }
            $master = MasterPelanggan::where('nik', $nikLocked)
                ->where(function ($q) use ($idPel) {
                    $q->where('id_pelanggan_12', $idPel)
                      ->orWhere('no_meter', $idPel);
                })
                ->first();
        }

        if (!$master) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'ID Pelanggan / No Meter tidak ditemukan.',
            ], 422);
        }

        $wizard['id_pelanggan_val'] = $master->id_pelanggan_12;
        $wizard['id_pelanggan_meter'] = $master->no_meter;
        $wizard['customer_name'] = $master->nama_lengkap;
        $wizard['tarif_lama'] = $master->tarif;
        $wizard['daya_lama'] = $master->daya;
        
        Session::put('tambah_daya', $wizard);

        return response()->json([
            'status' => 'ok',
            'message' => 'Data ditemukan dan sesuai dengan NIK pemohon.',
            'data' => [
                'nama' => $master->nama_lengkap,
                'id_pelanggan' => $master->id_pelanggan_12,
                'no_meter' => $master->no_meter,
                'tarif' => $master->tarif, 
                'daya' => $master->daya,
            ],
        ]);
    }

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
        
        // If NIK changes, clear previous IDPel verification to force re-verification
        if (isset($wizard['applicant_nik']) && $wizard['applicant_nik'] !== $nik) {
            unset($wizard['id_pelanggan_val']);
            unset($wizard['id_pelanggan_meter']);
        }
        
        $wizard['applicant_nik'] = $nik;
        $wizard['applicant_name'] = $master->nama_lengkap ?? null;
        Session::put('tambah_daya', $wizard);

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

        // ONE SOURCE OF TRUTH: Validasi SLO harus memeriksa kepemilikan.
        // Gunakan NIK pemohon dari wizard session. Fallback: master_pelanggan, lalu users.nik.
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
        Session::put('tambah_daya', $wizard);

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
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::guard('web')->id())->first();
        if ($sr && $sr->is_draft) {
            $sr->delete();
        }
        Session::forget('tambah_daya');
        return redirect()->route('monitoring');
    }

    public function resume($id)
    {
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::guard('web')->id())->first();
        if (!$sr || !$sr->is_draft) return redirect()->route('monitoring');
        
        Session::put('tambah_daya', $sr->payload_json ?? ['service_request_id' => $sr->id]);
        return redirect()->route('tambah-daya.step1');
    }
}
