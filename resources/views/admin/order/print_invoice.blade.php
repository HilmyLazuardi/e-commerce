<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Invoice - {{ $data->transaction_id }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: #f9fafc;
      margin: 0;
      padding: 40px;
      color: #1f2d3d;
    }
    .invoice-container {
      max-width: 900px;
      margin: auto;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      padding: 40px;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 20px;
      margin-bottom: 30px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .brand img {
      height: 50px;
    }
    .brand-title {
      font-size: 20px;
      font-weight: 700;
      color: #d4af37;
    }
    .status-label {
      background: #d69e6a;
      color: white;
      padding: 6px 16px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    .section {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
      gap: 40px;
    }
    .section address {
      line-height: 1.6;
      font-style: normal;
      color: #374151;
    }
    .info {
      font-size: 14px;
      line-height: 1.6;
      color: #374151;
    }
    .section-merged {
      display: flex;
      justify-content: space-between;
      gap: 40px;
      margin-bottom: 30px;
    }
    .section-merged > div {
      flex: 1;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-top: 10px;
    }
    table thead {
      background-color: #f3f4f6;
    }
    table th, table td {
      padding: 12px 16px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
      font-size: 14px;
    }
    table th {
      font-weight: 600;
    }
    .totals {
      margin-top: 30px;
      display: flex;
      justify-content: flex-end;
    }
    .totals table {
      width: 100%;
      max-width: 400px;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      background: #f9fafb;
    }
    .totals td {
      padding: 12px 16px;
      font-size: 14px;
    }
    .totals tr:not(:last-child) td {
      border-bottom: 1px solid #e5e7eb;
    }
    .totals tr:last-child td {
      font-weight: bold;
      font-size: 16px;
      background-color: #f3f4f6;
    }
    .footer {
      text-align: center;
      font-size: 12px;
      color: #6b7280;
      margin-top: 60px;
    }
    @media print {
      body {
        -webkit-print-color-adjust: exact;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
@php
    use App\Libraries\Helper;

    $pagetitle = ucwords(lang('order', $translations));
    // if (isset($data)) {
    //    $pagetitle .= ' ('.ucwords(lang('edit', $translations)).')';
    // } else {
    //    $pagetitle .= ' ('.ucwords(lang('new', $translations)).')';
    //    $data       = null;
    // }
@endphp
<body onload="window.print()">
  <div class="invoice-container">
    <div class="header">
      <div class="brand">
        <img src="{{ asset('web/images/logo_app_blue.png') }}" alt="Larizzka Jaya Logo" />
      </div>
      <div class="status-label">INVOICE</div>
    </div>

    <div class="section-merged">
      <div>
        <h3>Pengirim:</h3>
        <address>
          {{ $seller->fullname }}<br />
          {{ $seller->village_name }}, {{ $seller->sub_district_name }}<br />
          {{ $seller->city_name }}, {{ $seller->province_name }} {{ $seller->village_postal_codes }}<br />
          Tel: {{ $seller->phone_number }}<br />
          Email: {{ $seller->email }}
        </address>
      </div>
      <div>
        <h3>Penerima:</h3>
        <address>
          {{ $data->buyer_fullname }}<br />
          {{ $data->shipment_address_details }}<br />
          {{ $data->village_name }}, {{ $data->sub_district_name }}<br />
          {{ $data->city_name }}, {{ $data->province_name }} {{ $data->village_postal_codes }}
        </address>
      </div>
      <div class="info">
        <p><strong>ID Transaksi:</strong> {{ $data->transaction_id }}</p>
        <p><strong>Dibuat:</strong> {{ Helper::locale_timestamp($data->created_at, 'j/m/Y H:i', false) }} WIB</p>
        @if ($data->paid_at)
        <p><strong>Dibayar:</strong> {{ Helper::locale_timestamp($data->paid_at, 'j/m/Y H:i', false) }} WIB</p>
        @endif
        @if ($data->shipped_at)
        <p><strong>Dikirim:</strong> {{ Helper::locale_timestamp($data->shipped_at, 'j/m/Y', false) }}</p>
        @endif
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Produk</th>
          <th>SKU</th>
          <th>Varian</th>
          <th>Harga</th>
          <th>Qty</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order_details as $item)
        <tr>
          <td>{{ $item->product_name }}</td>
          <td>{{ $item->variant_sku }}</td>
          <td>{{ $item->variant_name }}</td>
          <td>Rp{{ number_format($item->price_per_item, 0, ',', '.') }}</td>
          <td>{{ $item->qty }}</td>
          <td>Rp{{ number_format($item->price_subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="totals">
      <table>
        <tr>
          <td>Subtotal</td>
          <td style="text-align:right">Rp{{ number_format($data->price_subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <td>Ongkos Kirim</td>
          <td style="text-align:right">Rp{{ number_format($data->price_shipping, 0, ',', '.') }}</td>
        </tr>
        @if ($data->use_insurance_shipping)
        <tr>
          <td>Asuransi</td>
          <td style="text-align:right">Rp{{ number_format($data->insurance_shipping_fee, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
          <td>Total</td>
          <td style="text-align:right">Rp{{ number_format($data->price_total, 0, ',', '.') }}</td>
        </tr>
      </table>
    </div>

    <div class="footer">
      Terima kasih telah berbelanja di toko kami!<br />
      Untuk bantuan, hubungi layanan pelanggan kami.
    </div>
  </div>
</body>
</html>