<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Pesanan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #eee; }
    </style>
</head>
@php
    function status_label($status) {
        switch ($status) {
            case 1:
                return 'Menunggu Pembayaran';
            case 2:
                return 'Siap Dikirim (Terbayar)';
            case 3:
                return 'Sudah Dikirim';
            case 4:
                return 'BATAL';
            case 5:
                return 'Refunded';
            case 6:
                return 'Selesai';
            default:
                return 'UNKNOWN';
        }
    }
@endphp
<body>
    <h2>Rekap Pesanan</h2>
    @if(!empty($daterange))
        <p><strong>Periode:</strong> {{ $daterange }}</p>
    @endif
    @if(!empty($status_label))
        <p><strong>Status:</strong> {{ status_label((int) $status_label) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No Transaksi</th>
                <th>Nama Toko</th>
                <th>Nama Seller</th>
                <th>Telp Seller</th>
                <th>Nama Pembeli</th>
                <th>Telp Pembeli</th>
                <th>Subtotal</th>
                <th>Ongkir</th>
                <th>Fee Admin</th>
                <th>Total</th>
                <th>Total Diterima Seller</th>
                {{-- <th>Status</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr>
                    <td>{{ \App\Libraries\Helper::locale_timestamp($item->created_at, 'd/m/Y H:i') }}</td>
                    <td>{{ $item->transaction_id }}</td>
                    <td>{{ $item->store_name }}</td>
                    <td>{{ $item->seller_name }}</td>
                    <td>{{ '0' . $item->seller_phone }}</td>
                    <td>{{ $item->buyer_name }}</td>
                    <td>{{ $item->phone_number }}</td>
                    <td>{{ 'Rp' . number_format($item->price_subtotal, 0, ',', '.') }}</td>
                    <td>{{ 'Rp' . number_format($item->price_shipping, 0, ',', '.') }}</td>
                    <td>{{ 'Rp' . number_format($item->amount_fee, 0, ',', '.') }}</td>
                    <td>{{ 'Rp' . number_format($item->price_total, 0, ',', '.') }}</td>
                    <td>{{ 'Rp' . number_format(($item->price_total - $item->amount_fee), 0, ',', '.') }}</td>
                    {{-- <td>{{ status_label($item->progress_status) }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
