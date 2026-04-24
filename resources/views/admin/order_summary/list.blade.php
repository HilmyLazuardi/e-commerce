@extends('_template_adm.master')

@php
    $module             = ucwords(lang('Rekap Order Pesanan terlaris', $translations));
    $pagetitle          = $module;
    $function_get_data  = 'refresh_data();';
@endphp

@section('title', $pagetitle)

@section('content')
    <div class="">
        {{-- display response message --}}
        @include('_template_adm.message')

        <div class="page-title">
            <div class="title_left">
                <h3>{{ $pagetitle }}</h3>
            </div>
        </div>
        
        <div class="clearfix"></div>

        {{-- FILTER --}}
        <div class="row">

            {{-- filter by: daterange --}}
            <div class="col-md-3 col-sm-12 col-xs-12">
                <div class="control-group">
                    <div class="controls">
                        <div class="input-prepend input-group">
                            <span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" style="width: 200px" name="reservation" id="reportrange_right" class="form-control" value="" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- EXPORT --}}
            <div class="col-md-8 col-sm-12 col-xs-12">
                <div class="control-group pull-right">
                    <div class="controls">
                        <a href="javascript:void(0)" class="btn btn-round btn-primary" target="_blank" style="margin-right: 10px;">
                             <i class="fa fa-eye"></i> Preview PDF
                         </a>
                    </div>
                </div>
            </div>
            {{-- <div class="col-md-4 col-sm-12 col-xs-12">
                <div class="control-group pull-right">
                    <div class="controls">
                        <a href="{{ route('admin.order_summary.print') }}?daterange={{ request('reportrange_right') }}" class="btn btn-round btn-danger" target="_blank" style="float: right; margin-right: 10px;">
                            <i class="fa fa-file-pdf-o"></i>&nbsp; Export PDF
                        </a>
                    </div>
                </div>
            </div> --}}
            <div class="col-md-1 col-sm-12 col-xs-12">
                <div class="control-group pull-right">
                    <div class="controls">
                        <a href="javascript:void(0)" class="btn btn-round btn-success" onclick="confirm_export()" style="float: right;">
                            <i class="fa fa-download"></i>&nbsp; {{ ucwords(lang('export', $translations)) }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{ ucwords(lang('data list', $translations)) }}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="table-responsive">
                            <table id="datatables" class="table table-striped table-bordered" style="display:none">
                                <thead>
                                    <tr>
                                        <th>{{ ucwords(lang('nama produk', $translations)) }}</th>
                                        <th>{{ ucwords(lang('nama penjual', $translations)) }}</th>
                                        <th>{{ ucwords(lang('harga', $translations)) }}</th>
                                        <th>{{ ucwords(lang('stok', $translations)) }}</th>
                                        <th>{{ ucwords(lang('total order', $translations)) }}</th>
                                        <th>{{ ucwords(lang('unit terjual', $translations)) }}</th>
                                        <th>{{ ucwords(lang('omzet', $translations)) }}</th>
                                        <th>{{ ucwords(lang('% terjual', $translations)) }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <!-- DataTables -->
    @include('_vendors.datatables.css')
    <!-- Select2 -->
    @include('_vendors.select2.css')
    <!-- bootstrap-daterangepicker -->
    @include('_vendors.daterangepicker.css')
@endsection

@section('script')
    <!-- DataTables -->
    @include('_vendors.datatables.script')
    <!-- Select2 -->
    @include('_vendors.select2.script')
    <!-- bootstrap-daterangepicker -->
    @include('_vendors.daterangepicker.script')

    <script>
        $(document).ready(function() {
            {{ $function_get_data }}

            $('#reportrange_right').on('change', function() {
                {{ $function_get_data }}
                $(this).blur();
            });

            updateExportPDF();

            $('#reportrange_right').on('apply.daterangepicker change', function () {
                updateExportPDF();
            });
        });

        function refresh_data() {
            var daterange = $('#reportrange_right').val();
            if (typeof daterange == 'undefined') {
                daterange = '';
            }

            $('#datatables').show();
            $('#datatables').dataTable().fnDestroy();
            var table = $('#datatables').DataTable({
                orderCellsTop: true,
                fixedHeader: false,
                serverSide: false,
                processing: true,
                ajax: "{{ route('admin.order_summary.get_data') }}?&daterange="+daterange,
                order: [[ 0, 'desc' ]],
                columns: [
                    { data: 'name', name: 'product_item.name' },
                    { data: 'seller_name', name: 'seller.store_name' },
                    { data: 'price', name: 'default_variant.price' },
                    { data: 'stock_info', name: 'stock_info', orderable: false, searchable: false },
                    { data: 'total_order', name: 'total_order' },
                    { data: 'unit_terjual', name: 'unit_terjual' },
                    { data: 'omzet', name: 'omzet' },
                    { data: 'percentage', name: 'percentage' },
                ]
            });
        }

        function confirm_export() {
            var daterange = $('#reportrange_right').val();
            if (typeof daterange == 'undefined') {
                daterange = '';
            }
            if (confirm("Apakah Anda yakin untuk mengekspor data ini?")) {
                window.location.href = "{{ route('admin.order_summary.export') }}?&daterange="+daterange;
            }
        }

        function updateExportPDF() {
            var daterange = $('#reportrange_right').val();
            if (typeof daterange == 'undefined') {
                daterange = '';
            }
            var encoded = encodeURIComponent(daterange);

            var url = "{{ route('admin.order_summary.preview_pdf') }}?daterange=" + encoded;
            $('a.btn-primary[target="_blank"]').attr('href', url);
        }
    </script>
@endsection