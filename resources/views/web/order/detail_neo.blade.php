@extends('_template_web.master')

@section('title', 'Detail Pesanan')

@section('css-plugins')
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/jquery-ui.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/jquery-ui.theme.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/select2.css') }}">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        .sol_box {
            padding-top: 40px;
            position: relative;
        }    
        .timeline-tracking {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
        }
    
        .timeline-tracking::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
    
        .timeline-tracking li {
            position: relative;
            padding-left: 70px;
            margin-bottom: 30px;
        }
    
        .timeline-tracking li .icon {
            position: absolute;
            left: 15px;
            top: 0;
            width: 30px;
            height: 30px;
            background-color: #fff;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    
        .timeline-tracking li.status-pickup .icon {
            background-color: #e3f2fd;
            border-color: #2196f3;
            color: #2196f3;
        }
    
        .timeline-tracking li.status-process .icon {
            background-color: #fff8e1;
            border-color: #ffc107;
            color: #ffc107;
        }
    
        .timeline-tracking li.status-delivered .icon {
            background-color: #e8f5e9;
            border-color: #4caf50;
            color: #4caf50;
        }
    
        .timeline-tracking li .desc {
            font-size: 14px;
            color: #495057;
        }
    
        .timeline-tracking li .date {
            font-weight: 600;
            margin-bottom: 5px;
            color: #343a40;
        }
        .sticky-action-buttons {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 16px 20px;
            background: #ffffff;
            box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            border-top: 1px solid #e9ecef;
        }
        .sticky-action-buttons .btn {
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 8px;
        }

        @media (max-width: 576px) {
            .btn {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .btn svg {
                margin-right: 8px !important;
            }
        }
    </style>    
@endsection

@section('script-plugins')
    <script type="text/javascript" src="{{ asset('web/js/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('web/js/select2.full.min.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadTrackingLog() {
            const shippingNumber = '{{ $data_order['shipping_number'] }}';
            if (!shippingNumber) {
                $('#tracking-log-content').html('<p>Nomor resi tidak tersedia.</p>');
                return;
            }
            const courierCode = "{{ $data_order['shipper_code'] ?? 'ninja' }}"; // default ninja

            $('#tracking-log-content').html('<p>Sedang mengambil data tracking...</p>');

            $.ajax({
                url: "{{ route('web.order.tracking.log') }}",
                type: "GET",
                data: {
                    awb: shippingNumber,
                    courier: courierCode
                },
                success: function (response) {
                    if (response.logs && response.logs.length > 0) {
                        let html = '<ul class="timeline-tracking">';

                        response.logs.forEach(log => {
                            let status = log.status ? log.status.toLowerCase() : '';
                            let statusClass = 'status-process';
                            let icon = `
                                <svg xmlns="http://www.w3.org/2000/svg" class="bi bi-clock" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 1 .5.5v4h3a.5.5 0 0 1 0 1H8a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zM1.5 8a6.5 6.5 0 1 1 13 0A6.5 6.5 0 0 1 1.5 8z"/>
                                </svg>`;

                            if (status.includes('pickup')) {
                                statusClass = 'status-pickup';
                                icon = `
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
                                        <path d="M0 1a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v10H0V1zm11 0v10h1v1h1a2 2 0 0 0 2-2v-3.5L13 3h-2V1zM2 2h2v2H2V2zm0 3h2v2H2V5zm0 3h2v2H2V8z"/>
                                    </svg>`;
                            } else if (status.includes('delivered')) {
                                statusClass = 'status-delivered';
                                icon = `
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14z"/>
                                    <path d="M10.97 5.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02l-2.39-2.5a.75.75 0 0 1 1.1-1.02l1.8 1.88 3.49-4.42z"/>
                                    </svg>`;
                            }

                            html += `
                                <li class="${statusClass}">
                                    <div class="icon">${icon}</div>
                                    <div class="date">${log.date}</div>
                                    <div class="desc">${log.description}</div>
                                </li>`;
                        });

                        html += '</ul>';
                        $('#tracking-log-content').html(html);
                    } else {
                        $('#tracking-log-content').html('<p>Data tracking tidak tersedia.</p>');
                    }
                },
                error: function () {
                    $('#tracking-log-content').html('<p>Gagal memuat data tracking. Silakan coba lagi nanti.</p>');
                }
            });
        }
    </script>

    {{-- Refund Order --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const triggerButton = document.getElementById('triggerRefundConfirmation');
            const submitButton = document.getElementById('submitRefundForm');
            const refundForm = document.querySelector('#confirmRefundModal form');
    
            if (triggerButton && submitButton && refundForm) {
                triggerButton.addEventListener('click', function () {
                    // validasi input minimal (opsional)
                    const fileInput = refundForm.querySelector('input[name="refund_photo"]');
                    const noteInput = refundForm.querySelector('textarea[name="refund_note"]');
    
                    if (!fileInput.files.length || !noteInput.value.trim()) {
                        alert('Mohon lengkapi foto bukti dan keterangan refund.');
                        return;
                    }
    
                    $('#confirmSubmitRefund').modal('show');
                });
    
                submitButton.addEventListener('click', function () {
                    refundForm.submit();
                });
            }
        });
    </script>    
@endsection

@section('content')
    @include('_template_web.header_with_categories')
    @include('_template_web.alert_popup')

    <section class="white_bg no_categories">
        <div class="section_order_list">
            <div class="container">
                <div class="sol_box">
                    <span class="flags {{ $data_order['status_label'] }}">{{ $data_order['status'] }}</span>
                    <div class="row_clear custom_one">
                        <span class="label">No. Tagihan</span>
                        {{ $data_order['transaction_id'] }}
                    </div>
                    
                    <div class="row_clear custom_one">
                        <span class="label">Alamat Pengiriman</span>
                        @if (!empty($data_order['receiver_name']))
                            {{ $data_order['receiver_name'] }}<br>
                        @endif
                        @if (!empty($data_order['receiver_phone']))
                            {{ $data_order['receiver_phone'] }}<br>
                        @endif
                        {{ $data_order['buyer_address'] }}
                    </div>
                </div>

                <div class="row_clear">
                    <div class="co_wrapper">
                        <span class="title">{{ $data_order['seller_name'] }}</span>
                        @foreach ($data as $item)
                            <div class="co_box">
                                <div class="row_clear">
                                    @php
                                        $product_image = $item->product_image;
                                        if (!empty($item->variant_image)) {
                                            $product_image = $item->variant_image;
                                        }
                                    @endphp
                                    <div class="co_img"><a href="#"><img src="{{ asset($product_image) }}"></a></div>
                                    <div class="co_desc">
                                        <h4><a href="#">{{ $item->product_name . ' - ' . $item->variant_name }}</a></h4>
                                        @php
                                            $subtotal_per_item = $item->price * $item->qty;
                                        @endphp
                                        <span class="co_price">Rp{{ number_format($subtotal_per_item, 0, ',', '.') }}</span>
                                        <span class="co_pcs">{{ $item->qty }} pcs</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="co_subtotal">
                            <div class="row_clear">
                                <div class="text_left">Subtotal</div>
                                <div class="text_right">Rp{{ number_format($data_order['price_subtotal'], 0, ',', '.') }}</div>
                            </div>

                            <div class="row_clear">
                                <div class="text_left">
                                    <span>Asuransi Pengiriman</span>
                                </div>
                                @php
                                    $asuransi = '-';
                                    if ($data_order['price_insurance'] > 0) {
                                        $asuransi = 'Rp' . number_format($data_order['price_insurance'], 0, ',', '.');
                                    }
                                @endphp
                                <div class="text_right">{{ $asuransi }}</div>
                            </div>

                            <div class="row_clear">
                                <div class="text_left">Kurir</div>
                                <div class="text_right bold">{{ $data_order['shipper_service_type'] }}</div>
                            </div>

                            <div class="row_clear">
                                <div class="text_left">Ongkos Kirim</div>
                                <div class="text_right">Rp{{ number_format($data_order['price_shipping'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row_clear custom_one">
                        <div class="detail_payment">
                            <h3>Detail Pembayaran ({{ count($data_invoice) }} Invoice)</h3>
                            <ol>
                                @foreach ($data_invoice as $key => $item)
                                    <li>
                                        {{ $key }}
                                        <div class="row_clear">
                                            <div class="text_left">{{ $item['transaction_id'] }}</div>
                                            <div class="text_right">Rp{{ number_format($item['price_total'], 0, ',', '.') }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
                
                @if (!empty($data_order['shipping_number']))
                    <div class="sol_box position-relative">
                        <div class="row_clear custom_one">
                            <span class="label">Nomor Resi</span>
                            <div>
                                <strong>{{ $data_order['shipping_number'] }}</strong><br>
                                <small>Kurir: {{ $data_order['shipper_service_type'] }}</small>
                            </div>
                        </div>
                    
                        <button type="button"
                            class="btn btn-sm btn-outline-success d-inline-flex align-items-center"
                            style="position: absolute; top: 10px; right: 10px; z-index: 10;"
                            data-toggle="modal"
                            data-target="#trackingLogModal"
                            onclick="loadTrackingLog()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                                class="bi bi-truck mr-1" viewBox="0 0 16 16">
                                <path d="M0 1a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v10H0V1zm11 0v10h1v1h1a2 2 0 0 0 2-2v-3.5L13 3h-2V1zM2 2h2v2H2V2zm0 3h2v2H2V5zm0 3h2v2H2V8z"/>
                            </svg>
                            Lihat Tracking Log
                        </button>
                    </div>
                @endif

                <div class="order_summary">
                    <h3>Rincian Pesanan</h3>
                    <div class="row_clear">
                        <div class="text_left">Subtotal</div>
                        <div class="text_right">Rp{{ number_format($data_invoice_price['price_subtotal'], 0, ',', '.') }}</div>
                    </div>

                    <div class="row_clear">
                        <div class="text_left">
                            <span>Asuransi Pengiriman</span>
                        </div>
                        @php
                            $invoice_insurance_fee = '-';
                            if (!empty($data_invoice_price['price_insurance'])) {
                                $invoice_insurance_fee = 'Rp' . number_format($data_invoice_price['price_insurance'], 0, ',', '.');
                            }
                        @endphp
                        <div class="text_right">{{ $invoice_insurance_fee }}</div>
                    </div>

                    <div class="row_clear">
                        <div class="text_left">Total Ongkos Kirim</div>
                        <div class="text_right bold">Rp{{ number_format($data_invoice_price['price_shipping'], 0, ',', '.') }}</div>
                    </div>

                    @if (!empty($data_voucher))
                        <div class="row_clear">
                            <div class="row_clear">Kode Voucher</div>
                            <div class="text_left bold">{{ $data_voucher['voucher_code'] }}</div>
                            <div class="text_right">-Rp{{ number_format($data_voucher['discount_amount'], 0, ',', '.') }}</div>
                        </div>
                    @endif

                    <div class="row_clear">
                        <div class="text_left bold">Harga Total</div>
                        <div class="text_right bold">Rp{{ number_format($data_invoice_price['price_total'], 0, ',', '.') }}</div>
                    </div>
                </div>
                
                @if (in_array($data_order['progress_status'], [2, 3]) && !empty($data_order['shipping_number']))
                    <div class="d-none d-sm-flex justify-content-end mt-4 gap-2">
                        <button class="btn btn-success mr-2" data-toggle="modal" data-target="#confirmFinishModal">
                            <i class="bi bi-check-circle mr-1"></i> Finish Order
                        </button>
                        {{-- <button class="btn btn-danger" data-toggle="modal" data-target="#confirmRefundModal">
                            <i class="bi bi-arrow-counterclockwise mr-1"></i> Refund
                        </button> --}}
                        <a href="{{ route('web.order.refund') }}" class="btn btn-danger">
                            <i class="bi bi-arrow-counterclockwise mr-1"></i> Refund Order
                        </a>
                    </div>
                
                    <!-- Sticky Action Buttons (Refund & Finish) -->
                    <div class="sticky-action-buttons d-block d-sm-none">
                        <button
                            type="button"
                            class="btn btn-success btn-block mb-2"
                            data-toggle="modal"
                            data-target="#confirmFinishModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle mr-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14z"/>
                                <path d="M10.97 5.97a.75.75 0 0 1 1.07 1.05L8.03 11.03a.75.75 0 0 1-1.08.02l-2.39-2.5a.75.75 0 0 1 1.1-1.02l1.8 1.88 3.49-4.42z"/>
                            </svg>
                            Selesaikan Pesanan
                        </button>

                        {{-- <button
                            type="button"
                            class="btn btn-danger btn-block"
                            data-toggle="modal"
                            data-target="#confirmRefundModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-counterclockwise mr-2" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 1 .908-.418A4 4 0 1 0 8 4V1.5a.5.5 0 0 1 1 0V4a.5.5 0 0 1-.5.5H6.707l.147.146a.5.5 0 0 1-.708.708l-1-1a.5.5 0 0 1 0-.708l1-1a.5.5 0 0 1 .708.708L6.707 3.5H8z"/>
                            </svg>
                            Refund Pesanan
                        </button> --}}
                        <a href="{{ route('web.order.refund') }}" class="red_btn"> Refund Pesanan</a>
                    </div>             
                @endif

                
                <div class="button_wrapper">
                    <a href="{{ route('web.home') }}" class="green_btn">Belanja Lagi</a>
                </div>
            </div>
        </div>
    </section>

    @if (!empty($data_order['shipping_number']))
        <!-- Modal -->
        <div class="modal fade" id="trackingLogModal" tabindex="-1" role="dialog" aria-labelledby="trackingLogModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="trackingLogModalLabel">
                            Tracking Log - Resi: {{ $data_order['shipping_number'] }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="tracking-log-content">
                        <p>Sedang mengambil data tracking...</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (in_array($data_order['progress_status'], [2, 3]) && !empty($data_order['shipping_number']) )
        <!-- Modal Upload Refund -->
        <div class="modal fade" id="confirmRefundModal" tabindex="-1" role="dialog" aria-labelledby="confirmRefundModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="refundForm" method="POST" action="{{ route('web.order.refund') }}" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    <input type="hidden" name="order_transaction_id" value="{{ $data_order['transaction_id'] }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmRefundModalLabel">Ajukan Refund</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p>Silakan unggah bukti dan berikan alasan refund pesanan.</p>

                        <div class="form-group">
                            <label for="refund_photo">Foto Bukti Refund</label>
                            <input type="file" name="refund_photo" id="refund_photo" class="form-control-file" accept="image/*" required>
                        </div>

                        <div class="form-group">
                            <label for="refund_note">Keterangan Refund</label>
                            <textarea name="refund_note" id="refund_note" class="form-control" rows="3" placeholder="Masukkan alasan refund..." required></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger" id="triggerRefundConfirmation">
                            <i class="bi bi-arrow-counterclockwise mr-1"></i> Konfirmasi Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- Modal Konfirmasi Submit Refund -->
        <div class="modal fade" id="confirmSubmitRefund" tabindex="-1" role="dialog" aria-labelledby="confirmSubmitRefundLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="confirmSubmitRefundLabel">Konfirmasi Refund</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin mengirim permintaan refund ini?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-warning" id="submitRefundForm">Ya, Kirim Refund</button>
                    </div>
                </div>
            </div>
        </div>  

        <!-- Modal Konfirmasi Finish -->
        <div class="modal fade" id="confirmFinishModal" tabindex="-1" role="dialog" aria-labelledby="confirmFinishModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
            <form action="{{ route('web.order.update_status') }}" method="POST">
                @csrf
                <input type="hidden" name="order_transaction" value="{{ $data_order['transaction_id'] }}">
                <input type="hidden" name="status" value="finished">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Konfirmasi Selesaikan Pesanan</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menandai pesanan ini sebagai <strong>selesai</strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Ya, Selesaikan</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    @endif
@endsection