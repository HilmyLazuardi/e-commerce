<!DOCTYPE html>
<html>
<head>
	<title>[Larizzka Jaya] {!! $data['title'] !!}</title>
</head>
<body style="padding-top:40px;margin:0 auto;background: #4fa8d8;">
	<table style="width: 100%;max-width: 600px;margin:40px auto 0;font-family: Arial, Helvetica, sans-serif;border-collapse: collapse;">
		<thead>
			<tr>
				<th style="padding: 0px 0;text-align: left;vertical-align: bottom;padding-bottom: 15px;"><img style="display: block;margin:0 auto;" src="{{ asset('web/images/logo_app_blue.png') }}"></th>
			</tr>
		</thead>
		<tbody style="background: #cfefff;border-radius: 10px;">
			<tr>
				<td style="color:#3A3A3A;background:#cfefff;font-family: Arial, Helvetica, sans-serif;padding:20px 40px 20px 40px;text-align: center;border-top: 1px solid #66b9e8;">
					<h2 style="color:#3A3A3A;font-size: 16px;">Selangkah lagi untuk jadi bagian dari Larizzka Jaya!</h2>
				</td>
			</tr>
			<tr>
				<td style="padding:0 40px 20px 40px;color:#3A3A3A;text-align: center;">
					<p style="margin:0;">Silakan tanda tangan Perjanjian maks. <strong>{!! $data['expired_date'] !!}.</strong></p>
					<p>Klik tombol di bawah untuk mengakses dokumen perjanjian.</p>
					<a href="{!! $data['link'] !!}" style="background: #4fa8d8;color:#FFF;padding:10px 20px;text-decoration: none;min-width: 200px;display: inline-block;">Lihat Perjanjian</a>
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td style="text-align: center;color:#FFF;padding:20px 20px 20px;border-top: 1px solid #66b9e8;font-size: 14px;">
					<strong>Butuh bantuan?</strong> Kami bisa dihubungi lewat WhatsApp di <a href="{!! $data['wa_link'] !!}" style="color: #FFF;">{!! $data['wa_number'] !!}</a> <br>atau email ke <a href="mailto:help@larizzkajaya.com" style="color:#FFF;">help@larizzkajaya.com</a>
				</td>
			</tr>
			<tr>
				<td style="color:#FFF;text-align: center;font-size: 14px;padding:0 0 20px;">
					Larizzka Jaya<br>
					Jl. Nasional 12, RT.6/RW.2, Grogol Selatan., Kec. Kebayoran Lama, Kota Jakarta Selatan<br>
					Daerah Khusus Ibukota Jakarta 12220<br>
					Indonesia
				</td>
			</tr>
		</tfoot>
	</table>
</body>
</html>