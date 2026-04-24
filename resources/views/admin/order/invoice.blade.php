@extends('_template_adm.master')

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

@section('title', $pagetitle)

@section('content')
    <div class="">
        <!-- message info -->
        @include('_template_adm.message')

        <div class="page-title">
            <div class="title_left">
                <h3>{!! $pagetitle !!}</h3>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{!! ucwords(lang('order', $translations)) !!}</h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li style="float: right !important;"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <form action="{{ route('admin.order.request_pickup') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin untuk request pickup sekarang?\n\n(pesanan harus sudah dipacking dan siap kirim)');">
                            @csrf
                            <input type="hidden" name="id" value="{{ $raw_id }}">
                            <section class="content invoice">
                                <!-- title row -->
                                <div class="row">
                                    <div class="col-xs-12 invoice-header">
                                        <h1>
                                            <small class="pull-right">{{ $data->transaction_id }}</small>
                                        </h1>
                                        <h1 style="font-size: 32px !important;">
                                            {{-- Rules "Progress Status" (1:waiting for payment | 2:paid | 3:shipped | 4:cancel) --}}
                                            @switch($data->progress_status)
                                                @case(1)
                                                    <label class="label label-info">Menunggu Pembayaran</label>
                                                    @break
                                                @case(2)
                                                    <label class="label label-warning">Siap Dikirim (Terbayar)</label>
                                                    @break
                                                @case(3)
                                                    <label class="label label-success">Telah Dikirim (Selesai)</label>
                                                    @break
                                                @case(4)
                                                    <label class="label label-danger">BATAL</label>
                                                    @break
                                                @case(5)
                                                    <label class="label label-default" style="background:#D3A381; color:#FFFFFF;">Refunded</label>
                                                    @break
                                                @case(6)
                                                    <label class="label label-default" style="background:#4CAF50; color:#FFFFFF;">Selesai</label>
                                                    @break
                                                @default
                                                    <label class="label label-default">{{ ucwords(lang('unknown', $translations)) }}</label>
                                            @endswitch
                                        </h1>
                                    </div>
                                    <!-- /.col -->
                                </div>

                                <!-- info row -->
                                <div class="row invoice-info" style="margin-top: 10px">
                                    <div class="col-sm-4 invoice-col">
                                        <b>Pengirim :</b>
                                        <address>
                                            <strong>{{ $seller->fullname }}</strong>
                                            <br>{{ $seller->village_name }}, {{ $seller->sub_district_name }}
                                            <br>{{ $seller->city_name }}, {{ $seller->province_name }} {{ $seller->village_postal_codes }}
                                            <br>{!! ucwords(lang('phone', $translations)) !!}: {{ $seller->phone_number }}
                                            <br>{!! ucwords(lang('email', $translations)) !!}: {{ $seller->email }}
                                        </address>
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-sm-4 invoice-col">
                                        <b>Penerima :</b>
                                        <address>
                                            <strong>{{ $data->buyer_fullname }}</strong>
                                            <br>{{ $data->shipment_address_details }}
                                            <br>{!! $data->village_name !!}, {!! $data->sub_district_name !!}
                                            <br>{{ $data->city_name }}, {!! $data->province_name !!} {!! $data->village_postal_codes !!}
                                            {{-- <br>{!! ucwords(lang('phone', $translations)) !!}: {!! $data->buyer_phone_number !!} --}}
                                            {{-- <br>{!! ucwords(lang('email', $translations)) !!}: {!! $data->buyer_email !!} --}}
                                        </address>
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-sm-4 invoice-col">
                                        <b>Tanggal Transaksi Dibuat :</b> {{ Helper::locale_timestamp($data->created_at, 'j/m/Y H:i', false) }} WIB
                                        @if ($data->progress_status == 1)
                                            {{-- hanya tampilkan jika masih Menunggu Pembayaran --}}
                                            <br>
                                            <b>Tanggal Transaksi Kadaluarsa :</b> {{ Helper::locale_timestamp($data->expired_at, 'j/m/Y H:i', false) }} WIB
                                        @endif
                                        @if ($data->paid_at)
                                            {{-- hanya tampilkan jika sudah membayar --}}
                                            <br>
                                            <b>Tanggal Pembayaran :</b> {{ Helper::locale_timestamp($data->paid_at, 'j/m/Y H:i', false) }} WIB
                                        @endif
                                    </div>
                                    <!-- /.col -->
                                </div>
                                <!-- /.row -->

                                <!-- Table row -->
                                <div class="row">
                                    <div class="col-xs-12 table">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Gambar</th>
                                                    <th>SKU</th>
                                                    <th>Produk</th>
                                                    <th>Varian</th>
                                                    <th>Harga</th>
                                                    <th>Qty</th>
                                                    <th>Subtotal</th>
                                                    <th>Catatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if (isset($order_details[0]))
                                                    @foreach ($order_details as $item)
                                                        <tr>
                                                            <td><img src="{{ asset($item->product_image) }}" alt="{!! $item->product_name !!}" style="max-height:100px;"></td>
                                                            <td>{!! $item->variant_sku !!}</td>
                                                            <td>{!! $item->product_name !!}</td>
                                                            <td>{!! $item->variant_name !!}</td>
                                                            <td>Rp{!! number_format($item->price_per_item, 0, ',', '.') !!}</td>
                                                            <td>{!! $item->qty !!}</td>
                                                            <td>Rp{!! number_format($item->price_subtotal, 0, ',', '.') !!}</td>
                                                            <td>
                                                                @if ($item->remarks)
                                                                    {!! $item->remarks !!}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.col -->
                                </div>
                                <!-- /.row -->

                                <div class="row">
                                    <!-- accepted payments column -->
                                    <div class="col-sm-6" style="margin-top: 10px">
                                        <p class="lead">Informasi Seller</p>
                                        <p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
                                            @php
                                                // tanggal kirim direkomendasi max 30 hari setelah campaign berakhir
                                                $tanggal_kirim_expired_raw = date('j/m/Y', strtotime($order_details[0]->product_campaign_end . ' +30 days'));
                                                $arr_tmp = explode('/', $tanggal_kirim_expired_raw);
                                                $arr_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                                $tanggal_kirim_expired = $arr_tmp[0].' '.$arr_bulan[(int) $arr_tmp[1]].' '.$arr_tmp[2];
                                            @endphp
                                            Harap seller mengirimkan pesanan sebelum tanggal {{ $tanggal_kirim_expired }}.
                                        </p>

                                        <p class="lead">Total Berat : {{ number_format($data->shipment_total_weight, 0, ',', '.') }} gram</p>
                                        <p class="lead">Pengiriman : {{ ($data->shipper_name) }} ({{ $data->shipper_service_type }})</p>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="shipping_number">Nomor Resi</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input type="text" value="{{ $data->shipping_number }}" name="shipping_number" id="shipping_number" class="form-control col-xs-12" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-sm-6" style="margin-top: 10px">
                                        <p class="lead">
                                            @if ($data->shipped_at)
                                                @php
                                                    $date_raw = Helper::locale_timestamp($data->shipped_at, 'j/m/Y', false);
                                                    $arr_tmp = explode('/', $date_raw);
                                                    $date_indo = $arr_tmp[0].' '.$arr_bulan[(int) $arr_tmp[1]].' '.$arr_tmp[2];
                                                @endphp
                                                Pesanan telah dikirim : {{ $date_indo }}
                                            @else
                                                Mohon dikirim sebelum : {{ $tanggal_kirim_expired }}
                                            @endif
                                        </p>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <th style="width:50%">Subtotal:</th>
                                                        <td>Rp{{ number_format($data->price_subtotal, 0, ',', '.') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Ongkos Kirim:</th>
                                                        <td>Rp{{ number_format($data->price_shipping, 0, ',', '.') }}</td>
                                                    </tr>
                                                    @if ($data->use_insurance_shipping)
                                                        <tr>
                                                            <th>Asuransi:</th>
                                                            <td>Rp{{ number_format($data->insurance_shipping_fee, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th>TOTAL:</th>
                                                        <td>Rp{{ number_format($data->price_total, 0, ',', '.') }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /.col -->
                                    @if ($data->progress_status == 5 && ($data->refund_photo || $data->refund_note))
                                        <div class="col-sm-12" style="margin-top: 20px">
                                            <h4>Informasi Refund</h4>
                                            <div class="row">
                                                @if ($data->refund_photo)
                                                    <div class="col-xs-4">
                                                        <p><strong>Foto Refund:</strong></p>
                                                        <img src="{{ asset($data->refund_photo) }}" alt="Foto Refund" style="max-width: 100%; max-height: 200px;">
                                                    </div>
                                                @endif
                                                @if ($data->refund_note)
                                                    <div class="col-xs-8">
                                                        <p><strong>Catatan Refund:</strong></p>
                                                        <div class="well well-sm">{{ $data->refund_note }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <!-- /.row -->

                                <div class="ln_solid"></div>

                                <!-- this row will not appear when printing -->
                                <div class="row no-print">
                                    <div class="col-xs-12">
                                        {{-- <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
                                        <button type="submit" class="btn btn-success">Request Pickup</button> --}}
                                        @if ($data->payment_status)
                                            @if ($data->shipping_number == '-')
                                                <button type="submit" class="btn btn-success">Request Pickup</button>
                                            @elseif(Helper::get_diff_dates($data->shipped_at) < 1)
                                                <button type="button" class="btn btn-primary" onclick="print_label();"><i class="fa fa-print"></i> Print Label Pengiriman</button>
                                            @endif
                                                {{-- <button type="button" class="btn btn-primary" onclick="print_label();"><i class="fa fa-print"></i> Print Label Pengiriman</button> --}}
                                        @endif
                                        <a href="{{ route('admin.order.print_invoice', $data->id) }}" class="btn btn-primary pull-right" style="margin-right: 5px;"><i class="fa fa-download"></i> Print Invoice</a>
                                        <a href="{{ route('admin.order') }}" class="btn btn-default pull-right"><i class="fa fa-times"></i>&nbsp; {!! ucwords(lang('close', $translations)) !!}</a>
                                        {{-- <button class="btn btn-primary pull-right" style="margin-right: 5px;"><i class="fa fa-download"></i> Generate PDF</button> --}}
                                    </div>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
@endsection

@section('script')
    <script>
        function print_label() {
            $('#btnPrint').click();
        }
    </script>
@endsection

@section('shipping_label')
    @if ($data->payment_status == 1)
        <style>
            #pre_print{
                width:10.5cm;
                visibility: visible;
                z-index: 10;
                border:1px dashed #000;
                font-family: 'Arial';
                visibility: hidden;
                position: absolute;
                top: 0;
                left: 0;
                z-index: -1;
            }
            .print_top{
                padding:10px 10px;
                overflow: hidden;
                border-bottom:1px solid #000;
                font-size: 14px;
            }
            .print_middle{
                padding:10px 20px;
            }
            .print_no_invoice{
                margin-bottom: 10px;
                font-weight:bold;
                font-size: 10px;
                font-weight: bold;
            }
            .print_row_clear{
                overflow: hidden;
            }
            .print_row{
                overflow:hidden;
                margin-bottom: 10px;
            }
            .print_row.print_half_box{
                width: 50%;
                float: left;
            }
            .print_rc{
                width: 180px;
                overflow: hidden;
                margin-bottom: 10px;
            }
            .print_rc:last-child{
                margin-bottom: 0;
            }
            .print_half{
                font-size: 11px;
                float: left;
                width: 90px;
            }
            .print_half span{
                font-weight: bold;
                display: block;
            }
            .print_info{
                font-size: 10px;
                border:1px solid #000;
                text-align: center;
                padding:5px 10px;
                border-radius: 3px;
                margin-bottom: 10px;
            }
            .print_info img{
                width: 16px;
                display: inline-block;
                vertical-align: middle;
            }
            .print_half_big{
                width: calc((100% - 20px)/2);
                float: left;
                font-size: 11px;
                margin-right: 20px;
                line-height: 16px;
            }
            .print_half_big:last-child{
                margin-right: 0;
            }
            .print_half_big span{
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
            }
            .print_bottom{
                border-top: 1px dashed #000;
                padding:10px 20px;
                position: relative;
            }
            .print_two_col{
                width:calc((100% - 20px)/2);
                float: left;
                margin-right: 20px;
            }
            .print_two_col:last-child{
                margin-right: 0;
            }
            .text-strip{
                text-decoration: line-through;
            }
            .print_no_resi{
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                display: block;
            }
            @media print {
                section, header, footer, .container, .scroll-top, .modal{
                    visibility: hidden;
                    position: absolute;
                    top: 0;
                    left: 0;
                    display: none;
                }
                #pre_print{
                    width:9cm;
                    visibility: visible;
                    z-index: 10;
                    border:1px dashed #000;
                    font-family: 'Arial';
                    position: static;
                    z-index: 10;
                }
                .print_top{
                    padding:10px 10px;
                    overflow: hidden;
                    border-bottom:1px solid #000;
                    font-size: 12px;
                }
                .print_middle{
                    padding:10px 20px;
                }
                .print_no_invoice{
                    margin-bottom: 10px;
                    font-weight:bold;
                    font-size: 8px;
                    font-weight: bold;
                }
                .print_row_clear{
                    overflow: hidden;
                }
                .print_row{
                    overflow:hidden;
                    margin-bottom: 10px;
                }
                .print_row.print_half_box{
                    width: 50%;
                    float: left;
                }
                .print_rc{
                    width: 180px;
                    overflow: hidden;
                    margin-bottom: 10px;
                }
                .print_rc:last-child{
                    margin-bottom: 0;
                }
                .print_half{
                    font-size: 9px;
                    float: left;
                    width: 90px;
                }
                .print_half span{
                    font-weight: bold;
                    display: block;
                }
                .print_info{
                    font-size: 8px;
                    border:1px solid #000;
                    text-align: center;
                    padding:5px 10px;
                    border-radius: 3px;
                    margin-bottom: 10px;
                }
                .print_info img{
                    width: 16px;
                    display: inline-block;
                    vertical-align: middle;
                }
                .print_half_big{
                    width: calc((100% - 20px)/2);
                    float: left;
                    font-size: 9px;
                    margin-right: 20px;
                    line-height: 14px;
                }
                .print_half_big:last-child{
                    margin-right: 0;
                }
                .print_half_big span{
                    font-weight: bold;
                    display: block;
                    margin-bottom: 5px;
                }
                .print_bottom{
                    border-top: 1px dashed #000;
                    padding:10px 20px;
                    position: relative;
                }
                .print_two_col{
                    width:calc((100% - 20px)/2);
                    float: left;
                    margin-right: 20px;
                }
                .print_two_col:last-child{
                    margin-right: 0;
                }
                .text-strip{
                    text-decoration: line-through;
                }
                .print_no_resi{
                    font-size: 14px;
                    font-weight: bold;
                    text-align: center;
                    display: block;
                }
                #btnPrint{
                    display: none;
                }
            }
        </style>

        <div id="pre_print">
            <div class="print_top">
                <img style="width: 80px;display: block;float: left;position: relative;top: 2px;" src="{{ asset('web/images/logo.png') }}">
                <span style="float: right;display: block;font-family: 'Arial';font-weight: bold;">Non Tunai</span>
            </div>
            <div class="print_middle">
                <div class="print_no_invoice">{{ $data->transaction_id }}</div>
                <div class="print_row_clear">
                    <div class="print_row print_half_box">
                        <div class="print_rc">
                            <div class="print_half">
                                @php
                                    // switch (strtolower($data->shipper_name)) {
                                        // case 'jne':
                                            // $shipper_logo = asset('web/images/jne.jpeg');
                                            // break;
                                        // case 'anteraja':
                                            // $shipper_logo = asset('web/images/anteraja.png');
                                            // break;
                                    // }
                                    if ($data->shipper_name === 'EmasKorner') {
                                        $shipping_type = explode(' ', $data->shipper_service_type);
                                        $shipping = strtoupper($shipping_type[0]);
                                        switch (strtolower($shipping)) {
                                            case 'jne':
                                                $shipper_logo = asset('web/images/jne.jpeg');
                                                break;
                                            case 'anteraja':
                                                $shipper_logo = asset('web/images/anteraja.png');
                                                break;
                                            case 'sicepat':
                                                $shipper_logo = asset('web/images/sicepat.png');
                                                break;
                                            case 'jnt':
                                                $shipper_logo = asset('web/images/jnt.png');
                                                break;
                                            case 'ninja':
                                                $shipper_logo = asset('web/images/ninja.png');
                                                break;
                                            case 'lion':
                                                $shipper_logo = asset('web/images/lion.png');
                                                break;
                                        }
                                    }
                                @endphp
                                @if (isset($shipper_logo))
                                    <img style="width: 50px;" src="{{ $shipper_logo }}">
                                @endif
                            </div>
                            <div class="print_half">
                                {{ $data->shipper_name }}
                                <span>{{ $data->shipper_service_type }}</span>
                            </div>
                        </div>
                        <div class="print_rc">
                            <div class="print_half">
                                Berat
                                <span>{{ ceil($data->shipment_total_weight / 1000) }} Kg</span>
                            </div>
                            <div class="print_half">
                                Ongkir
                                {{-- <span class="text-strip">Rp{{ number_format($data->price_shipping, 0, ',', '.') }}</span> --}}
                                <span class="text">Rp{{ number_format($data->price_shipping, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="print_row print_half_box">
                        <img style="width: 100%;" src="data:image/png;base64,{{ \DNS1D::getBarcodePNG($data->shipping_number, 'C128') }}" />
                        <span class="print_no_resi">{{ $data->shipping_number }}</span>
                    </div>
                </div>
                <div class="print_info">
                    <img src="{{ asset('web/images/icon-money-alt.png') }}">
                    <i>Penjual <strong>tidak perlu</strong> bayar apapun ke kurir, sudah dibayarkan otomatis</i>
                </div>
                <div class="print_row">
                    <div class="print_half_big">
                        Kepada:<br>
                        <strong>{{ $data->receiver_name }}</strong><br>
                        {{ $data->shipment_address_details }}, {!! $data->village_name !!}, {!! $data->sub_district_name !!}, {{ $data->city_name }}, {!! $data->province_name !!}, {!! $data->village_postal_codes !!}<br> 
                        {!! $data->buyer_phone_number !!}
                    </div>
                    <div class="print_half_big">
                        Dari:<br>
                        <strong>Emas Korner</strong><br>
                        Jl. Cililitan Besar No. 32 C. Cililitan,<br>
                        Kramat Jati, Jakarta Timur 13640<br>
                        Indonesia
                    </div>
                </div>
            </div>
            <div class="print_bottom">
                <div class="print_row">
                    <div class="print_half_big">
                        <span>Produk</span>
                    </div>
                    <div class="print_half_big">
                        <div class="print_row">
                            <div class="print_two_col">
                                <span>Varian</span>
                            </div>
                            <div class="print_two_col">
                                <span>Jumlah</span>
                            </div>
                        </div>
                    </div>
                </div>
                @if (isset($order_details[0]))
                    @foreach ($order_details as $item)
                        <div class="print_row">
                            <div class="print_half_big">
                                {!! $item->product_name !!}<br>
                                Keterangan:
                                @if ($item->remarks)
                                    {!! $item->remarks !!}
                                @else
                                    -
                                @endif
                            </div>
                            <div class="print_half_big">
                                <div class="print_row">
                                    <div class="print_two_col">
                                        {!! $item->variant_name !!}
                                    </div>
                                    <div class="print_two_col">
                                        <strong>{!! $item->qty !!}</strong> pcs
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                <img style="width:16px;position: absolute;top: -7px;right: 0;" src="{{ asset('web/images/icon-cut.png') }}">
            </div>
        </div>
        <button id="btnPrint" style="display: none;">Print</button>

        <script>
            const $btnPrint = document.querySelector("#btnPrint");
            $btnPrint.addEventListener("click", () => {
                window.print();
            });
        </script>
    @endif
@endsection