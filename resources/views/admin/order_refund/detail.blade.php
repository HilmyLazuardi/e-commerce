@extends('_template_adm.master')

@php
    use App\Libraries\Helper;

    $pagetitle = ucwords(lang('refund pesanan', $translations));
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

        {{-- <div class="row">
            <div class="col-md-8 col-sm-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2><i class="fa fa-undo"></i> Detail Refund</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">No. Transaksi</th>
                                <td><strong>{{ $data->transaction_id }}</strong></td>
                            </tr>
                            <tr>
                                <th>Nama Pembeli</th>
                                <td>{{ $data->buyer_fullname }}<br>
                                    <i class="fa fa-phone"></i> {{ $data->buyer_phone_number }}<br>
                                    <i class="fa fa-envelope"></i> {{ $data->buyer_email }}
                                </td>
                            </tr>
                            <tr>
                                <th>Produk</th>
                                <td>
                                    @foreach($order_details as $item)
                                        <div class="media" style="margin-bottom: 15px;">
                                            <img src="{{ asset($item->product_image) }}"
                                                class="mr-3"
                                                alt="Product Image"
                                                width="60" style="border-radius: 6px;">
                                            <div class="media-body">
                                                <strong>{{ $item->product_name }}</strong><br>
                                                SKU: <span class="badge badge-default">{{ $item->variant_sku }}</span>
                                                @if($item->variant_name)
                                                    <span class="text-muted">({{ $item->variant_name }})</span>
                                                @endif
                                                <br>Qty: {{ $item->qty }}
                                            </div>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <th>Alasan Refund</th>
                                <td>
                                    <div class="alert alert-warning" style="margin: 0;">
                                        <i class="fa fa-commenting"></i>
                                        {{ $data->refund_notes ?: 'Tidak ada alasan yang diberikan.' }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Waktu Refund</th>
                                <td><i class="fa fa-calendar"></i> {{ \App\Libraries\Helper::locale_timestamp($data->updated_at, 'd M Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2><i class="fa fa-photo"></i> Bukti Refund</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content text-center">
                        @if($data->refund_photo)
                            <img src="{{ asset($data->refund_photo) }}"
                                alt="Bukti Refund"
                                class="img-thumbnail"
                                style="max-width: 100%; border-radius: 8px;">
                        @else
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Tidak ada foto refund yang diunggah.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div> --}}
        <div class="row">
            <div class="col-md-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2><i class="fa fa-undo"></i> Detail Refund #{{ $data->transaction_id }}</h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li><a href="{{ route('admin.refund_order') }}" class="btn btn-sm btn-default"><i class="fa fa-arrow-left"></i> Kembali</a></li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
        
                    <div class="x_content">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card p-3">
                                    <h5><i class="fa fa-user-circle"></i> Pembeli</h5>
                                    <p><strong>{{ $data->buyer_fullname }}</strong></p>
                                    <p><i class="fa fa-phone"></i> {{ $data->buyer_phone_number }}</p>
                                    <p><i class="fa fa-envelope"></i> {{ $data->buyer_email }}</p>
                                </div>
                            </div>
        
                            {{-- Informasi Refund --}}
                            <div class="col-md-4">
                                <div class="card p-3">
                                    <h5><i class="fa fa-info-circle"></i> Info Refund</h5>
                                    <p><strong>Tanggal Refund:</strong><br>
                                        {{ Helper::locale_timestamp($data->updated_at, 'd M Y H:i') }}
                                    </p>
                                    <p><strong>Alasan:</strong><br>
                                        {{ $data->refund_notes ?: 'Tidak ada alasan diberikan.' }}
                                    </p>
                                </div>
                            </div>
        
                            <div class="col-md-4 text-center">
                                <div class="card p-3">
                                    <h5><i class="fa fa-image"></i> Bukti Refund</h5>
                                    @if($data->refund_photo)
                                        <img src="{{ asset($data->refund_photo) }}"
                                             alt="Bukti Refund" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                    @else
                                        <div class="alert alert-secondary mb-0">Tidak ada foto diunggah.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
        
                        <hr>
        
                        <h5><i class="fa fa-box"></i> Produk dalam Refund</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>SKU</th>
                                        <th>Varian</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order_details as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset($item->product_image) }}"
                                                         alt="" width="50" class="mr-3 rounded">
                                                    <div>{{ $item->product_name }}</div>
                                                </div>
                                            </td>
                                            <td>{{ $item->variant_sku }}</td>
                                            <td>{{ $item->variant_name ?: '-' }}</td>
                                            <td>{{ $item->qty }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
        
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
