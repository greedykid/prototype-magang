@php
    $labels = [
        'ACTIVE' => 'Aktif',
        'INACTIVE' => 'Tidak Aktif',
        'NOT_STARTED' => 'Belum Dimulai',
        'IN_PROGRESS' => 'Sedang Berlangsung',
        'IN_PROCESS' => 'Dalam Proses',
        'WAITING_OUTPUT' => 'Menunggu Output',
        'OUTPUT_RELEASED' => 'Output Dirilis',
        'COMPLETED' => 'Selesai',
        'OPEN' => 'Terbuka',
        'WAITING_RESPONSE' => 'Menunggu Respons',
        'RESOLVED' => 'Terselesaikan',
        'CLOSED' => 'Ditutup',
        'PLANNED' => 'Direncanakan',
        'SCHEDULED' => 'Terjadwal',
        'CANCELLED' => 'Dibatalkan',
        'SUBMITTED' => 'Diajukan',
        'UNDER_REVIEW' => 'Sedang Ditinjau',
        'NEED_REVISION' => 'Perlu Perbaikan',
        'APPROVED' => 'Disetujui',
        'REJECTED' => 'Ditolak',
        'HEALTHY' => 'Normal',
        'DEGRADED' => 'Menurun',
        'DOWN' => 'Tidak Aktif',
        'UNKNOWN' => 'Tidak Diketahui',
        'SUCCESS' => 'Berhasil',
        'FAILED' => 'Gagal',
        'RUNNING' => 'Sedang Berjalan',
        // Biaya Asesor (SBM)
        'BELUM_DILAPORKAN' => 'Belum Dilaporkan',
        'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi SBM',
        'TERVERIFIKASI' => 'Terverifikasi SBM',
        'PERLU_REVISI' => 'Perlu Revisi Biaya',
        // Realisasi PNBP SIMPONI
        'UNPAID' => 'Belum Bayar',
        'PAID' => 'Terbayar (NTPN Sah)',
        'EXPIRED' => 'Kedaluwarsa',
        // Tanda Tangan Elektronik BSrE
        'SIGNED' => 'Tersertifikasi BSrE',
        'UNSIGNED' => 'Belum TTE',
        // Siklus Pengawasan Akreditasi KAN
        'SURVEILLANCE_OVERDUE' => 'Lewat Jadwal Surveilen',
        'SURVEILLANCE_DUE' => 'Jatuh Tempo Surveilen',
    ];
    $label = $labels[$value] ?? str_replace('_', ' ', $value);
@endphp
<span class="status status-{{ strtolower($value) }}">{{ $label }}</span>
