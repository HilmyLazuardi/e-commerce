<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

# Exports
use App\Exports\StockRecapExport;

// Libraries
use App\Libraries\Helper;

// Models
use App\Models\product_item;

class ProductStockRecapController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Stock Recap';
    private $module_id  = 50;

    // SET THIS OBJECT/ITEM NAME
    private $item = 'stock recap';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // AUTHORIZING...
        $authorize = Helper::authorizing($this->module, 'View List');
        if ($authorize['status'] != 'true') {
            return back()->with('error', $authorize['message']);
        }

        return view('admin.stock_recap.list');
    }

    /**
     * Get a listing of the resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data(Datatables $datatables, Request $request)
    {
        $query = product_item::select(
                'product_item.id',
                'product_item.name',
                'product_item.global_stock',
                'product_item.qty as global_qty',
                'product_item.qty_booked as global_qty_booked',
                'product_item.qty_sold as global_qty_sold',
                DB::raw('COALESCE(SUM(product_item_variant.qty),0) as variant_qty_total'),
                DB::raw('COALESCE(SUM(product_item_variant.qty_booked),0) as variant_qty_booked_total'),
                DB::raw('COALESCE(SUM(product_item_variant.qty_sold),0) as variant_qty_sold_total'),
                DB::raw('
                    CASE 
                        WHEN product_item.global_stock = 1 THEN product_item.qty
                        ELSE COALESCE(SUM(product_item_variant.qty),0)
                    END as total_qty
                '),
                DB::raw('
                    CASE 
                        WHEN product_item.global_stock = 1 THEN product_item.qty_booked
                        ELSE COALESCE(SUM(product_item_variant.qty_booked),0)
                    END as total_qty_booked
                '),
                DB::raw('
                    CASE 
                        WHEN product_item.global_stock = 1 THEN product_item.qty_sold
                        ELSE COALESCE(SUM(product_item_variant.qty_sold),0)
                    END as total_qty_sold
                '),
                'product_category.name as product_category_name',
                'seller.store_name as seller_name',
                DB::raw('GROUP_CONCAT(DISTINCT product_item_variant.sku_id SEPARATOR ", ") as sku_variants')
            )
            ->leftJoin('product_item_variant', function($join) {
                $join->on('product_item.id', '=', 'product_item_variant.product_item_id')
                    ->whereNull('product_item_variant.deleted_at');
            })
            ->leftJoin('product_category', 'product_item.category_id', '=', 'product_category.id')
            ->leftJoin('seller', 'product_item.seller_id', '=', 'seller.id')
            ->groupBy(
                'product_item.id',
                'product_item.name',
                'product_item.global_stock',
                'product_item.qty',
                'product_item.qty_booked',
                'product_item.qty_sold',
                'product_category.name',
                'seller.store_name'
            );

        // if (!empty($request->daterange)) {
        //     $daterange = explode(' - ', $request->daterange);
        //     $start = explode('/', $daterange[0]);
        //     $end = explode('/', $daterange[1]);

        //     $start_date = "{$start[2]}-{$start[1]}-{$start[0]} 00:00:00";
        //     $end_date = "{$end[2]}-{$end[1]}-{$end[0]} 23:59:59";

        //     $query->whereBetween('product_item.created_at', [$start, $end]);
        // }
        if (!empty($request->daterange)) {
            // DATERANGE FORMATING
            $daterange = explode(' - ', $request->daterange);
            $start_date_plain = Helper::convert_datepicker($daterange[0]); // GMT+7
            $start_date = Helper::server_timestamp($start_date_plain . ' 00:00:00', 'Y-m-d H:i:s'); // UTC
            $end_date_plain = Helper::convert_datepicker($daterange[1]); // GMT+7
            $end_date = Helper::server_timestamp($end_date_plain . ' 23:59:59', 'Y-m-d H:i:s'); // UTC

            $query->whereRaw("product_item.created_at BETWEEN ? AND ?", [$start_date, $end_date]);
        }


        return $datatables->eloquent($query)
            ->addColumn('status', function($row) {
                if ($row->global_stock == 1) {
                    $total_qty = $row->global_qty;
                } else {
                    $total_qty = $row->variant_qty_total;
                }
        
                if ($total_qty <= 0) {
                    return '<span class="label label-danger">Habis</span>';
                } else {
                    return '<span class="label label-success">Aman</span>';
                }
            })
            ->addColumn('action', function($row) {
                return '-';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function export(Request $request)
    {
        // logging
        $log_detail_id = 11; // export data
        $module_id = $this->module_id;
        $target_id = null;
        $note = null;
        $value_before = null;
        $value_after = null;
        $ip_address = $request->ip();
        Helper::logging($log_detail_id, $module_id, $target_id, $note, $value_before, $value_after, $ip_address);

        // $start_date = '';
        // $end_date = '';
        // if (!empty($request->daterange)) {
        //     // DATERANGE FORMATING
        //     $daterange = explode(' - ', $request->daterange);
        //     $start_date_plain = explode('/', $daterange[0]);
        //     $end_date_plain = explode('/', $daterange[1]);
        //     $start_date = $start_date_plain[2].'-'.$start_date_plain[1].'-'.$start_date_plain[0];
        //     $end_date = $end_date_plain[2].'-'.$end_date_plain[1].'-'.$end_date_plain[0];
        // }

        // SET FILE NAME
        $filename = date('YmdHis') . '_' . $this->module;

        // Format range
        $range = $request->filled('daterange')
            ? collect(explode(' - ', $request->daterange))
                ->map(fn($d) => \Carbon\Carbon::createFromFormat('d/m/Y', $d)->startOfDay())
                ->toArray()
            : null;

        $export = new StockRecapExport($range);
        $exportQuery = clone $export->query();

        // ✅ Cek data
        if ($exportQuery->get()->isEmpty()) {
            return redirect()->back()->with('error', 'Data tidak ditemukan pada periode yang dipilih.');
        }

        $filename = $filename . '.xlsx';

        return Excel::download($export, $filename);
    }

    public function preview_pdf(Request $request)
    {
        $daterange = $request->input('daterange');
        $start_date = null;
        $end_date = null;

        if (!empty($daterange)) {
            $range = explode(' - ', $daterange);
            $start_date = Helper::server_timestamp(Helper::convert_datepicker($range[0]) . ' 00:00:00');
            $end_date = Helper::server_timestamp(Helper::convert_datepicker($range[1]) . ' 23:59:59');
        }

        $query = product_item::select(
                    'product_item.name',
                    'product_category.name as category',
                    'seller.store_name',
                    'product_item.global_stock',
                    DB::raw('
                        CASE 
                            WHEN product_item.global_stock = 1 THEN product_item.qty
                            ELSE COALESCE(SUM(product_item_variant.qty),0)
                        END as total_qty
                    ')
                )
                ->leftJoin('product_item_variant', function($join) {
                    $join->on('product_item.id', '=', 'product_item_variant.product_item_id')
                        ->whereNull('product_item_variant.deleted_at');
                })
                ->leftJoin('product_category', 'product_item.category_id', '=', 'product_category.id')
                ->leftJoin('seller', 'product_item.seller_id', '=', 'seller.id')
                ->groupBy(
                    'product_item.id',
                    'product_item.name',
                    'product_item.global_stock',
                    'product_item.qty',
                    'product_category.name',
                    'seller.store_name'
                );

        if ($start_date && $end_date) {
            $query->whereBetween('product_item.created_at', [$start_date, $end_date]);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan pada periode yang dipilih.');
        }

        $pdf = PDF::loadView('admin.stock_recap.preview_pdf', [
            'data' => $data,
            'daterange' => $daterange,
            'title' => 'Laporan Stok Produk'
        ])->setPaper('A4', 'landscape');

        return $pdf->stream('preview_stock_recap.pdf');
    }
}