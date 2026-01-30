<?php

namespace App\Http\Controllers;

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

class TambahDayaController extends Controller
{
    protected function getWizardSession()
    {
        return Session::get('tambah_daya', []);
    }

    // --- STEP 1: Pilih Pemohon ---
    public function step1(Request $request)
    {
        $wizard = $this->getWizardSession();
        $user = Auth::user();
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
                ->where('submitter_user_id', Auth::id())
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
                 'submitter_user_id' => Auth::id(),
                 'applicant_nik' => $wizard['applicant_nik'],
                 'daya_baru' => $request->daya_baru,
                 'peruntukan_koneksi' => $request->peruntukan_koneksi,
                 'jenis_layanan' => 'TAMBAH_DAYA',
                 'status' => PermohonanStatus::DRAFT,
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

        Session::put('tambah_daya', $wizard);

        return redirect()->route('tambah-daya.step4');
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
                ->where('submitter_user_id', Auth::id())
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
        
        $user = Auth::user();
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
            $applicantData['user_id'] = Auth::id();
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
                        ->where('submitter_user_id', Auth::id())
                        ->first();

            $ser->update([
                'applicant_id' => $applicant->id,
                'status' => PermohonanStatus::DITERIMA_PLN,
                'status_detail' => null,
                'is_draft' => false,
                'submitted_at' => now(),
                'payload_json' => $wizard,
                'nomor_permohonan' => $ser->nomor_permohonan ?? ('TD-' . str_pad((string) $ser->id, 6, '0', STR_PAD_LEFT)),
            ]);

            $ser->syncApplicantFromPayloadSafely();
            $ser->syncLocationFromPayload();

            // Create submission event manually since we used update()
            \App\Models\ServiceRequestEvent::create([
                'service_request_id' => $ser->id,
                'status' => PermohonanStatus::DITERIMA_PLN,
                'status_detail' => \App\Enums\PermohonanDetailStatus::MENUNGGU_VERIFIKASI,
                'occurred_at' => $ser->submitted_at,
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
        
        // Determine the NIK to match against
        if ($forWhom === 'self') {
            $nikLocked = Auth::user()->nik;
        } else {
            $nikLocked = $wizard['applicant_nik'] ?? null;
        }

        if (!$nikLocked) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Silahkan verifikasi NIK pemohon terlebih dahulu.',
            ], 422);
        }

        $master = MasterPelanggan::where('id_pelanggan_12', $idPel)
                    ->orWhere('no_meter', $idPel)
                    ->first();

        if (!$master) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'ID Pelanggan / No Meter tidak ditemukan.',
            ], 422);
        }

        // --- NEW VALIDATION: Must match the locked NIK ---
        if ($master->nik !== $nikLocked) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'ID Pelanggan / Nomor Meter tidak sesuai dengan NIK pemohon.',
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

        if ($request->no_kk !== $nik) {
            return response()->json(['status' => 'invalid', 'message' => 'No KK harus sama dengan NIK pemohon.'], 422);
        }

        return response()->json(['status' => 'valid', 'message' => 'No KK terverifikasi.']);
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
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::id())->first();
        if ($sr && $sr->is_draft) {
             $sr->update(['payload_json' => array_merge($sr->payload_json ?? [], $request->all())]);
        }
        return response()->json(['status' => 'ok']);
    }

    public function cancel($id)
    {
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::id())->first();
        if ($sr && $sr->is_draft) {
            $sr->delete();
        }
        Session::forget('tambah_daya');
        return redirect()->route('monitoring');
    }

    public function resume($id)
    {
        $sr = ServiceRequest::where('id', $id)->where('submitter_user_id', Auth::id())->first();
        if (!$sr || !$sr->is_draft) return redirect()->route('monitoring');
        
        Session::put('tambah_daya', $sr->payload_json ?? ['service_request_id' => $sr->id]);
        return redirect()->route('tambah-daya.step1');
    }
}
