<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pertukaran Jadwal Mengajar Guru - SMA PGRI 1 Kudus</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 2px 0 0 0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #374151;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #6b7280;
        }
        .meta-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 6px 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 10.5px;
        }
        .summary-boxes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .summary-box {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: center;
            background: #fdfdfd;
        }
        .summary-box .val {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }
        .summary-box .lbl {
            font-size: 9px;
            text-transform: uppercase;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #9ca3af;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 9px;
            font-weight: 600;
            border-radius: 3px;
            border: 1px solid #d1d5db;
        }
        .badge-approved { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .badge-pending { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .badge-rejected { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .badge-other { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 200px;
            text-align: center;
            font-size: 10.5px;
        }
        .signature-space {
            height: 55px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
        .btn-print {
            background: #0093dd;
            color: #fff;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
        <button class="btn-print" onclick="window.print()">Cetak Dokumen / Simpan PDF</button>
        <a href="{{ route('schedule-swaps.index') }}" style="color: #4b5563; font-size: 11px; text-decoration: none;">&larr; Kembali ke Laporan Web</a>
    </div>

    <div class="header">
        <h1>SMA PGRI 1 KUDUS</h1>
        <h2>Laporan Pertukaran Jadwal Mengajar Guru</h2>
        <p>Jl. Mejobo No. 99 Kudus &bull; Sistem Monitoring SCP Kurikulum</p>
    </div>

    <div class="meta-strip">
        <div>
            <strong>Periode:</strong> {{ \Carbon\Carbon::parse($activeFilters['start_date'])->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($activeFilters['end_date'])->translatedFormat('d F Y') }}
            &bull; <strong>Putaran:</strong> {{ $activeFilters['cycle'] ?? 'Semua' }}
        </div>
        <div>
            <strong>Dicetak Pada:</strong> {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y H:i') }} WIB
        </div>
    </div>

    <div class="summary-boxes">
        <div class="summary-box">
            <div class="val">{{ $summary['total'] }}</div>
            <div class="lbl">Total Pengajuan</div>
        </div>
        <div class="summary-box">
            <div class="val" style="color: #059669;">{{ $summary['approved'] }}</div>
            <div class="lbl">Disetujui</div>
        </div>
        <div class="summary-box">
            <div class="val" style="color: #d97706;">{{ $summary['pending'] }}</div>
            <div class="lbl">Menunggu</div>
        </div>
        <div class="summary-box">
            <div class="val" style="color: #dc2626;">{{ $summary['rejected_cancelled_expired'] }}</div>
            <div class="lbl">Ditolak / Batal / Expired</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th>Guru Pemohon</th>
                <th>Jadwal Asli</th>
                <th>Guru Pengganti</th>
                <th>Jadwal Pengganti</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 65px;">Jurnal</th>
                <th style="width: 80px;">Waktu Respon</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($swaps as $index => $swap)
                @php
                    $badgeClass = match($swap->status) {
                        'approved'  => 'badge-approved',
                        'pending'   => 'badge-pending',
                        'rejected'  => 'badge-rejected',
                        default     => 'badge-other',
                    };
                    $statusLabel = match($swap->status) {
                        'approved'  => 'Disetujui',
                        'pending'   => 'Menunggu',
                        'rejected'  => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        'expired'   => 'Kedaluwarsa',
                        default     => ucfirst($swap->status),
                    };
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $swap->swap_date?->format('d/m/Y') }}</strong><br>
                        <span style="font-size: 8.5px; color: #6b7280;">{{ $swap->cycle }}</span>
                    </td>
                    <td>
                        <strong>{{ $swap->requester?->fullname }}</strong><br>
                        <span style="font-size: 8.5px; color: #6b7280;">NIP: {{ $swap->requester?->username }}</span>
                    </td>
                    <td>
                        <strong>{{ $swap->requesterSchedule?->subject }}</strong><br>
                        {{ $swap->requesterSchedule?->schoolClass?->class_name }} &bull;
                        {{ $swap->requesterSchedule ? substr($swap->requesterSchedule->start_time, 0, 5) . '-' . substr($swap->requesterSchedule->end_time, 0, 5) : '-' }}
                    </td>
                    <td>
                        <strong>{{ $swap->target?->fullname }}</strong><br>
                        <span style="font-size: 8.5px; color: #6b7280;">NIP: {{ $swap->target?->username }}</span>
                    </td>
                    <td>
                        <strong>{{ $swap->targetSchedule?->subject }}</strong><br>
                        {{ $swap->targetSchedule?->schoolClass?->class_name }} &bull;
                        {{ $swap->targetSchedule ? substr($swap->targetSchedule->start_time, 0, 5) . '-' . substr($swap->targetSchedule->end_time, 0, 5) : '-' }}
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>
                        @if($swap->status === 'approved')
                            <span class="badge {{ $swap->isJournalFilled() ? 'badge-approved' : 'badge-pending' }}">
                                {{ $swap->isJournalFilled() ? 'Sudah Diisi' : 'Belum Diisi' }}
                            </span>
                        @else
                            <span style="color: #9ca3af;">-</span>
                        @endif
                    </td>
                    <td style="font-size: 9px;">
                        {{ $swap->responded_at ? $swap->responded_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td style="font-size: 9px; color: #4b5563;">
                        {{ $swap->note ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; color: #6b7280;">
                        Tidak ada data pertukaran jadwal guru pada periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            Mengetahui,<br>
            <strong>Kepala Sekolah</strong>
            <div class="signature-space"></div>
            <strong>__________________________</strong><br>
            NIP. ........................................
        </div>
        <div class="signature-box">
            Kudus, {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}<br>
            <strong>Waka Kurikulum</strong>
            <div class="signature-space"></div>
            <strong>__________________________</strong><br>
            NIP. ........................................
        </div>
    </div>
</body>
</html>
