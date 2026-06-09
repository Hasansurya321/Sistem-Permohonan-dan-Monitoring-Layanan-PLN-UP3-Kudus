<x-filament-panels::page>
    <style>
        .detail-header {
            margin-bottom: 16px;
        }
        .detail-header a {
            color: #093c5d;
            font-size: 14px;
            text-decoration: none;
        }
        .detail-header a:hover {
            text-decoration: underline;
        }
        .detail-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .detail-card-header {
            display: none;
        }
        .detail-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .detail-badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .detail-badge-approved {
            background: #dcfce7;
            color: #166534;
        }
        .detail-badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 24px;
        }
        .detail-section-title {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 12px;
        }
        .detail-field {
            margin-bottom: 10px;
        }
        .detail-field:last-child {
            margin-bottom: 0;
        }
        .detail-field-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .detail-field-value {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
        }
        .detail-actions {
            background: #f8fafc;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .detail-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            font-family: inherit;
        }
        .detail-btn-reject {
            background: #ffffff;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }
        .detail-btn-reject:hover {
            background: #fef2f2;
        }
        .detail-btn-approve {
            background: #093c5d;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(9,60,93,0.2);
        }
        .detail-btn-approve:hover {
            background: #1e4d7a;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            margin: 0 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .modal-body {
            padding: 24px;
        }
        .modal-body p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .modal-body textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
            resize: vertical;
        }
        .modal-body textarea:focus {
            border-color: #093c5d;
            box-shadow: 0 0 0 3px rgba(9,60,93,0.1);
        }
        .modal-footer {
            background: #f8fafc;
            padding: 12px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .modal-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            font-family: inherit;
        }
        .modal-btn-cancel {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #374151;
        }
        .modal-btn-cancel:hover {
            background: #f3f4f6;
        }
        .modal-btn-submit {
            background: #dc2626;
            color: #ffffff;
        }
        .modal-btn-submit:hover {
            background: #b91c1c;
        }
        .hidden {
            display: none !important;
        }
    </style>

    <div class="detail-header">
        <a href="{{ \App\Filament\AdminLayanan\Pages\PermintaanAkun::getUrl() }}">&larr; Kembali ke Menu</a>
    </div>

    <div class="detail-card">

        <div class="detail-grid">
            <!-- Informasi Pribadi -->
            <div>
                <div class="detail-section-title">Informasi Pribadi</div>
                <div class="detail-field">
                    <div class="detail-field-label">Nama Lengkap</div>
                    <div class="detail-field-value">{{ $request->full_name }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">NIK</div>
                    <div class="detail-field-value">{{ $request->nik ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Nomor NPWP</div>
                    <div class="detail-field-value">{{ $request->nomor_npwp ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">SLO Reg</div>
                    <div class="detail-field-value">{{ $request->slo_reg ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">SLO Cert</div>
                    <div class="detail-field-value">{{ $request->slo_cert ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">No KK</div>
                    <div class="detail-field-value">{{ $request->no_kk ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Email</div>
                    <div class="detail-field-value">{{ $request->email }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">No HP</div>
                    <div class="detail-field-value">{{ $request->phone }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Jenis Kelamin</div>
                    <div class="detail-field-value">{{ $request->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                </div>
            </div>

            <!-- Alamat & Data Instalasi -->
            <div>
                <div class="detail-section-title">Alamat Domisili</div>
                <div class="detail-field">
                    <div class="detail-field-label">Provinsi</div>
                    <div class="detail-field-value">{{ $request->province }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Kota/Kabupaten</div>
                    <div class="detail-field-value">{{ $request->regency }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Kecamatan</div>
                    <div class="detail-field-value">{{ $request->district }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Kelurahan/Desa</div>
                    <div class="detail-field-value">{{ $request->village }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Kode Pos</div>
                    <div class="detail-field-value">{{ $request->postal_code }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Detail Alamat</div>
                    <div class="detail-field-value">{{ $request->address_text ?? '-' }}</div>
                </div>
                <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 12px;">
                    <div class="detail-section-title">Data Instalasi PLN</div>
                    <div class="detail-field">
                        <div class="detail-field-label">ID Pelanggan</div>
                        <div class="detail-field-value">{{ $request->id_pelanggan ?? '-' }}</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-field-label">Nomor Meter</div>
                        <div class="detail-field-value">{{ $request->nomor_meter ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($request->status === 'pending')
        <div class="detail-actions">
            <button type="button" class="detail-btn detail-btn-reject" onclick="document.getElementById('rejectModal').classList.remove('hidden')">
                Tolak Permintaan
            </button>

            <button type="button" class="detail-btn detail-btn-approve" wire:click="approve" wire:loading.attr="disabled">
                Setujui & Buat Akun
            </button>
        </div>
        @else
        <div class="detail-actions">
            <span style="font-size:13px;color:#64748b;">
                Status: {{ $request->status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                @if($request->rejection_reason)
                    — {{ $request->rejection_reason }}
                @endif
            </span>
        </div>
        @endif
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Tolak Permintaan Akun</h2>
            </div>
            <div class="modal-body">
                <p>Anda akan menolak permintaan registrasi akun dari <strong>{{ $request->full_name }}</strong> ({{ $request->email }}). Silakan isi alasan penolakan.</p>
                <textarea wire:model.lazy="rejectionReason" rows="4" placeholder="Contoh: Data identitas tidak lengkap / Data tidak sesuai ketentuan / Dokumen pendukung tidak valid"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="document.getElementById('rejectModal').classList.add('hidden')">
                    Batal
                </button>
                <button type="button" class="modal-btn modal-btn-submit" wire:click="reject" wire:loading.attr="disabled">
                    Ya, Tolak Akun
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var el = document.getElementById('rejectModal');
                if (el) el.classList.add('hidden');
            }
        });
        document.addEventListener('click', function(e) {
            var el = document.getElementById('rejectModal');
            if (el && e.target === el) {
                el.classList.add('hidden');
            }
        });
    </script>
</x-filament-panels::page>