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
use App\Models\invoice;
use App\Models\seller;

class OrderInvoiceController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Order Invoice';
    private $module_id  = 49;

    // SET THIS OBJECT/ITEM NAME
    private $item = 'order invoice';

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

        return view('admin.order_invoice.list');
    }

    /**
     * Get a listing of the resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data(Datatables $datatables, Request $request)
    {
        $query = invoice::select(
                'invoices.*',
                'buyer.fullname AS buyer_name'
            )
            ->leftJoin('buyer', 'invoices.buyer_id', 'buyer.id')
            ->leftJoin('vouchers', 'invoices.voucher_id', 'vouchers.id')
            ->orderBy('invoices.updated_at', 'desc');

        if (!empty($request->status)) {
            $status = Helper::validate_input_text($request->status);
            switch ($status) {
                case 'paid':
                    $query->whereNotNull('invoices.paid_at');
                    $query->where('invoices.payment_status', 1);
                    $query->where('invoices.is_cancelled', 0);
                    break;
                case 'unpaid':
                    $query->whereNull('invoices.paid_at');
                    $query->where('invoices.payment_status', 0);
                    $query->where('invoices.is_cancelled', 0);
                    break;
                case 'cancelled':
                    $query->where('invoices.is_cancelled', 1);
                    break;
            }
        }

        if (!empty($request->daterange)) {
            // DATERANGE FORMATING
            $daterange = explode(' - ', $request->daterange);
            $start_date_plain = Helper::convert_datepicker($daterange[0]); // GMT+7
            $start_date = Helper::server_timestamp($start_date_plain . ' 00:00:00', 'Y-m-d H:i:s'); // UTC
            $end_date_plain = Helper::convert_datepicker($daterange[1]); // GMT+7
            $end_date = Helper::server_timestamp($end_date_plain . ' 23:59:59', 'Y-m-d H:i:s'); // UTC

            $query->whereRaw("invoices.created_at BETWEEN ? AND ?", [$start_date, $end_date]);

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
                $html = '<a href="' . route('admin.invoice.detail', $object_id) . '" class="btn btn-xs btn-primary" title="' . $wording_detail . '"><i class="fa fa-eye"></i>&nbsp; ' . $wording_detail . '</a>';

                return $html;
            })
            ->addColumn('status', function ($data) {
                $label = 'UNKNOWN';

                if (!empty($data->paid_at) && $data->payment_status == 1 && $data->is_cancelled == 0) {
                    $label = 'Paid';
                }

                if (empty($data->paid_at) && $data->payment_status == 0 && $data->is_cancelled == 0) {
                    $label = 'Unpaid';
                }
                
                if ($data->is_cancelled == 1) {
                    $label = 'Cancelled';
                }

                return $label;
            })
            ->editColumn('created_at', function ($data) {
                return Helper::locale_timestamp($data->created_at, 'd M Y H:i', false);
            })
            ->editColumn('subtotal', function ($data) {
                return 'Rp' . number_format($data->subtotal, 0, ',', '.');
            })
            ->editColumn('shipping_fee', function ($data) {
                return 'Rp' . number_format($data->shipping_fee, 0, ',', '.');
            })
            ->editColumn('shipping_insurance_fee', function ($data) {
                if ($data->shipping_insurance_fee) {
                    return 'Rp' . number_format($data->shipping_insurance_fee, 0, ',', '.');
                }
                return '(Tidak Pakai Asuransi)';
            })
            ->editColumn('total_amount', function ($data) {
                return 'Rp' . number_format($data->total_amount, 0, ',', '.');
            })
            ->editColumn('discount_amount', function ($data) {
                if ($data->discount_amount) {
                    return '(' . $data->voucher_code . ') Rp' . number_format($data->discount_amount, 0, ',', '.');
                }
                return '(Tidak Pakai Voucher)';
            })
            ->rawColumns(['action', 'status'])
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

        $query = invoice::select(
                'invoices.*',
                'buyer.fullname AS buyer_name'
            )
            ->leftJoin('buyer', 'invoices.buyer_id', 'buyer.id')
            ->leftJoin('vouchers', 'invoices.voucher_id', 'vouchers.id')
            ->orderBy('invoices.created_at', 'desc');

        if (!empty($status)) {
            switch ($status) {
                case 'paid':
                    $query->whereNotNull('invoices.paid_at')->where('invoices.payment_status', 1)->where('invoices.is_cancelled', 0);
                    break;
                case 'unpaid':
                    $query->whereNull('invoices.paid_at')->where('invoices.payment_status', 0)->where('invoices.is_cancelled', 0);
                    break;
                case 'cancelled':
                    $query->where('invoices.is_cancelled', 1);
                    break;
            }
        }

        if ($range) {
            $query->whereBetween('invoices.created_at', $range);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan pada periode yang dipilih.');
        }

        $pdf = PDF::loadView('admin.order_invoice.pdf', [
            'data' => $data,
            'daterange' => $daterange,
            'status' => ucfirst($status)
        ])->setPaper('A4', 'landscape');

        return $pdf->stream('invoice_preview.pdf');
    }
}