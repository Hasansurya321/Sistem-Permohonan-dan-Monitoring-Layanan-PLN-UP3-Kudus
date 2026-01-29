<?php

namespace App\Http\Controllers;

use App\Enums\PermohonanStatus;
use App\Models\ApplicantIdentity;
use App\Models\MasterPelanggan;
use App\Models\ServiceRequest;
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

    public function storeStep5(Request $request)
    {
        $wizard = $this->getWizardSession();
        $applicantNik = data_get($wizard, 'applicant_nik');

        if (!$applicantNik) {
            return redirect()->route('tambah-daya.step1')
                ->withErrors(['global' => 'Data Pemohon tidak lengkap. Silahkan ulangi verifikasi.']);
        }

        // Determine Wajib NPWP (dari SR)
        $wajibNPWP = false;
        $srDraft = null;

        if (!empty($wizard['service_request_id'])) {
            $srDraft = ServiceRequest::find($wizard['service_request_id']);
            if ($srDraft && ($srDraft->daya_baru >= 7700 || in_array($srDraft->peruntukan_koneksi, ['BISNIS', 'INDUSTRI']))) {
                $wajibNPWP = true;
            }
        }

        // STRICT validation
        $rules = [
            'foto_bangunan' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'foto_ktp_selfie' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'no_kk' => ['required', 'digits:16'],
            'no_hp' => ['required', 'regex:/^62[0-9]{10}$/'], // 12 digit start 62
        ];

        $messages = [
            'no_hp.regex' => 'Nomor HP harus 12 digit dan diawali 62 (contoh: 628123456789).',
            'no_kk.digits' => 'No KK harus tepat 16 digit angka.',
            'foto_bangunan.required' => 'Foto bangunan wajib diunggah.',
            'foto_ktp_selfie.required' => 'Foto diri dengan KTP wajib diunggah.',
        ];

        if ($wajibNPWP) {
            $rules['npwp'] = ['required', 'regex:/^\d{16}$/']; // format baru 16 digit
            $messages['npwp.regex'] = 'NPWP harus tepat 16 digit angka (format baru).';
        } else {
            // kalau FE ngirim npwp tetap, kita tolak biar disiplin
            $rules['npwp'] = ['nullable', 'prohibited'];
        }

        $request->validate($rules, $messages);

        // MASTER check
        $master = MasterPelanggan::where('nik', $applicantNik)->first();
        if (!$master) {
            return back()->withErrors(['global' => 'Data Master Pemohon tidak ditemukan.']);
        }

        // KK rule (sesuaikan dengan rule bisnis lo)
        // kalau rule lo memang "KK harus sama dengan NIK", ini lebih aman:
        if ($request->no_kk !== $applicantNik) {
            return back()->withErrors(['no_kk' => 'No KK harus sama dengan NIK pemohon.'])->withInput();
        }

        // NPWP final validation vs master (kalau wajib)
        $npwpToSave = null;
        if ($wajibNPWP) {
            $npwpClean = preg_replace('/\D/', '', (string) $request->npwp);
            if ($master->npwp !== $npwpClean) {
                return back()->withErrors(['npwp' => 'NPWP tidak valid atau tidak sesuai dengan NIK Pemohon.'])->withInput();
            }
            $npwpToSave = $npwpClean;
        }

        $user = Auth::user();

        // 1) Fallback no_kk & no_hp (WAJIB)
        $reqNoKk = $request->input('no_kk');
        $masterNoKk = $master->no_kk;
        $noKkFinal = filled($reqNoKk) ? $reqNoKk : $masterNoKk;

        $reqNoHp = $request->input('no_hp');
        $masterNoHp = $master->no_hp;
        $noHpFinal = filled($reqNoHp) ? $reqNoHp : $masterNoHp;

        if (blank($noKkFinal)) {
             return back()->withErrors(['no_kk' => 'Data No KK tidak ditemukan.'])->withInput();
        }

        // Ensure strictly digits and length 16
        if (!preg_match('/^\d{16}$/', $noKkFinal)) {
             return back()->withErrors(['no_kk' => 'No KK harus tepat 16 digit angka.'])->withInput();
        }

        // Log Source Analysis
        Log::info('STEP5_KK_SOURCE', [
            'req_no_kk' => $reqNoKk,
            'master_no_kk' => $masterNoKk,
            'wizard_no_kk' => data_get($wizard, 'no_kk'),
            'final_no_kk' => $noKkFinal,
            'nik' => $applicantNik,
        ]);

        // Build applicantData
        $applicantData = [
            'nama_lengkap' => data_get($wizard, 'applicant_name') ?? $master->nama_lengkap,
            'no_kk' => $noKkFinal,
            'no_hp' => $noHpFinal,
            'npwp' => $npwpToSave, 
            'id_pelanggan_12' => data_get($wizard, 'id_pelanggan_val'),
            'no_meter' => data_get($wizard, 'id_pelanggan_meter'),
        ];

        if (data_get($wizard, 'for_whom') === 'self') {
            $applicantData['user_id'] = $user->id;
        }

        // Files
        if ($request->hasFile('foto_bangunan')) {
            $applicantData['foto_bangunan'] = $request->file('foto_bangunan')->store('uploads/bangunan', 'public');
        }
        if ($request->hasFile('foto_ktp_selfie')) {
            $applicantData['foto_ktp_selfie'] = $request->file('foto_ktp_selfie')->store('uploads/ktp_selfie', 'public');
        }

        // Finalize Service Request (TRANSACTION)
        $sr = DB::transaction(function () use ($wizard, $applicantData, $applicantNik) {
            // 1. Update/Create Applicant Identity inside transaction
            $applicant = ApplicantIdentity::updateOrCreate(
                ['nik' => $applicantNik],
                $applicantData
            );

            // 2. Resolve Service Request (Strict: Must exist)
            $serviceRequestId = data_get($wizard, 'service_request_id');
            $sr = null;
            
            if ($serviceRequestId) {
                $sr = ServiceRequest::where('id', $serviceRequestId)
                        ->where('submitter_user_id', Auth::id())
                        ->first();
            }

            // Fallback: try latest draft
            if (!$sr) {
                $sr = ServiceRequest::where('submitter_user_id', Auth::id())
                        ->where('is_draft', true)
                        ->latest()
                        ->first();
            }

            if (!$sr) {
                // Should fail if no draft found to finalize
                abort(422, 'Service Request Draft not found. Silahkan ulangi proses.');
            }

            // idempotent: kalau sudah submit, return
            if (!$sr->is_draft && $sr->status !== PermohonanStatus::DRAFT) {
                return $sr;
            }

            // 2) Pastikan SR mengarah ke applicant yang sama
            $sr->update([
                'applicant_id' => $applicant->id,
                'applicant_nik' => $applicant->nik,
                'status' => PermohonanStatus::DITERIMA_PLN,
                'is_draft' => false,
                'submitted_at' => now(),
                'payload_json' => $wizard,
            ]);

            if (empty($sr->nomor_permohonan)) {
                $sr->update([
                    'nomor_permohonan' => 'TD-' . str_pad((string) $sr->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            // Sync lokasi payload -> applicant.default_*
            $sr->refresh()->loadMissing('applicant');
            $sr->syncApplicantFromPayloadSafely();

            // 4) Add log marker
            Log::info('STEP5_KK_SAVED', [
                'sr_id' => $sr->id,
                'applicant_id' => $applicant->id,
                'nik' => $applicant->nik,
                'no_kk' => $applicant->no_kk,
                'npwp' => $applicant->npwp,
            ]);

            return $sr;
        });

        Session::forget('tambah_daya');
        Session::forget('td_step1');

        return redirect()->route('monitoring')
            ->with('success', 'Permohonan berhasil dikirim dan diterima PLN. Nomor: ' . $sr->nomor_permohonan);
    }
}
