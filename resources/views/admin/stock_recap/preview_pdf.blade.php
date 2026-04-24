<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 6px; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 0; }
        .sub-title { text-align: center; margin-top: 0; font-size: 12px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p class="sub-title">Periode: {{ $daterange }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Toko</th>
                <th>Status Stok</th>
                <th>Total Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->store_name }}</td>
                    <td>
                        @if ($row->total_qty <= 0)
                            Habis
                        @else
                            Aman
                        @endif
                    </td>
                    <td>{{ $row->total_qty }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
