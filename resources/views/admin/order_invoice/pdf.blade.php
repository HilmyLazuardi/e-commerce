<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Rekap Invoice</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 6px; }
        th { background-color: #eee; }
        h2, p { text-align: center; margin: 0; }
        p.sub { font-size: 11px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Rekap Invoice</h2>
    <p class="sub">Periode: {{ $daterange }} | Status: {{ $status ?: 'Semua' }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Nama Pembeli</th>
                <th>Subtotal</th>
                <th>Biaya Kirim</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $row->invoice_no }}</td>
                    <td>{{ $row->buyer_name }}</td>
                    <td>Rp{{ number_format($row->subtotal, 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($row->shipping_fee, 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($row->total_amount, 0, ',', '.') }}</td>
                    <td>
                        @if ($row->is_cancelled == 1)
                            Cancelled
                        @elseif ($row->paid_at && $row->payment_status == 1)
                            Paid
                        @else
                            Unpaid
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
