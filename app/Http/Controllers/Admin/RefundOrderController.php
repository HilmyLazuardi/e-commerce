<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrderExportView;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

// Libraries
use App\Libraries\Helper;
use App\Libraries\Anteraja;
use App\Libraries\HelperWeb;

// Models
use App\Models\order;
use App\Models\order_refund;
use App\Models\order_details;
use App\Models\seller;

class RefundOrderController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Order Refund';
    private $module_id  = 28;

    // SET THIS OBJECT/ITEM NAME
    private $item = 'order';

    protected $bulan            = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    protected $bulan3char       = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des');
    protected $indonesian_day   = array(
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
        'Sunday'    => 'Minggu'
    );

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

        return view('admin.order_refund.list');
    }

    /**
     * Get a listing of the resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data(Datatables $datatables, Request $request)
    {
        $query = order_refund::select(
                'order_refund.*',

                // 'order.id',
                'order.invoice_id',
                'order.transaction_id',
                'order.seller_id',
                'order.buyer_id',
                'order.shipment_province_code',
                'order.shipment_district_code',
                'order.shipment_sub_district_code',
                'order.shipment_village_code',
                'order.shipment_postal_code',
                'order.shipment_address_details',
                'order.shipment_remarks',
                'order.receiver_name',
                'order.receiver_phone',
                'order.shipper_id',
                'order.shipper_name',
                'order.shipper_service_type',
                'order.origin_code',
                'order.destination_code',
                'order.shipment_total_weight',
                'order.shipping_order_no',
                'order.shipping_number',
                'order.shipped_at',
                'order.estimate_arrived_at',
                'order.price_shipping',
                'order.use_insurance_shipping',
                'order.insurance_shipping_fee',
                'order.price_subtotal',
                'order.price_discount',
                'order.price_total',
                'order.percentage_fee',
                'order.amount_fee',
                'order.order_remarks',
                'order.payment_url',
                'order.payment_result_id',
                'order.payment_method',
                'order.payment_channel',
                'order.payment_remarks',
                'order.paid_at',
                'order.payment_status',
                'order.progress_status',

                'order_details.qty',
                'order_details.price_per_item',
                'buyer.fullname AS buyer_name',
                'buyer.phone_number',
                'product_item.name AS product_name',
                'product_item_variant.sku_id',
                'seller.store_name',
                'seller.fullname AS seller_name',
                'seller.phone_number AS seller_phone',
                // 'order_refund.refund_notes',
                // 'order_refund.refund_photo as order_refund_photo',
                // 'order_refund.created_at as create_order_refund'
            )
            ->leftJoin('order', 'order_refund.order_id', 'order.id')
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->leftJoin('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('seller', 'order.seller_id', 'seller.id')
            ->where('order.progress_status', 5)
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
            $query->whereRaw("order_refund.created_at BETWEEN ? AND ?", [$start_date, $end_date]);

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
                $html = '<a href="' . route('admin.refund_order.detail', $object_id) . '" class="btn btn-xs btn-primary" title="' . $wording_detail . '"><i class="fa fa-eye"></i>&nbsp; ' . $wording_detail . '</a>';

                // $wording_delete = ucwords(lang('delete', $this->translations));
                // $html .= '<form action="' . route('admin.order.delete') . '" method="POST" onsubmit="return confirm(\'' . lang('Are you sure to delete this #item?', $this->translations, ['#item' => $this->item]) . '\');" style="display: inline"> ' . csrf_field() . ' <input type="hidden" name="id" value="' . $object_id . '">
                // <button type="submit" class="btn btn-xs btn-danger" title="' . $wording_delete . '"><i class="fa fa-trash"></i>&nbsp; ' . $wording_delete . '</button></form>';
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
            ->editColumn('refund_photo', function ($data) {
                if (!is_null($data->refund_photo) || $data->refund_photo != '') {
                    $html = '<img src="' . env('APP_URL') . $data->refund_photo . '" height="100">';
                } else {
                    $html = '<img src="' . env('APP_URL') . 'images/no-image.png" height="100">';
                }

                return $html;
            })
            
            ->rawColumns(['action', 'payment_status_label', 'progress_status_label', 'total_net', 'refund_photo'])
            ->toJson();
    }

    public function detail($id, Request $request)
    {
        // AUTHORIZING...
        $authorize = Helper::authorizing($this->module, 'View Details');
        if ($authorize['status'] != 'true') {
            return back()->with('error', $authorize['message']);
        }

        // SET THIS OBJECT/ITEM NAME BASED ON TRANSLATION
        $this->item = ucwords(lang($this->item, $this->translations));

        $raw_id = $id;

        if (env('CRYPTOGRAPHY_MODE', false)) {
            $id = Helper::validate_token($id);
        }

        // CHECK OBJECT ID
        if ((int) $id < 1) {
            // INVALID OBJECT ID
            return redirect()
                ->route('admin.order_refund')
                ->with('error', lang('Invalid #item ID, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        // GET DATA BY ID
        $data = order_refund::select(
                'order_refund.*',
                // 'order.id',
                'order.transaction_id',
                'order.seller_id',
                'order.buyer_id',
                'order.shipment_address_details',
                'order.shipper_id',
                'order.shipper_name',
                'order.shipper_service_type',
                'order.shipment_total_weight',
                'order.shipping_number',
                'order.shipped_at',
                'order.estimate_arrived_at',
                'order.price_shipping',
                'order.use_insurance_shipping',
                'order.insurance_shipping_fee',
                'order.price_subtotal',
                'order.price_discount',
                'order.price_total',
                'order.order_remarks',
                'order.payment_result_id',
                'order.payment_method',
                'order.payment_channel',
                'order.payment_remarks',
                'order.paid_at',
                'order.expired_at',
                'order.payment_status',
                'order.progress_status',
                'order.created_at',
                'order.updated_at',

                'order_details.qty',

                'buyer.fullname as buyer_fullname',
                'buyer.phone_number as buyer_phone_number',
                'buyer.email as buyer_email',

                'product_item_variant.name as product_item_variant_name',
                'product_item.name as product_item_name',

                'provinces.name as province_name',
                'cities.name as city_name',
                'sub_districts.name as sub_district_name',
                'villages.name as village_name',
                'villages.pos_code as village_postal_codes'
            )
            ->leftJoin('order', 'order_refund.order_id', 'order.id')
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->join('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('id_provinces as provinces', 'order.shipment_province_code', 'provinces.code')
            ->leftJoin('id_cities as cities', 'order.shipment_district_code', 'cities.full_code')
            ->leftJoin('id_sub_districts as sub_districts', 'order.shipment_sub_district_code', 'sub_districts.full_code')
            ->leftJoin('id_villages as villages', 'order.shipment_village_code', 'villages.full_code')
            ->where('order_refund.id', $id)
            ->where('order.progress_status', 5)
            ->first();

        // $data->payment_status_label     = 'Pending';
        // $data->progress_status_label    = 'Pending';

        // switch ($data->payment_status) {
        //     case '1':
        //         $data->payment_status_label = 'Paid';
        //         break;
            
        //     case '2':
        //         $data->payment_status_label = 'Expired';
        //         break;
        // }

        // switch ($data->progress_status) {
        //     case '1':
        //         $data->progress_status_label = 'On-process';
        //         break;
            
        //     case '2':
        //         $data->progress_status_label = 'Shipped';
        //         break;
        // }

        // $data->price_per_item   = 'Rp. ' . number_format($data->price_per_item);
        // $data->price_discount   = 'Rp. ' . number_format($data->price_discount);
        // $data->price_subtotal   = 'Rp. ' . number_format($data->price_subtotal);
        // $data->price_shipping   = 'Rp. ' . number_format($data->price_shipping);
        // $data->price_total      = 'Rp. ' . number_format($data->price_total);

        if (is_null($data->shipping_number)) {
            $data->shipping_number = '-';
        }

        // if (!is_null($data->shipped_at)) {
        //     $data->shipped_at = Helper::locale_timestamp($data->shipped_at);
        // } else {
        //     $data->shipped_at = '-';
        // }

        // CHECK IS DATA FOUND
        if (empty($data)) {
            # FAILED - DATA NOT FOUND
            return redirect()
                ->route('admin.order_refund')
                ->with('error', lang('#item not found, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        $order_details = order_details::where('order_id', $data->order_id)
            ->leftjoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftjoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->select(
                'order_details.*',
                'product_item.image as product_image',
                'product_item.name as product_name',
                // 'product_item.campaign_end as product_campaign_end',
                'product_item_variant.sku_id as variant_sku',
                'product_item_variant.name as variant_name'
            )
            ->get();

        // GET SELLER ADDRESS
        $seller = seller::where('seller.id', $data->seller_id)
            ->select(
                'seller.fullname',
                'seller.phone_number',
                'seller.email',
                'provinces.name as province_name',
                'cities.name as city_name',
                'sub_districts.name as sub_district_name',
                'villages.name as village_name',
                'villages.pos_code as village_postal_codes'
            )
            ->leftJoin('id_provinces as provinces', 'seller.province_code', 'provinces.code')
            ->leftJoin('id_cities as cities', 'seller.district_code', 'cities.full_code')
            ->leftJoin('id_sub_districts as sub_districts', 'seller.sub_district_code', 'sub_districts.full_code')
            ->leftJoin('id_villages as villages', 'seller.village_code', 'villages.full_code')
            ->first();

        return view('admin.order_refund.detail', compact('data', 'raw_id', 'order_details', 'seller'));
    }
}