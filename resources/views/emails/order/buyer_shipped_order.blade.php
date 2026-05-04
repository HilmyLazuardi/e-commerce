@php
    use App\Libraries\Helper;
    use App\Libraries\HelperWeb;

    $company_info = HelperWeb::get_company_info();
@endphp

<!DOCTYPE html>
<html>
<head>
	<title>[Larizzka Jaya] {!! $data['title'] !!}</title>
</head>
<body style="padding-top:40px;margin:0 auto;background: #234100;">
	<table style="width: 100%;max-width: 600px;margin:40px auto 0;font-family: Arial, Helvetica, sans-serif;border-collapse: collapse;">
		<thead>
			<tr>
				<th style="padding: 0px 0;text-align: left;vertical-align: bottom;padding-bottom: 15px;"><img style="margin:0 auto;display: block;" src="{{ asset('web/images/logo_app_blue.png') }}"></th>
			</tr>
		</thead>
		<tbody style="background: #BEA365;">
			<tr>
				<td colspan="2" style="color:#3A3A3A;font-family: Arial, Helvetica, sans-serif;padding:0 40px 20px 40px;text-align: center;border-top: 1px solid #66b9e8;">
					<p style="padding:20px 0 0">Halo <strong>{!! $data['user_name'] !!}</strong>,<br></p>
					<p style="color:#3A3A3A;margin:0;">
						Produk yang kamu pesan sudah dikirim. Silakan cek nomor resi kamu dalam waktu <strong>1x24 jam</strong>, ya.
						Terima kasih sudah membeli produk di Emas Korner.
					</p>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding:20px;">
					<table style="width: 400px;background:#F0F0F0;border-radius: 10px;padding:20px;margin:0 auto 20px;">
						<tr>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: left;">Order ID</td>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: right;"><strong>{{ $data['order_id'] }}</strong></td>
						</tr>
						<tr>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: left;">Tanggal pemesanan</td>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: right;"><strong>{{ $data['tanggal'] }}</strong></td>
						</tr>
						<tr>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: left;">Kurir</td>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: right;"><strong>{{ $data['service'] }}</strong></td>
						</tr>
						<tr>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: left;">No. resi</td>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: right;"><strong>{{ $data['resi_number'] }}</strong></td>
						</tr>
						<tr>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: left;">Metode pembayaran</td>
							<td style="padding-bottom:10px;color:#24536d;font-size:14px;text-align: right;"><strong>{{ $data['payment_method'] }}</strong></td>
						</tr>
					</table>

					{{-- <table style="width: 400px;margin:0 auto 20px;">
						<tr>
							<td colspan="2"><a href="https://anteraja.id/id/tracking" style="width:160px;color:#fff;font-size:14px;font-weight:bold;border:1px solid #234100;background:#234100;text-align: center;line-height: 36px;margin:0 auto;display: block;text-decoration: none;border-radius: 5px;">Lacak Pesanan Saya</a></td>
						</tr>
					</table> --}}

					<table style="width: 400px;margin:0 auto;border-collapse: collapse;">
						<tr>
							<td colspan="2" style="padding-bottom: 5px;color:#234100;"><strong>Detail Pesanan</strong></td>
						</tr>
						@foreach ($data['orders'] as $order_details)
							<tr>
								<td colspan="2" style="font-size: 14px;color:#24536d;padding-top: 10px;">Order ID: <span style="color:#234100;">{{ $order_details[0]->transaction_id }}</span></td>
							</tr>
							{{-- <tr>
								<td colspan="2" style="font-size: 14px;color:#24536d;">Toko: <span style="color:#4fa8d8;font-weight: bold;">{{ $order_details[0]->store_name }}</span></td>
							</tr> --}}
							{{-- <tr>
								<td colspan="2" style="font-size: 14px;color:#24536d;">Estimasi Tiba: <span style="color:#3A3A3A;font-weight: bold;">{{ Helper::convert_date_to_indonesian(Helper::convert_timestamp($order_details[0]->estimate_arrived_at, 'Y-m-d', env('APP_TIMEZONE', 'UTC'))) }}</span></td>
							</tr> --}}
							@foreach ($order_details as $order_item)
								@php
									$product_image = asset($order_item->product_image);
									if (!empty($order_item->product_variant_image)) {
										$product_image = asset($order_item->product_variant_image);
									}
								@endphp
								<tr>
									<td style="padding-right: 10px;width: 300px;border-bottom:1px dashed #24536d;padding-bottom: 10px;vertical-align: top;">
										<span style="color:#24536d;font-size: 14px;display: block;padding-top: 5px;"><strong>{{ $order_item->product_name }}</strong></span>
										<span style="color:#24536d;font-size: 12px;display: block;padding-top: 5px;">Jumlah: {{ $order_item->qty }} Pcs</span>
										<span style="color:#24536d;font-size: 12px;display: block;padding-top: 5px;">Varian: {{ $order_item->product_variant_name }}</span>
										<span style="color:#24536d;font-size: 12px;display: block;padding-top: 5px;">Berat: {{ $order_item->weight }} gram</span>
									</td>
                                    <td style="border-bottom:1px dashed #24536d;vertical-align: top;padding-top: 5px;"><img style="width: 80px;" src="{{ $product_image }}"></td>
								</tr>
							@endforeach
							<tr>
								<td colspan="2" style="padding-bottom: 5px;padding-top: 5px;color:#234100;"><strong>Alamat Pengiriman</strong></td>
							</tr>
							<tr>
								<td colspan="2" style="font-size: 14px;color:#24536d;padding-bottom: 15px;border-bottom:1px solid #24536d;">
									<span style="margin-bottom: 5px;display: block;">{{ $order_details[0]->receiver_name }}</span>
									{{ $order_details[0]->receiver_address }}
									<br>
									{{ $order_details[0]->receiver_village_name }}, {{ $order_details[0]->receiver_sub_district_name }}, {{ $order_details[0]->receiver_city_name }}, {{ $order_details[0]->receiver_province_name }} {{ $order_details[0]->receiver_postal_code }}
									<br>
									{{ $order_details[0]->receiver_phone }}
								</td>
							</tr>
						@endforeach
					</table>

					<table style="width: 400px;margin:0 auto 20px;">
						<tr>
							<td colspan="2" style="padding-bottom: 10px;color:#234100;"><strong>Ringkasan Pembayaran</strong></td>
						</tr>
						<tr>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: left;">Subtotal</td>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: right;">{{ $data['subtotal'] }}</td>
						</tr>
						<tr>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: left;">Ongkos kirim</td>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: right;">{{ $data['shipping_fee'] }}</td>
						</tr>
                        {{-- @if ($data['insurance_shipping_fee'] > 0) --}}
                            <tr>
                                <td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: left;">Asuransi</td>
                                <td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: right;">{{ $data['insurance_shipping_fee'] }}</td>
                            </tr>
                        {{-- @endif --}}
                        {{-- @if ($data['invoice']->discount_amount > 0)
                            <tr>
                                <td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: left;">Promo (<strong>{{ $data['invoice']->voucher_code }}</strong>)</td>
                                <td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: right;">-{!! Helper::currency_format($data['invoice']->discount_amount, 0, ',', '.', 'Rp', null) !!}</td>
                            </tr>
                        @endif --}}
						<tr>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: left;"><strong>Total</strong></td>
							<td style="padding-bottom:5px;font-size:14px;vertical-align:top;color:#24536d;text-align: right;"><strong>{{ $data['total_price'] }}</strong></td>
						</tr>
					</table>

					<table style="width: 400px;margin:0 auto 20px;">
						<tr>
							<td><a href="{{ route('web.order.history') }}" style="width:180px;color:#000;font-size:14px;font-weight:bold;border:1px solid #000;text-align: center;line-height: 36px;display: block;text-decoration: none;">Riwayat Pesanan</a></td>
							<td><a href="{{ route('web.home') }}" style="float:right;width:160px;color:#fff;font-size:14px;font-weight:bold;border:1px solid #234100;background:#234100;text-align: center;line-height: 36px;display: block;text-decoration: none;">Belanja Lagi</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</tbody><tfoot>
			{{-- <tr>
				<td colspan="2" style="text-align: center;color:#FFF;padding:20px 20px 20px;font-size: 14px;">
                    Jangan menginformasikan bukti dan data pembayaran kepada pihak manapun kecuali<br> 
                    Kaya Halal Market!
				</td>
			</tr> --}}
			<tr>
				<td colspan="2" style="text-align: center;color:#FFF;padding:20px 20px 20px;font-size: 14px;">
					<strong>Butuh bantuan?</strong> Kami selalu bisa dihubungi lewat <strong>WhatsApp di {!! env('COUNTRY_CODE').$company_info->wa_phone !!}</strong> atau email ke <a href="mailto:help@larizzkajaya.com" style="color: #fff;"><strong>help@larizzkajaya.com</strong></a>
					{{-- <strong>Need help?</strong> Ask at <a href="mailto:team@emaskorner.com" style="color: #fff;">team@emaskorner.com</a> or Visit our Help Center --}}
				</td>
			</tr>
			<tr>
				<td colspan="2" style="color:#FFF;text-align: center;font-size: 14px;padding:0 0 20px;">
					Larizzka Jaya<br>
					Jl. Nasional 12, RT.6/RW.2, Grogol Selatan., Kec. Kebayoran Lama, Kota Jakarta Selatan<br>
					Daerah Khusus Ibukota Jakarta 12220<br>
					Indonesia Follow us on<br>
					<a href="https://www.instagram.com/larizzkajaya/" style="display: inline-block;margin-top:5px;"><img style="width: 30px;" src="{{ asset('web/images/instagram.png') }}"></a><br>
					<p style="font-size: 12px;">Copyright by Larizzka Jaya</p>
				</td>
			</tr>
		</tfoot>
	</table>
</body>
</html>