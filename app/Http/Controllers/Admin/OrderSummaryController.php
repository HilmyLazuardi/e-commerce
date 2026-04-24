<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

# Exports
use App\Exports\ProductSummaryExport;
use PDF;

// Libraries
use App\Libraries\Helper;

// Models
use App\Models\product_item;

class OrderSummaryController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Order Summary';
    private $module_id  = 49;

    // SET THIS OBJECT/ITEM NAME
    private $item = 'order summary';

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

        return view('admin.order_summary.list');
    }

    /**
     * Get a listing of the resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data(Datatables $datatables, Request $request)
    {
        $start_date = null;
        $end_date = null;
        
        $query = product_item::select(
            'product_item.id',
            'product_item.name',
            'product_item.summary',
            'product_item.image',
            'product_item.global_stock',
            'product_item.qty',
            'product_item.qty_booked',
            'product_item.qty_sold',
            'seller.store_name as seller_name',
            'default_variant.slug',
            'default_variant.price',
            DB::raw('SUM(COALESCE(product_item_variant.qty, 0)) as variant_qty'),
            DB::raw('SUM(COALESCE(product_item_variant.qty_booked, 0)) as variant_qty_booked'),
            DB::raw('SUM(COALESCE(product_item_variant.qty_sold, 0)) as variant_qty_sold'),
            DB::raw('COUNT(DISTINCT order.id) as total_order'),
            DB::raw('SUM(COALESCE(order_details.qty, 0)) as unit_terjual'),
            DB::raw('SUM(COALESCE(order_details.qty * order_details.price_per_item, 0)) as omzet'),
            DB::raw('
                IF(product_item.global_stock = 1,
                    (product_item.qty_sold + product_item.qty_booked) / NULLIF((product_item.qty_sold + product_item.qty_booked + product_item.qty), 0) * 100,
                    (SUM(COALESCE(product_item_variant.qty_sold,0)) + SUM(COALESCE(product_item_variant.qty_booked,0))) / 
                    NULLIF((SUM(COALESCE(product_item_variant.qty_sold,0)) + SUM(COALESCE(product_item_variant.qty_booked,0)) + SUM(COALESCE(product_item_variant.qty,0))), 0) * 100
                ) as percentage
            ')
        )
        ->leftJoin('seller', 'product_item.seller_id', '=', 'seller.id')
        ->leftJoin('product_item_variant as default_variant', function ($join) {
            $join->on('product_item.id', '=', 'default_variant.product_item_id')
                 ->where('default_variant.is_default', 1)
                 ->where('default_variant.status', 1)
                 ->whereNull('default_variant.deleted_at');
        })
        ->leftJoin('product_item_variant', function ($join) {
            $join->on('product_item.id', '=', 'product_item_variant.product_item_id')
                 ->where('product_item_variant.status', 1)
                 ->whereNull('product_item_variant.deleted_at');
        })
        ->leftJoin('order_details', 'order_details.product_id', '=', 'product_item.id')
        ->leftJoin('order', function ($join) {
            $join->on('order.id', '=', 'order_details.order_id')
                 ->whereNull('order.deleted_at');
        })
        ->where('product_item.published_status', 1)
        ->where('product_item.approval_status', 1);

        if (!empty($request->daterange)) {
            // DATERANGE FORMATING
            $daterange = explode(' - ', $request->daterange);
            $start_date_plain = Helper::convert_datepicker($daterange[0]); // GMT+7
            $start_date = Helper::server_timestamp($start_date_plain . ' 00:00:00', 'Y-m-d H:i:s'); // UTC
            $end_date_plain = Helper::convert_datepicker($daterange[1]); // GMT+7
            $end_date = Helper::server_timestamp($end_date_plain . ' 23:59:59', 'Y-m-d H:i:s'); // UTC

            $query->whereRaw("order.created_at BETWEEN ? AND ?", [$start_date, $end_date]);
        }
    
        $query->groupBy(
            'product_item.id',
            'product_item.name',
            'product_item.summary',
            'product_item.image',
            'product_item.global_stock',
            'product_item.qty',
            'product_item.qty_booked',
            'product_item.qty_sold',
            'seller.store_name',
            'default_variant.slug',
            'default_variant.price'
        )
        ->orderByDesc('percentage');

        return $datatables->eloquent($query)
            ->addColumn('stock_info', function ($data) {
                return $data->global_stock
                    ? "Gbl: {$data->qty} | Sold: {$data->qty_sold} | Booked: {$data->qty_booked}"
                    : "Var: {$data->variant_qty} | Sold: {$data->variant_qty_sold} | Booked: {$data->variant_qty_booked}";
            })
            ->editColumn('price', function ($data) {
                return 'Rp' . number_format($data->price, 0, ',', '.');
            })
            ->editColumn('omzet', function ($data) {
                return 'Rp' . number_format($data->omzet, 0, ',', '.');
            })
            ->editColumn('percentage', function ($data) {
                return number_format($data->percentage, 2) . '%';
            })
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

        // return Excel::download(new ProductSummaryExport($start_date, $end_date), $filename . '.xlsx');

        // Format range
        $range = $request->filled('daterange')
            ? collect(explode(' - ', $request->daterange))
                ->map(fn($d) => \Carbon\Carbon::createFromFormat('d/m/Y', $d)->startOfDay())
                ->toArray()
            : null;

        $export = new ProductSummaryExport($range);
        $exportQuery = clone $export->query();

        // ✅ Cek data
        if ($exportQuery->get()->isEmpty()) {
            return redirect()->back()->with('error', 'Data tidak ditemukan pada periode yang dipilih.');
        }

        $filename = $filename . '.xlsx';

        return Excel::download($export, $filename);
    }

    public function print_pesanan_terlaris(Request $request)
    {
        $daterange = $request->input('daterange', '');
        $range = null;

        if (!empty($daterange)) {
            $dates = explode(' - ', $daterange);
            $start = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
            $end = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
            $range = [$start, $end];
        }

        $query = product_item::select(
            'product_item.name',
            'seller.store_name as seller_name',
            'default_variant.price',
            DB::raw('SUM(COALESCE(order_details.qty, 0)) as unit_terjual'),
            DB::raw('SUM(COALESCE(order_details.qty * order_details.price_per_item, 0)) as omzet')
        )
        ->leftJoin('seller', 'product_item.seller_id', '=', 'seller.id')
        ->leftJoin('product_item_variant as default_variant', function ($join) {
            $join->on('product_item.id', '=', 'default_variant.product_item_id')
                ->where('default_variant.is_default', 1)
                ->where('default_variant.status', 1)
                ->whereNull('default_variant.deleted_at');
        })
        ->leftJoin('order_details', 'order_details.product_id', '=', 'product_item.id')
        ->leftJoin('order', function ($join) {
            $join->on('order.id', '=', 'order_details.order_id')
                ->whereNull('order.deleted_at');
        })
        ->where('product_item.published_status', 1)
        ->where('product_item.approval_status', 1);

        if ($range) {
            $query->whereBetween('order.created_at', $range);
        }

        $query->groupBy('product_item.name', 'seller.store_name', 'default_variant.price');
        $data = $query->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('error', 'Data tidak ditemukan untuk periode tersebut.');
        }

        $pdf = PDF::loadView('admin.order_summary.pdf', compact('data', 'daterange'));

        return $pdf->stream('preview_rekap_order_pesanan.pdf');
    }
}