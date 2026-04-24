@extends('_template_adm.master')

@php
    $module             = ucwords(lang('Product Stock Recap', $translations));
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
            <div class="col-md-8 col-sm-12 col-xs-12 title_right">
                <div class="control-group pull-right">
                    <div class="controls">
                        <a href="javascript:void(0)" class="btn btn-round btn-primary" target="_blank" style="margin-right: 10px;">
                             <i class="fa fa-eye"></i> Preview PDF
                         </a>
                    </div>
                </div>
            </div>
            <div class="col-md-1 col-sm-12 col-xs-12 title_right">
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
                                        <th>ID</th>
                                        <th>{{ ucwords(lang('status', $translations)) }}</th>
                                        <th>{{ ucwords(lang('nama produk', $translations)) }}</th>
                                        <th>{{ ucwords(lang('kategori', $translations)) }}</th>
                                        <th>{{ ucwords(lang('toko', $translations)) }}</th>
                                        <th>{{ ucwords(lang('global stock', $translations)) }}</th>
                                        <th>{{ ucwords(lang('global qty', $translations)) }}</th>
                                        <th>{{ ucwords(lang('global booked', $translations)) }}</th>
                                        <th>{{ ucwords(lang('global sold', $translations)) }}</th>
                                        <th>{{ ucwords(lang('variant qty', $translations)) }}</th>
                                        <th>{{ ucwords(lang('variant booked', $translations)) }}</th>
                                        <th>{{ ucwords(lang('variant sold', $translations)) }}</th>
                                        <th>{{ ucwords(lang('total qty', $translations)) }}</th>
                                        <th>{{ ucwords(lang('total booked', $translations)) }}</th>
                                        <th>{{ ucwords(lang('total sold', $translations)) }}</th>
                                        <th>{{ ucwords(lang('SKU variant', $translations)) }}</th>
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
                ajax: "{{ route('admin.stock_recap.get_data') }}?&daterange="+daterange,
                order: [[ 0, 'desc' ]],
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'status', name: 'status' },
                    { data: 'name', name: 'product_item.name' },
                    { data: 'product_category_name', name: 'product_category.name' },
                    { data: 'seller_name', name: 'seller.store_name' },
                    { data: 'global_stock', name: 'product_item.global_stock' },
                    { data: 'global_qty', name: 'product_item.qty' },
                    { data: 'global_qty_booked', name: 'product_item.qty_booked' },
                    { data: 'global_qty_sold', name: 'product_item.qty_sold' },
                    { data: 'variant_qty_total', name: 'variant_qty_total' },
                    { data: 'variant_qty_booked_total', name: 'variant_qty_booked_total' },
                    { data: 'variant_qty_sold_total', name: 'variant_qty_sold_total' },
                    { data: 'total_qty', name: 'total_qty' },
                    { data: 'total_qty_booked', name: 'total_qty_booked' },
                    { data: 'total_qty_sold', name: 'total_qty_sold' },
                    { data: 'sku_variants', name: 'sku_variants', orderable: false, searchable: false },
                ]
            });
        }

        function confirm_export() {
            var daterange = $('#reportrange_right').val();
            if (typeof daterange == 'undefined') {
                daterange = '';
            }
            if (confirm("Apakah Anda yakin untuk mengekspor data ini?")) {
                window.location.href = "{{ route('admin.stock_recap.export') }}?&daterange="+daterange;
            }
        }

        function updateExportPDF() {
            var daterange = $('#reportrange_right').val();
            if (typeof daterange == 'undefined') {
                daterange = '';
            }
            var encoded = encodeURIComponent(daterange);
            var url = "{{ route('admin.stock_recap.preview_pdf') }}?daterange=" + encoded;
            $('a.btn-primary[target="_blank"]').attr('href', url);
        }
    </script>
@endsection