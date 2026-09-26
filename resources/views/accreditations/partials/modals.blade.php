{{-- Modals for Billing PNBP & e-Sign --}}

{{-- Modal 1: Terbitkan Billing SIMPONI --}}
<div class="simasadi-modal" id="modal-create-billing" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Penerbitan Kode Billing PNBP (SIMPONI)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-create-billing')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.billings.store', $accreditation) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Jenis Tarif PNBP</span>
                    <input type="text" name="tariff_name" value="PNBP Jasa Akreditasi Laboratorium / Lembaga Sertifikasi (PP PNBP BSN)" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nominal Tarif (Rp)</span>
                    <input type="number" name="amount" value="7500000" min="100000" step="50000" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Nominal tarif jasa akreditasi sesuai regulasi PP PNBP yang berlaku.</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-create-billing')">Batal</button>
                <button type="submit" class="button primary">Terbitkan Kode Billing</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Konfirmasi / Pelunasan Pembayaran PNBP --}}
@if($latestBilling && $latestBilling->status === 'UNPAID')
<div class="simasadi-modal" id="modal-pay-billing" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Konfirmasi Setoran PNBP ke Kas Negara</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-pay-billing')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.billings.pay', [$accreditation, $latestBilling]) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div style="background: #fafaf9; border: 1px solid var(--line); border-radius: 6px; padding: 12px;">
                    <span style="font-size: 12px; color: var(--muted); display: block;">Kode Billing SIMPONI:</span>
                    <strong style="font-size: 16px; font-family: monospace;">{{ $latestBilling->billing_code }}</strong>
                    <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 4px;">
                        Nominal Tagihan: <strong>Rp {{ number_format($latestBilling->amount, 0, ',', '.') }}</strong>
                    </span>
                </div>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Kanal Pembayaran</span>
                    <select name="payment_channel" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <option value="Bank Mandiri (ATM / Livin)">Bank Mandiri (ATM / Livin)</option>
                        <option value="BNI (Internet Banking / Teller)">BNI (Internet Banking / Teller)</option>
                        <option value="BRI (BRIMO / Teller)">BRI (BRIMO / Teller)</option>
                        <option value="BCA (KlikBCA / ATM)">BCA (KlikBCA / ATM)</option>
                        <option value="PT Pos Indonesia (Kantor Pos)">PT Pos Indonesia (Kantor Pos)</option>
                    </select>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nomor Transaksi Penerimaan Negara (NTPN)</span>
                    <input type="text" name="ntpn" placeholder="Kosongkan untuk simulasi otomatis 16-karakter" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">NTPN 16 digit alfanumerik yang tertera pada Bukti Penerimaan Negara (BPN).</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-pay-billing')">Batal</button>
                <button type="submit" class="button primary">Konfirmasi Setoran Sah</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal 3: Penandatanganan SK Digital (BSrE) --}}
<div class="simasadi-modal" id="modal-sign-doc" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Tanda Tangan Elektronik Dokumen SK KAN (BSrE)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-sign-doc')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('accreditations.esign.sign', $accreditation) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div style="background: #fdfaf3; border: 1px solid #f6e6be; border-radius: 6px; padding: 12px; font-size: 13px; color: #795200;">
                    Pembubuhan TTE dilakukan atas nama <strong>Ketua Komite Akreditasi Nasional (KAN)</strong> menggunakan sertifikat digital aktif dari Balai Sertifikasi Elektronik (BSrE).
                </div>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Nomor Surat Keputusan (SK)</span>
                    <input type="text" name="sk_number" value="{{ $signature->sk_number ?? ('SK.KAN.' . str_pad($accreditation->id, 3, '0', STR_PAD_LEFT) . '/BSN/IX/' . date('Y')) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Pejabat Penandatangan</span>
                    <input type="text" name="signer_name" value="{{ $signature->signer_name ?? 'Drs. Kukuh S. Achmad, M.Sc.' }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Passphrase Sertifikat BSrE</span>
                    <input type="password" name="passphrase" value="kan-bsre-demo" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Simulasi otentikasi passphrase token sertifikat digital.</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-sign-doc')">Batal</button>
                <button type="submit" class="button primary">Tandatangani & Rilis SK</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 4: Pratinjau QR Code Verifikasi Keaslian Dokumen --}}
@if($signature && $signature->is_signed)
<div class="simasadi-modal" id="modal-qr-preview" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box" style="text-align: center;">
        <div class="simasadi-modal-head">
            <h4>Segel Sertifikat Digital BSrE</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-qr-preview')" aria-label="Tutup modal">&times;</button>
        </div>
        <div style="margin: 16px 0;">
            <div style="background: #ffffff; border: 2px solid var(--maroon); border-radius: 12px; display: inline-block; padding: 18px; box-shadow: 0 4px 14px rgba(86, 69, 212, 0.15);">
                {{-- Simulated SVG QR Code --}}
                <svg width="150" height="150" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto;">
                    <rect width="100" height="100" fill="#ffffff"/>
                    <rect x="5" y="5" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="9" y="9" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="13" y="13" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="65" y="5" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="69" y="9" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="73" y="13" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="5" y="65" width="30" height="30" rx="3" fill="#1a1a1a"/>
                    <rect x="9" y="69" width="22" height="22" rx="2" fill="#ffffff"/>
                    <rect x="13" y="73" width="14" height="14" rx="1" fill="#5645d4"/>
                    <rect x="42" y="10" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="52" y="18" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="42" y="42" width="16" height="16" rx="2" fill="#5645d4"/>
                    <rect x="65" y="42" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="80" y="52" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="42" y="68" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="55" y="78" width="8" height="8" fill="#1a1a1a"/>
                    <rect x="75" y="72" width="16" height="16" fill="#1a1a1a"/>
                </svg>
            </div>
            <p style="font-size: 13.5px; font-weight: 600; color: var(--ink); margin: 14px 0 4px;">
                {{ $signature->sk_number }}
            </p>
            <span style="font-size: 12px; color: var(--muted); display: block;">
                Scan QR untuk memvalidasi integritas dokumen secara langsung via portal publik BSrE
            </span>
        </div>
        <div class="modal-form-actions" style="justify-content: center;">
            <a href="{{ route('accreditations.esign.verify', $signature->verify_hash) }}" target="_blank" class="button primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <span>Buka Halaman Verifikasi Publik</span>
                <x-icon name="chevron-right" size="14" />
            </a>
            <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-qr-preview')">
                Tutup
            </button>
        </div>
    </div>
</div>
@endif
