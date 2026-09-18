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
    ];
    $label = $labels[$value] ?? str_replace('_', ' ', $value);
@endphp
<span class="status status-{{ strtolower($value) }}">{{ $label }}</span>
