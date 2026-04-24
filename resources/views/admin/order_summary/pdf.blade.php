<!DOCTYPE html>
<html>
<head>
    <title>Rekap Order Pesanan Terlaris</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h3>Rekap Order Pesanan Terlaris</h3>
    <p>Periode: {{ $daterange ?? 'Semua Periode' }}</p>

    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Nama Penjual</th>
                <th>Harga</th>
                <th>Unit Terjual</th>
                <th>Omzet</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->seller_name }}</td>
                    <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td>{{ $item->unit_terjual }}</td>
                    <td>Rp{{ number_format($item->omzet, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
