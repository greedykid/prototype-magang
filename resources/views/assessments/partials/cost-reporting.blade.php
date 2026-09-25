{{-- SEMENTARA DISEMBUNYIKAN SESUAI PERMINTAAN USER: Fitur Pelaporan Biaya Perjalanan Dinas Asesor (Cost Reporting) --}}
@php
    $expense = $assessment->expense;
@endphp

@if($expense && $expense->total_cost > 0)
    {{-- Tersembunyi secara visual agar tidak tampil di antarmuka selama dinonaktifkan sementara --}}
    <div style="display: none;" aria-hidden="true">
        <x-status :value="$expense->status" />
        <span>{{ number_format($expense->total_cost, 0, ',', '.') }}</span>
    </div>
@endif

@include('partials.sheets-modal', [
    'modalId' => 'modal-sheets-sync-expenses',
    'title' => 'Integrasi Google Sheets: Rekapitulasi Biaya SBM Asesor',
    'subtitle' => 'Sinkronkan data biaya perjalanan dinas asesor (SBM Kemenkeu) untuk asesmen ini dan seluruh asesmen lainnya secara live ke Google Sheets.',
    'exportUrl' => route('reports.expenses.export'),
    'feedUrl' => route('feeds.expenses', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')]),
    'fileName' => 'rekap-biaya-sbm-simasadi.csv',
    'columns' => [
        'ID Asesmen', 'Judul Agenda', 'Nama LPK', 'Nomor Registrasi LPK', 'Jenis Asesmen',
        'Waktu Pelaksanaan', 'Status Asesmen', 'Uang Harian (Rp)', 'Transportasi (Rp)',
        'Akomodasi (Rp)', 'Paket Data (Rp)', 'Total Biaya (Rp)', 'Status SBM', 'Bukti Kwitansi / SPPD',
        'Catatan Verifikator KAN', 'Waktu Verifikasi'
    ]
])
