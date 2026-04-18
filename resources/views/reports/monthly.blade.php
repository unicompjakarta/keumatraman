<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan - {{ $branch->name }} - {{ sprintf('%02d', $month) }}/{{ $year }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f3f4f6;
            color: #111827;
        }
        .toolbar {
            width: 210mm;
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            background: white;
            padding: 24px 28px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }
        .title {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .title h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .title h2 {
            margin: 8px 0 0;
            font-size: 16px;
            font-weight: 600;
        }
        .meta, .summary, .table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta td {
            padding: 5px 6px;
            font-size: 14px;
        }
        .section {
            margin-top: 22px;
        }
        .section h3 {
            margin: 0 0 10px;
            font-size: 16px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
        }
        .table th, .table td, .summary td {
            border: 1px solid #9ca3af;
            padding: 8px 10px;
            font-size: 13px;
        }
        .table th {
            background: #f3f4f6;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary {
            margin-top: 18px;
        }
        .summary td:first-child {
            width: 70%;
        }
        .summary tr:last-child td {
            font-weight: bold;
            background: #f9fafb;
        }
        .notes {
            margin-top: 10px;
            white-space: pre-line;
            font-size: 14px;
            line-height: 1.6;
        }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .page {
                margin: 0;
                width: 100%;
                min-height: auto;
                box-shadow: none;
                padding: 0;
            }
            @page {
                size: A4;
                margin: 16mm;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a
            class="btn"
            style="text-decoration:none; display:inline-block; margin-right:8px;"
            href="{{ route('financial-reports.export-excel', ['branch_id' => $branch->id, 'month' => $month, 'year' => $year]) }}"
        >
            Convert Excel
        </a>
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="page">
        <div class="title">
            <h1>LAPORAN BULAN {{ \Carbon\Carbon::parse($year . '-' . $month . '-01')->translatedFormat('F Y') }}</h1>
            <h2>{{ $branch->name }}</h2>
        </div>

        <table class="meta">
            <tr>
                <td width="180"><strong>Cabang</strong></td>
                <td width="10">:</td>
                <td>{{ $branch->name }}</td>
            </tr>
            <tr>
                <td><strong>Periode</strong></td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }} {{ $year }}</td>
            </tr>
            <tr>
                <td><strong>Jumlah Pegawai</strong></td>
                <td>:</td>
                <td>{{ $report->employee_total }}</td>
            </tr>
            <tr>
                <td><strong>Pegawai Ikut Iuran</strong></td>
                <td>:</td>
                <td>{{ $report->employee_contributor_total }}</td>
            </tr>
            <tr>
                <td><strong>Target Capaian Bulanan</strong></td>
                <td>:</td>
                <td>Rp {{ number_format($report->monthly_target_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Infak Masuk</strong></td>
                <td>:</td>
                <td>Rp {{ number_format($report->mandatory_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Kiriman</strong></td>
                <td>:</td>
                <td>Rp {{ number_format($report->total_sent_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Kirim</strong></td>
                <td>:</td>
                <td>{{ $report->sent_date ? \Carbon\Carbon::parse($report->sent_date)->translatedFormat('d F Y') : '-' }}</td>
            </tr>
        </table>

        <div class="section">
            <h3>Daftar Pengeluaran</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th width="120">Tanggal</th>
                        <th width="140">Kategori</th>
                        <th>Uraian</th>
                        <th width="150">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenseItems as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($item->entry_date)->format('d-m-Y') }}</td>
                            <td>{{ $item->category ?? '-' }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data pengeluaran</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($expenseTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Daftar Pengajuan Dana</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th width="140">Periode</th>
                        <th width="140">Kategori</th>
                        <th>Uraian</th>
                        <th width="150">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposalItems as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">
                                {{ $item->target_month ? sprintf('%02d', $item->target_month) : '-' }}/{{ $item->target_year ?? '-' }}
                            </td>
                            <td>{{ $item->category ?? '-' }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data pengajuan</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($proposalTotal ?? 0, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Ringkasan</h3>
            <table class="summary">
                <tr>
                    <td>Saldo Awal</td>
                    <td class="text-right">Rp {{ number_format($report->opening_balance, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Realisasi Pusat Bulan Ini</td>
                    <td class="text-right">Rp {{ number_format($report->central_fund_received, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Total Pengeluaran {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }}</td>
                    <td class="text-right">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Saldo Akhir</td>
                    <td class="text-right">Rp {{ number_format($report->closing_balance, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        @if($report->notes)
            <div class="section">
                <h3>Catatan</h3>
                <div class="notes">{{ $report->notes }}</div>
            </div>
        @endif
    </div>
</body>
</html>
