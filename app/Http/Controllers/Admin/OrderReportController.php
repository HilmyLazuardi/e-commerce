<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

# Exports
use App\Exports\ProductSummaryExport;
use PDF;

// Libraries
use App\Libraries\Helper;

// Models
use App\Models\order;
use App\Models\order_details;
use App\Models\seller;

class OrderReportController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Order Report';
    private $module_id  = 49;

    // SET THIS OBJECT/ITEM NAME
    private $item = 'order report';

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

        return view('admin.order_report.list');
    }

    /**
     * Get a listing of the resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data(Datatables $datatables, Request $request)
    {
        $query = order::select(
            'order.*',
            'order_details.qty',
            'order_details.price_per_item',
            'buyer.fullname AS buyer_name',
            'buyer.phone_number',
            'product_item.name AS product_name',
            'product_item_variant.sku_id',
            'seller.store_name',
            'seller.fullname AS seller_name',
            'seller.phone_number AS seller_phone'
        )
        ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
        ->leftJoin('order_details', 'order.id', 'order_details.order_id')
        ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
        ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
        ->leftJoin('seller', 'order.seller_id', 'seller.id')
        ->orderBy('order.updated_at', 'desc')
        ->groupBy(
            'order.seller_id',
            'order.transaction_id'
        );

    if (!empty($request->status)) {
        $query->where('order.progress_status', $request->status);
    }

    if (!empty($request->daterange)) {
        // DATERANGE FORMATING
        $daterange = explode(' - ', $request->daterange);
        $start_date_plain = Helper::convert_datepicker($daterange[0]); // GMT+7
        $start_date = Helper::server_timestamp($start_date_plain . ' 00:00:00', 'Y-m-d H:i:s'); // UTC
        $end_date_plain = Helper::convert_datepicker($daterange[1]); // GMT+7
        $end_date = Helper::server_timestamp($end_date_plain . ' 23:59:59', 'Y-m-d H:i:s'); // UTC

        // $start_date_plain = explode('/', $daterange[0]);
        // $end_date_plain = explode('/', $daterange[1]);
        // $start_date = $start_date_plain[2].'-'.$start_date_plain[1].'-'.$start_date_plain[0];
        // $end_date = $end_date_plain[2].'-'.$end_date_plain[1].'-'.$end_date_plain[0];
        // DATERANGE QUERY
        $query->whereRaw("order.created_at BETWEEN ? AND ?", [$start_date, $end_date]);

        if (isset($_COOKIE['devon'])) {
            return [$start_date, $end_date];
        }
    }

    return $datatables->eloquent($query)
        ->addColumn('action', function ($data) {
            $object_id = $data->id;
            if (env('CRYPTOGRAPHY_MODE', false)) {
                $object_id = Helper::generate_token($data->id);
            }

            $wording_detail = ucwords(lang('detail', $this->translations));
            $html = '<a href="' . route('admin.order.detail', $object_id) . '" class="btn btn-xs btn-primary" title="' . $wording_detail . '"><i class="fa fa-eye"></i>&nbsp; ' . $wording_detail . '</a>';

            // $wording_delete = ucwords(lang('delete', $this->translations));
            // $html .= '<form action="' . route('admin.order.delete') . '" method="POST" onsubmit="return confirm(\'' . lang('Are you sure to delete this #item?', $this->translations, ['#item' => $this->item]) . '\');" style="display: inline"> ' . csrf_field() . ' <input type="hidden" name="id" value="' . $object_id . '">
            // <button type="submit" class="btn btn-xs btn-danger" title="' . $wording_delete . '"><i class="fa fa-trash"></i>&nbsp; ' . $wording_delete . '</button></form>';
            if ($data->payment_status === 1 || $data->progress_status_label === 2) {
                $wording_sent_wa = ucwords(lang('kirim via whatsApp', $this->translations));
                $html .= '<a href="' . route('admin.order.send_wa', $object_id) . '" class="btn btn-sm btn-success" title="' . $wording_sent_wa . '"><img src="' . asset('/images/ic_wa.png') . '" style="width: 20px; height: 20px;"></a>';
            }
            return $html;
        })
        ->addColumn('payment_status_label', function ($data) {
            $label = 'Pending';

            switch ($data->payment_status) {
                case '1':
                    $label = 'Paid';
                    break;
                
                case '2':
                    $label = 'Expired';
                    break;
            }

            return $label;
        })
        // ->editColumn('shipping_number', function ($data) {
        //     if (!is_null($shipping_number)) {
        //         return $data->shipping_number;
        //     } else {
        //         return '-';
        //     }
        // })
        // ->editColumn('shipped_at', function ($data) {
        //     if (!is_null($shipped_at)) {
        //         return date('Y-m-d', strtotime($data->shipped_at));
        //     } else {
        //         return '-';
        //     }
        // })
        // ->editColumn('updated_at', function ($data) {
        //     return Helper::locale_timestamp($data->updated_at);
        // })
        ->editColumn('created_at', function ($data) {
            return Helper::locale_timestamp($data->created_at, 'd M Y H:i', false);
        })
        ->editColumn('price_per_item', function ($data) {
            return 'Rp' . number_format($data->price_per_item, 0, ',', '.');
        })
        ->editColumn('price_subtotal', function ($data) {
            return 'Rp' . number_format($data->price_subtotal, 0, ',', '.');
        })
        ->editColumn('price_shipping', function ($data) {
            return 'Rp' . number_format($data->price_shipping, 0, ',', '.');
        })
        ->editColumn('insurance_shipping_fee', function ($data) {
            if ($data->insurance_shipping_fee) {
                return 'Rp' . number_format($data->insurance_shipping_fee, 0, ',', '.');
            }
            return '(Tidak Pakai Asuransi)';
        })
        ->editColumn('amount_fee', function ($data) {
            return 'Rp' . number_format($data->amount_fee, 0, ',', '.');
        })
        ->editColumn('price_total', function ($data) {
            return 'Rp' . number_format($data->price_total, 0, ',', '.');
        })
        ->addColumn('total_net', function ($data) {
            return 'Rp' . number_format(($data->price_total - $data->amount_fee), 0, ',', '.');
        })
        ->addColumn('progress_status_label', function ($data) {
            $label = '<span class="label label-primary">UNKNOWN</span>';

            switch ($data->progress_status) {
                case '1':
                    $label = '<span class="label label-info">Menunggu Pembayaran</span>';
                    break;
                
                case '2':
                    $label = '<span class="label label-warning">Siap Dikirim (Terbayar)</span>';
                    break;

                case '3':
                    $label = '<span class="label label-success">Sudah Dikirim</span>';
                    break;
                
                case '4':
                    $label = '<span class="label label-danger">BATAL</span>';
                    break;
                
                case '5':
                    $label = '<span class="label label-default" style="background:#D3A381; color:#FFFFFF;">Refunded</span>';
                    break;
                
                case '6':
                    $label = '<span class="label label-default" style="background:#4CAF50; color:#FFFFFF;">Selesai</span>';
                    break;
            }

            return $label;
        })
        ->editColumn('fullname', function ($data) {
            return $data->fullname;
        })
        ->editColumn('phone_number', function ($data) {
            return $data->phone_number;
        })
        ->editColumn('seller_phone', function ($data) {
            $o = '-';
            if (!empty($data->seller_phone)) {
                $o = '0'.$data->seller_phone;
            }
            return $o;
        })
        ->rawColumns(['action', 'payment_status_label', 'progress_status_label', 'total_net'])
        ->toJson();
    }

    public function preview_pdf(Request $request)
    {
        $daterange = $request->input('daterange', '');
        $status = $request->input('status', '');
        $range = null;

        if (!empty($daterange)) {
            $dates = explode(' - ', $daterange);
            $start = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
            $range = [$start, $end];
        }

        $query = order::select(
            'order.*',
            'order_details.qty',
            'order_details.price_per_item',
            'buyer.fullname AS buyer_name',
            'buyer.phone_number',
            'seller.fullname AS seller_name',
            'seller.store_name',
            'seller.phone_number AS seller_phone'
        )
        ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
        ->leftJoin('order_details', 'order.id', 'order_details.order_id')
        ->leftJoin('seller', 'order.seller_id', 'seller.id')
        ->orderBy('order.created_at', 'desc')
        ->groupBy('order.seller_id', 'order.transaction_id');

        if (!empty($status)) {
            $query->where('order.progress_status', $status);
        }

        if ($range) {
            $query->whereBetween('order.created_at', $range);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan pada periode yang dipilih.');
        }

        $pdf = PDF::loadView('admin.order_report.pdf', [
            'data' => $data,
            'daterange' => $daterange,
            'status_label' => $status
        ])->setPaper('A4', 'landscape');

        return $pdf->stream('rekap_pesanan.pdf');
    }
}