@extends('_template_web.master')

@section('title', 'Profile')

@section('css-plugins')
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/jquery-ui.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/jquery-ui.theme.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/select2.css') }}">
@endsection

@section('script-plugins')
    <script type="text/javascript" src="{{ asset('web/js/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('web/js/select2.full.min.js') }}"></script>
@endsection

@section('content')
    @include('_template_web.header_with_categories')
    @include('_template_web.alert_popup')
    
    <section class="white_bg no_categories">
        <div class="section_profile">
            <div class="profile_menu">
                <img class="logo" src="{{ asset('web/images/logo_app_blue.png') }}">
                {{-- <div class="container">
                    <ul>
                        <li><a href="{{ route('web.buyer.profile') }}" class="active">Profil</a></li>
                        <li><a href="{{ route('web.buyer.list_address') }}">Daftar Alamat</a></li>
                        <li><a href="{{ route('web.order.history') }}">Riwayat Pesanan</a></li>
                    </ul>
                </div> --}}
            </div>

            <div class="profile_box">
                <div class="container">
                    <h3>Refund Pesanan</h3>
                    <form action="{{ route('web.order.refund_store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form_wrapper form_bg">
                            <div class="form_box">
                                <span class="title">ID Transaksi</span>
                                <select class="select2" data-placeholder="Pilih ID Transaksi Kamu" name="transaction_id" id="transaction_id" onchange="filter_sku_variant();">
                                    <option value="">Pilih ID Transaksi Kamu</option>
                                    @if (isset($order_transaction_id[0]))
                                        @foreach ($order_transaction_id as $order_id)
                                            <option value="{{ $order_id->transaction_id }}">
                                                {{ $order_id->transaction_id }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class="error_msg" id="transaction_id-error" style="display: none">ID Transaksi wajib diisi</span>
                                @if (Session::has('error_order_transaction'))
                                    <span class="error_msg">{{ Session::get('error_order_transaction') }}.</span>
                                @endif

                                @if (count($errors) > 0)
                                    @foreach ($errors->messages() as $key => $error)
                                        @if ($key == 'transaction_id')
                                            <span class="error_msg">{{ $error[0] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>

                            <div class="form_box">
                                <span class="title">SKU Variant</span>
                                <select class="select2" data-placeholder="Pilih SKU Variant Kamu" name="sku_variant" id="sku_variant" disabled>
                                </select>
                                <span class="error_msg" id="sku_variant-error" style="display: none">SKU Variant wajib diisi</span>
                                @if (Session::has('error_sku_variant'))
                                    <span class="error_msg">{{ Session::get('error_sku_variant') }}.</span>
                                @endif

                                @if (count($errors) > 0)
                                    @foreach ($errors->messages() as $key => $error)
                                        @if ($key == 'sku_variant')
                                            <span class="error_msg">{{ $error[0] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>

                            <div class="form_box">
                                <span class="title">Foto Bukti Refund</span>
                                <input type="file" name="refund_photo" id="refund_photo" accept="image/*" required>
                                <span class="notes">*Maks. 2 MB</span>
                                @if (Session::has('error_refund_photo'))
                                    <span class="error_msg">{{ Session::get('error_refund_photo') }}</span>
                                @endif

                                @if (count($errors) > 0)
                                    @foreach ($errors->messages() as $key => $error)
                                        @if ($key == 'refund_photo')
                                            <span class="error_msg">{{ $error[0] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>

                            <div class="form_box">
                                <span class="title">Keterangan Refund</span>
                                <textarea placeholder="Masukkan alasan refund" name="refund_notes">{{ old('refund_notes') }}</textarea>
                                <span class="error_msg" id="refund_note-error" style="display: none">Keterangan Refund wajib diisi</span>
                                @if (Session::has('error_refund_notes'))
                                    <span class="error_msg">{{ Session::get('error_refund_notes') }}.</span>
                                @endif

                                @if (count($errors) > 0)
                                    @foreach ($errors->messages() as $key => $error)
                                        @if ($key == 'refund_notes')
                                            <span class="error_msg">{{ $error[0] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>

                            <div class="button_wrapper">
                                <button class="red_btn" type="submit" id="submit_btn">Simpan</button>
                                <a href="{{ route('web.order.refund') }}" class="green_btn">Kembali</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('footer-script')
    <script>
        @if (session('error_profile'))
            show_popup_failed();
        @endif

        @if (session('success'))
            show_popup_success();
        @endif

        function filter_sku_variant() {
            var parent_value = $('#transaction_id').val();
            var select2_elm = $('#sku_variant');
            
            // Stop function if parent_value is empty/null
            if (!parent_value) {
                // Kosongkan dan disable elemen jika sebelumnya sudah ada data
                select2_elm.empty().prop('disabled', true);
                var newOption = new Option("- {{ ucwords(lang('please choose one', $translations)) }} -", "", false, true);
                select2_elm.append(newOption);
                return; // Jangan lanjut ke AJAX
            }

            $.ajax({
                type: "POST",
                url: "{{ route('helper.filter_sku_variant') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    parent: parent_value
                },
                beforeSend: function () {
                    // do something before send the data
                    // set default option - just will be selected in "view"
                    var newOption = new Option("- {{ ucwords(lang('please wait', $translations)) }} -", "", false, true);
                    select2_elm.append(newOption);
                },
            })
            .done(function (response) {
                // Callback handler that will be called on success
                if (typeof response != 'undefined') {
                    if (response.status == 'true') {
                        // SUCCESS RESPONSE

                        // remove all existing options in select2 element
                        select2_elm.empty();

                        if (response.data != null) {
                            // set default option - just will be selected in "view"
                            var newOption = new Option("- {{ ucwords('pilih salah satu') }} -", "", false, true);
                            select2_elm.append(newOption);
                            
                            // looping the response data to set new options
                            $.each(response.data, function(key, value) {
                                new_data = {
                                    id: value.id,
                                    text: value.sku_id
                                };
                                newOption = new Option(new_data.text, new_data.id, false, false);
                                select2_elm.append(newOption);
                            });

                            // reset selected value of select2 element
                            // select2_elm.val(null);

                            // Notify any JS components that the value changed
                            // select2_elm.trigger('change');

                            // enable the select2 element
                            select2_elm.prop('disabled', false);
                        } else {
                            // set default option - just will be selected in "view"
                            var newOption = new Option("*{{ strtoupper('tidak ada data') }}", "", false, true);
                            select2_elm.append(newOption);
                            // disable the select2 element
                            select2_elm.prop('disabled', true);
                        }
                    } else {
                        // FAILED RESPONSE
                        alert('ERROR: ' + response.message);
                    }
                } else {
                    alert('Server not respond, please try again.');
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                // Callback handler that will be called on failure

                // Log the error to the console
                console.error("The following error occurred: " + textStatus, errorThrown);

                alert("The following error occurred: " + textStatus + "\n" + errorThrown);
                // location.reload();
            })
            .always(function () {
                // Callback handler that will be called regardless
                // if the request failed or succeeded
            });
        }
    </script>
@endsection