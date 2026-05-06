@extends('_template_web.master')

@section('css-plugins')
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/select2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/slick.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/slick-theme.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/jquery.fancybox.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/content_element.css') }}">
@endsection

@section('script-plugins')
    <script type="text/javascript" src="{{ asset('web/js/select2.full.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('web/js/slick.js') }}"></script>
    <script type="text/javascript" src="{{ asset('web/js/jquery.fancybox.min.js') }}"></script>
@endsection

@section('content')
    @include('_template_web.header')

    <section class="white_bg no_categories">
        <div class="section_profile">
            <div class="profile_box">
                <form action="{{ route('web.order.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $confirm->variant_id }}">
                    <input type="hidden" name="address" value="{{ $confirm->address_id }}">
                    <input type="hidden" name="shipper" value="{{ $confirm->shipper_id }}">
                    <input type="hidden" name="delivery_method" value="{{ $confirm->delivery_method }}">
                    <input type="hidden" name="price_shipping" value="{{ $confirm->price_shipping }}">
                    <input type="hidden" name="insurance_shipping_fee" value="{{ $confirm->insurance_shipping_fee }}">
                    <input type="hidden" name="total_price" value="{{ $confirm->total_price }}">
                    <input type="hidden" name="shipper_service_type" value="{{ $confirm->shipper_service_type }}">
                    <input type="hidden" name="origin_code" value="{{ $confirm->origin_code }}">
                    <input type="hidden" name="destination_code" value="{{ $confirm->destination_code }}">
                    <input type="hidden" name="estimate" value="{{ $confirm->estimate }}">

                    <div class="container">
                        <img class="logo" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAAABmJLR0QA/wD/AP+gvaeTAAAgAElEQVR4nOy9e7Ak2V3f+TmZWY9bVffefvd0T/c8NDPSWEKMQBIPScho1yaMFqwlgDE2BoMw9obtxTYOwhsOMJglAofXdrBevBEswbL2InulWAdrMF6M10gCBGIkJJA00rQ0mlere/ox/bjvemWe/SMr69YjHyezsqoyq37fiDtzK3/nmyf7ZlZ+Tp4853cUokJLa62uHHLBcXlUa/dRPHVWKU4DpzX6LHAa1GnQTUABJwbWKtDUYzsL2X9c3Qkb47zhcW3sj923zlK3uV/HfBpuidmB0bFFFEr17wo9n/F7mIrOdD5z8APopKOe7/WQ/nyGXCEZr4dZvp/p/OF7Mrse1A5oD9gHeqCOQO8CtzTqVY1301Lc0prryuOaQ+eLf/axUzsJhy4qgNSyD0Dk67nbetOp8WY8781K8RSoxzT6EeBhoBb2Tc3v5mAYE/gL/PP0g8A/pmAx4G9Qd7j/VTTPgfoi6Cso63NddfSJ73j05P2EXYsWKGkALEHP3tKterX/9SjrnQr1taDfrOFRRs5H/Jdb4D8Pv8Df0JuHHwT+MQVLDv846xWN/kM0z2ibj7debHzmPe9R/YQqRXOSNAAWoBf29Xnluu/RqHdoeCfwFGBHlRf4hwcF/uGFBf7p/QL/8A2Lvx70rtLqtzX6tyzP/q33PlH/csIuRDlKGgBz0Ie1dh7a4xsU3rdqzZ9T8DUM/tazfbkF/vPwC/wNvXn4QeAfU3C94H8cGLkivgz8lrL0v2++1Pwv0jswX0kDICfduKGbR03327S2vhOt/yzHg/GGEvinqDt8N7n7Bf6G3jz8IPCPKSjwn5DiDh6/6ln6Q51Hm7/9tFJuQjWilJIGwAy6qvWGu+++V3vqaSy+TWsa87nZC/zn4Rf4G3rz8IPAP6agwD/Re1vDv1Pwv//5x5qfSKhSZChpAGTQC7v6myy8Hwa+Q0ML5nmzF/jPwy/wN/Tm4QeBf0xBgX86r4Y/Vh6/2LY6H3haphvOJGkAGOrKrj5Tw/t+DT8MPDkaE/iPbBH4C/ynAgL/qIIC/5TeoR+AQ6X5oLb4X9/3WPOTCYcjCpE0ABL04o7+BvD+NorvAGqTcYH/yBaBv8B/KiDwjyoo8E/pHfrDpD6s0P/Ttz/W+E2lVNLhiQaSBkCItNbWK3vuf6O19SNa6T8TWW74n4hYXB2xhQX+8/AL/A29efhB4B9TUOCf0jv0J9b9WQ0/X9ONf/XeJ1QnofjaSxoAI/IH9Xk/iObvang8rqzAf2SLwF/gPxUQ+EcVFPin9A79qep+RcHPbF1t/LJMJYyWNACAZ7Wutva9H9CanwQuLu9mL/Cfh1/gb+jNww8C/5iCAv+U3qE/Y93wkoaf7T3W+CWZRjittW4AfFLrytk99we0Uj+O5iFY5s1e4D8Pv8Df0JuHHwT+MQUF/im9Q3/Gujk+n1rzGVvxE+97ovlrCbtbK61lA0BrrV7adb9HKfUzwOuG25N8MYUE/inqDt9N7n6Bv6E3Dz8I/GMKCvxTeof+jHWT+Xw+4+G9/7ue2Hw2oeqV00o3AF7Y108pz/1FhXp7WFzgb+oX+Cf5Bf4pvYOgwD/JL/A39Wc/nxqgp+Gfu93mP3z6TaqbcBgro5VsAFzVeqO/7/19pfkHQCWsjMDf1C/wT/IL/FN6B0GBf5Jf4G/qnxH+Q79WfFZ56oe+8w3rseDQ" alt="Larizzka Jaya">
                        <h3>Pengiriman</h3>
                        <div class="address_box custom_one">
                            <span>Label Alamat</span>
                            {{ $pengiriman->name }}
                        </div>
                        <div class="address_box custom_one">
                            <span>Penerima</span>
                            {{ $pengiriman->fullname }} | {{ $pengiriman->phone_number }}
                        </div>
                        <div class="address_box custom_one">
                            <span>Alamat Pengiriman</span>
                            {{ $pengiriman->address_details }}<br>
                            {{ $pengiriman->village_name . ', ' . $pengiriman->sub_district_name . ', ' . $pengiriman->city_name }}<br>
                            {{ $pengiriman->province_name }}
                            @if (isset($pengiriman->postal_code))
                            - {{ $pengiriman->postal_code }}
                            @endif
                            @if ($pengiriman->remarks)
                                <br> {{ $pengiriman->remarks }}
                            @endif
                        </div>
                        <div class="address_box custom_one">
                            <span>Kurir</span>
                            {{ $confirm->shipper_name }}
                        </div>
                        <div class="address_box custom_one">
                            <span>Pengiriman</span>
                            {{ $confirm->shipper_service_type }}
                        </div>
                        <div class="preview_product">
                            <div class="row_clear">
                                <table>
                                    <tr>
                                        <td>Produk</td>
                                        <td>
                                            {{ $confirm->product_name }}
                                            <span>Jumlah : {{ $confirm->qty }} Pcs</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Subtotal</td>
                                        <td>Rp{{ number_format($confirm->sub_total_price, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Ongkos Kirim</td>
                                        <td>Rp{{ number_format($confirm->price_shipping, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($confirm->insurance_shipping_fee != 0)
                                        <tr>
                                            <td>Asuransi</td>
                                            <td>Rp{{ number_format($confirm->insurance_shipping_fee, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Harga Total</strong></td>
                                        <td><strong>Rp{{ number_format($confirm->total_price, 0, ',', '.') }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="button_wrapper">
                            <button type="submit" class="red_btn">Bayar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection