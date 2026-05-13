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
use App\Models\order_details;
use App\Models\seller;

class OrderController extends Controller
{
    // SET THIS MODULE
    private $module     = 'Order';
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

        return view('admin.order.list');
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  id   $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function detail_v1($id, Request $request)
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
                ->route('admin.order')
                ->with('error', lang('Invalid #item ID, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        // GET DATA BY ID
        $data = order::select(
                'order.*',
                'order_details.qty',
                'buyer.fullname as buyer_fullname',
                'buyer.phone_number as buyer_phone_number',
                'product_item_variant.name as product_item_variant_name',
                'product_item.name as product_item_name'
            )
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->join('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->where('order.id', $id)
            ->first();

        $data->payment_status_label     = 'Pending';
        $data->progress_status_label    = 'Pending';

        switch ($data->payment_status) {
            case '1':
                $data->payment_status_label = 'Paid';
                break;
            
            case '2':
                $data->payment_status_label = 'Expired';
                break;
        }

        switch ($data->progress_status) {
            case '1':
                $data->progress_status_label = 'On-process';
                break;
            
            case '2':
                $data->progress_status_label = 'Shipped';
                break;
        }

        $data->price_per_item   = 'Rp. ' . number_format($data->price_per_item);
        $data->price_discount   = 'Rp. ' . number_format($data->price_discount);
        $data->price_subtotal   = 'Rp. ' . number_format($data->price_subtotal);
        $data->price_shipping   = 'Rp. ' . number_format($data->price_shipping);
        $data->price_total      = 'Rp. ' . number_format($data->price_total);

        if (is_null($data->shipping_number)) {
            $data->shipping_number = '-';
        }

        if (!is_null($data->shipped_at)) {
            $data->shipped_at = Helper::locale_timestamp($data->shipped_at);
        } else {
            $data->shipped_at = '-';
        }

        // CHECK IS DATA FOUND
        if (empty($data)) {
            # FAILED - DATA NOT FOUND
            return redirect()
                ->route('admin.order')
                ->with('error', lang('#item not found, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        return view('admin.order.form', compact('data', 'raw_id'));
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
                ->route('admin.order')
                ->with('error', lang('Invalid #item ID, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        // GET DATA BY ID
        $data = order::select(
                'order.id',
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
                'order.refund_photo',
                'order.refund_note',
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
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->join('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('id_provinces as provinces', 'order.shipment_province_code', 'provinces.code')
            ->leftJoin('id_cities as cities', 'order.shipment_district_code', 'cities.full_code')
            ->leftJoin('id_sub_districts as sub_districts', 'order.shipment_sub_district_code', 'sub_districts.full_code')
            ->leftJoin('id_villages as villages', 'order.shipment_village_code', 'villages.full_code')
            ->where('order.id', $id)
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
                ->route('admin.order')
                ->with('error', lang('#item not found, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        $order_details = order_details::where('order_id', $data->id)
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

        return view('admin.order.invoice', compact('data', 'raw_id', 'order_details', 'seller'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        // AUTHORIZING...
        $authorize = Helper::authorizing($this->module, 'Delete');
        if ($authorize['status'] != 'true') {
            return back()->with('error', $authorize['message']);
        }

        // SET THIS OBJECT/ITEM NAME BASED ON TRANSLATION
        $this->item = ucwords(lang($this->item, $this->translations));

        $raw_id = $request->id;

        if (env('CRYPTOGRAPHY_MODE', false)) {
            $id = Helper::validate_token($raw_id);
        }

        // GET DATA BY ID
        $data = order::find($id);

        // CHECK IS DATA FOUND
        if (!$data) {
            # FAILED - DATA NOT FOUND
            return back()
                ->withInput()
                ->with('error', lang('#item not found, please reload your page before resubmit', $this->translations, ['#item' => $this->item]));
        }

        // store data before updated
        $value_before = $data->toJson();

        DB::beginTransaction();
        try {
            // DELETE DATA TABLE ORDER DETAILS By ORDER ID
            order_details::where('order_id', $data->id)->delete();

            $data->delete();
            
            // logging
            $log_detail_id  = 8; // delete
            $module_id      = $this->module_id;
            $target_id      = $data->id;
            $note           = '';
            $value_after    = null;
            $ip_address     = $request->ip();
            Helper::logging($log_detail_id, $module_id, $target_id, $note, $value_before, $value_after, $ip_address);

            # SUCCESS
            DB::commit();

            return redirect()
                ->route('admin.order')
                ->with('success', lang('Successfully deleted #item', $this->translations, ['#item' => $this->item]));
        } catch (Exception $e) {
            DB::rollback();

            $error_msg = $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine();
            Helper::error_logging($error_msg, $this->module_id, $id);

            if (env('APP_DEBUG') == false) {
                $error_msg = lang('Oops, something went wrong please try again later.', $this->translations);
            }

            # ERROR
            return back()->withInput()->with('error', $error_msg);
        }

        # FAILED
        // return back()->with('error', lang('Oops, failed to delete #item. Please try again.', $this->translations, ['#item' => $this->item]));
    }

    /**
     * Display a listing of the deleted resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleted_data()
    {
        $deleted_data = true;

        return view('admin.order.list', compact('deleted_data'));
    }

    /**
     * Get a listing of the deleted resource using DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_deleted_data(Datatables $datatables, Request $request)
    {
        $query = order::select(
            'order.*',
            'order_details.qty',
            'order_details.price_per_item',
            'buyer.fullname AS buyer_name',
            'buyer.phone_number',
            'product_item.name AS product_name',
            'product_item_variant.sku_id',
            'seller.fullname AS seller_name',
            'seller.phone_number AS seller_phone'
        )
        ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
        ->leftJoin('order_details', 'order.id', 'order_details.order_id')
        ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
        ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('seller', 'order.seller_id', 'seller.id')
        ->onlyTrashed();

        return $datatables->eloquent($query)
            ->addColumn('action', function ($data) {
                $object_id = $data->id;
                if (env('CRYPTOGRAPHY_MODE', false)) {
                    $object_id = Helper::generate_token($data->id);
                }

                $wording_restore = ucwords(lang('restore', $this->translations));
                return '<form action="' . route('admin.order.restore') . '" method="POST" onsubmit="return confirm(\'' . lang('Are you sure to restore this #item?', $this->translations, ['#item' => $this->item]) . '\');" style="display: inline"> ' . csrf_field() . ' <input type="hidden" name="id" value="' . $object_id . '">
                <button type="submit" class="btn btn-xs btn-success" title="' . $wording_restore . '"><i class="fa fa-check"></i>&nbsp; ' . $wording_restore . '</button></form>';
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
                return 'Rp. ' . number_format($data->price_per_item, 0, ',', '.');
            })
            ->editColumn('price_subtotal', function ($data) {
                return 'Rp' . number_format($data->price_subtotal, 0, ',', '.');
            })
            ->editColumn('price_shipping', function ($data) {
                return 'Rp. ' . number_format($data->price_shipping, 0, ',', '.');
            })
            ->editColumn('insurance_shipping_fee', function ($data) {
                if ($data->insurance_shipping_fee) {
                    return 'Rp' . number_format($data->insurance_shipping_fee, 0, ',', '.');
                }
                return '(Tidak Pakai Asuransi)';
            })
            ->editColumn('amount_fee', function ($data) {
                return 'Rp. ' . number_format($data->amount_fee, 0, ',', '.');
            })
            ->editColumn('price_total', function ($data) {
                return 'Rp. ' . number_format($data->price_total, 0, ',', '.');
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

    /**
     * Restore the specified deleted resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request)
    {
        // AUTHORIZING...
        $authorize = Helper::authorizing($this->module, 'Restore');
        if ($authorize['status'] != 'true') {
            return back()->with('error', $authorize['message']);
        }

        // SET THIS OBJECT/ITEM NAME BASED ON TRANSLATION
        $this->item = ucwords(lang($this->item, $this->translations));

        $raw_id = $request->id;

        if (env('CRYPTOGRAPHY_MODE', false)) {
            $id = Helper::validate_token($raw_id);
        }

        // GET DATA BY ID
        $data = order::onlyTrashed()->find($id);

        // CHECK IS DATA FOUND
        if (!$data) {
            # FAILED - DATA NOT FOUND
            return back()
                ->withInput()
                ->with('error', lang('#item not found, please reload your page before resubmit', $this->translations, ['#item' => $this->item]));
        }

        // RESTORE THE DATA
        if ($data->restore()) {
            // logging
            $log_detail_id  = 9; // restore
            $module_id      = $this->module_id;
            $target_id      = $data->id;
            $note           = '';
            $value_before   = null;
            $value_after    = $data->toJson();
            $ip_address     = $request->ip();
            Helper::logging($log_detail_id, $module_id, $target_id, $note, $value_before, $value_after, $ip_address);

            # SUCCESS
            return redirect()
                ->route('admin.order.deleted_data')
                ->with('success', lang('Successfully restored #item', $this->translations, ['#item' => $this->item]));
        }

        # FAILED
        return back()->with('error', lang('Oops, failed to restore #item. Please try again.', $this->translations, ['#item' => $this->item]));
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

        $status = '';
        if (!empty($request->status)) {
            $status = $request->status;
        }
        $start_date = '';
        $end_date = '';
        if (!empty($request->daterange)) {
            // DATERANGE FORMATING
            $daterange = explode(' - ', $request->daterange);
            $start_date_plain = explode('/', $daterange[0]);
            $end_date_plain = explode('/', $daterange[1]);
            $start_date = $start_date_plain[2].'-'.$start_date_plain[1].'-'.$start_date_plain[0];
            $end_date = $end_date_plain[2].'-'.$end_date_plain[1].'-'.$end_date_plain[0];
        }

        // SET FILE NAME
        $filename = date('YmdHis') . '_' . $this->module;

        return Excel::download(new OrderExportView($status, $start_date, $end_date), $filename . '.xlsx');
    }

    public function request_pickup(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()
                ->route('admin.order');
        } else {
            $id = $request->id;
            if (env('CRYPTOGRAPHY_MODE', false)) {
                $id = Helper::validate_token($id);
            }

            // CHECK OBJECT ID
            if ((int) $id < 1) {
                // INVALID OBJECT ID
                return redirect()
                    ->route('admin.order')
                    ->with('error', 'Order ID tidak valid, mohon coba lagi.');
            }

            // GET DATA BY ID
            $data = order::select(
                    'order.id',
                    'order.transaction_id',
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
                    'order.shipment_postal_code',
                    'order.origin_code',
                    'order.destination_code',
                    'order.shipment_remarks',
                    'order.receiver_name',
                    'order.receiver_phone',

                    'buyer.fullname as buyer_firstname',
                    // 'buyer.lastname as buyer_lastname',
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
                ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
                ->join('order_details', 'order.id', 'order_details.order_id')
                ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
                ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
                ->leftJoin('id_provinces as provinces', 'order.shipment_province_code', 'provinces.code')
                ->leftJoin('id_cities as cities', 'order.shipment_district_code', 'cities.full_code')
                ->leftJoin('id_sub_districts as sub_districts', 'order.shipment_sub_district_code', 'sub_districts.full_code')
                ->leftJoin('id_villages as villages', 'order.shipment_village_code', 'villages.full_code')
                ->where('order.id', $id)
                ->first();

            // CHECK IS DATA FOUND
            if (empty($data)) {
                # FAILED - DATA NOT FOUND
                return redirect()
                    ->route('admin.order')
                    ->with('error', 'Order tidak ditemukan, mohon periksa kembali');
            }

            $receiver = new \stdClass();
            $receiver->name = $data->receiver_name;
            $receiver->phone = $data->receiver_phone;
            $receiver->email = $data->buyer_email;
            $receiver->district = $data->destination_code;
            $receiver->address = $data->shipment_address_details;
            $receiver->postcode = $data->shipment_postal_code;
            $receiver->geoloc = "";
            $receiver->province = $data->province_name;
            $receiver->city = $data->city_name;

            $use_insurance = false;
            if ($data->use_insurance_shipping) {
                $use_insurance = true;
            }

            // GET SHIPPER DATA
            $company_info = HelperWeb::get_company_info();

            $shipper = new \stdClass();
            $shipper->name = $company_info->name;
            $shipper->phone = '0' . $company_info->wa_phone;
            $shipper->email = $company_info->email_office ?: env('MAIL_FROM_ADDRESS', 'admin@example.com');
            $shipper->district = $data->origin_code;
            $shipper->address = $company_info->address;
            $shipper->postcode = $company_info->postal_code;
            $shipper->geoloc = "";
            $shipper->province = $company_info->province_name;
            $shipper->city = $company_info->city_name;

            // GET ITEM LIST
            $order_details = order_details::select(
                    'order_details.*',
                    'product_item.image as product_image',
                    'product_item.name as product_name',
                    'product_item_variant.sku_id as variant_sku',
                    'product_item_variant.name as variant_name'
                )
                ->where('order_id', $data->id)
                ->leftjoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
                ->leftjoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
                ->get();

            $items      = [];
            $total_qty  = 0;
            foreach ($order_details as $item) {
                $new_item = new \stdClass();
                // $new_item->item_name = $item->product_name;
                // $new_item->item_quantity = $item->qty;
                // $new_item->declared_value = $item->price_per_item;

                $item_weight = $item->weight;
                if ($item_weight < 100) {
                    $item_weight = 100;
                } elseif ($item_weight > 50000) {
                    # ERROR
                    return back()
                        ->with('error', 'Berat per item melebihi nilai maksimum 50 KG');
                }
                // $new_item->weight = $item_weight;
                $new_item->product_name = $item->product_name;
                $new_item->product_variant_name = $item->variant_name;
                $new_item->product_price = $item->price_per_item;
                $new_item->product_width = 1;
                $new_item->product_height = 2;
                $new_item->product_weight = $item->item_weight;
                $new_item->product_length = 20;
                $new_item->qty = $item->qty;
                $new_item->subtotal = $item->price_subtotal;

                $items[] = $new_item;

                $total_qty += $item->qty;
            }

            // using current time based on APP_TIMEZONE, example: GMT+7
            $expect_time = Helper::current_datetime('Y-m-d H:i:s');

            // dd($data->id, $data->shipper_service_type, $data->shipper_name, $data->shipment_total_weight, $shipper, $receiver, $receiver->name, $items, $use_insurance, $data->price_subtotal, $expect_time, $data);
            switch (strtolower($data->shipper_name)) {
                // case 'anteraja':
                case 'larizzka_jaya':
                    // invoice_no = {ENV(ANTERAJA_PREFIX_ORDER)}-{order.id}
                    // Anteraja::create_order($invoice_no, $service_type, $total_weight_grams, $shipper, $receiver, $items, $use_insurance, $declared_value, $expect_time)
                    $data_id = $data->id;
                    if ($data_id < 10) {
                        $data_id = '0'.$data->id;
                    }

                    $result = Anteraja::create_order($data_id, $data->shipper_service_type, $data->shipment_total_weight, $shipper, $receiver, $items, $use_insurance, $data->price_subtotal, $expect_time, $data);
                    // dd($result);
                    if (!$result['status']) {
                        $error_msg = $result['info'];
                        Helper::error_logging($error_msg, $this->module_id, $id, 'Failed to request pickup AnterAja (Seller Dashboard)');

                        return back()->with('error', $error_msg);
                    }
                    // if (!isset($result['data']->content->waybill_no)) {
                    if (!isset($result['data'][0]->awb)) {
                        $error_msg = 'Nomor resi pengiriman gagal diperoleh dari kurir, mohon coba lagi';
                        Helper::error_logging($error_msg, $this->module_id, $id, 'Failed to request pickup AnterAja (Seller Dashboard) - API Response: ' . json_encode($result));

                        return back()->with('error', $error_msg);
                    }
                    // $awb = $result['data']->content->waybill_no;
                    $order_no = $result['data'][0]->order_no;
                    $awb = $result['data'][0]->awb;
                    break;

                default:
                    # ERROR
                    return back()->with('error', 'Kurir tidak dikenali, mohon coba lagi');
                    break;
            }

            DB::beginTransaction();
            try {
                // UPDATE RESI IN ORDER TABLE
                order::where('id', $id)->update(
                    [
                        'shipping_order_no' => $order_no,
                        'shipping_number' => $awb,
                        'progress_status' => 3,
                        'shipped_at' => date('Y-m-d H:i:s'),
                    ]
                );

                // ambil data order utk ditampilkan di email
                $order_details = order::select(
                    'product_item.id as product_id',
                    'product_item.image as product_image',
                    'product_item.name as product_name',
        
                    'product_item_variant.id as product_variant_id',
                    'product_item_variant.name as product_variant_name',
                    'product_item_variant.variant_image as product_variant_image',
                    'order_details.qty',
                    'order_details.weight',
        
                    'order.price_subtotal',
                    'order.price_shipping',
                    'order.insurance_shipping_fee',
                    'order.price_total',
        
                    'order.transaction_id',
                    'order.receiver_name',
                    'order.receiver_phone',
                    'order.shipment_address_details as receiver_address',
                    'order.expired_at',

                    'id_provinces.name as receiver_province_name',
                    'id_cities.name as receiver_city_name',
                    'id_sub_districts.name as receiver_sub_district_name',
                    'id_villages.name as receiver_village_name',
                    'order.shipment_postal_code as receiver_postal_code'
                )
                    ->leftJoin('order_details', 'order.id', 'order_details.order_id')
                    ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
                    ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
                    ->leftJoin('id_provinces', 'order.shipment_province_code', 'id_provinces.code')
                    ->leftJoin('id_cities', 'order.shipment_district_code', 'id_cities.full_code')
                    ->leftJoin('id_sub_districts', 'order.shipment_sub_district_code', 'id_sub_districts.full_code')
                    ->leftJoin('id_villages', 'order.shipment_village_code', 'id_villages.full_code')
                    ->where('order.id', $id)
                    ->get();

                // # ubah format data order berdasarkan seller
                $params_child = [
                    'product_id',
                    'product_image',
                    'product_name',
                    'product_variant_id',
                    'product_variant_name',
                    'product_variant_image',
                    'qty',
                    'weight',
                    'transaction_id',
                    'receiver_name',
                    'receiver_phone',
                    'receiver_address',
                    'receiver_province_name',
                    'receiver_city_name',
                    'receiver_sub_district_name',
                    'receiver_village_name',
                    'receiver_postal_code'
                ];
                $order_per_seller = Helper::generate_parent_child_data($order_details, 'seller_id', $params_child);

                // SET EMAIL CONTENT
                $email_template                     = 'emails.order.buyer_shipped_order';
                $this_subject                       = '[' . $data->transaction_id . '] Produk yang kamu pesan sudah dikirim!';

                $content                            = [];
                $content['title']                   = '[' . $data->transaction_id . '] Produk yang kamu pesan sudah dikirim!';
                $content['email']                   = $data->buyer_email;
                $content['wa_number']               = $company_info->wa_phone;
                $content['wa_link']                 = 'http://wa.me/' . $company_info->wa_phone;

                $content['user_name']               = $data->buyer_firstname;

                $month = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $content['tanggal']                 = date('d', strtotime($data->created_at)) . ' ' . $month[date('m', strtotime($data->created_at))] . ' ' . date('Y', strtotime($data->created_at)); // TANGGAL BULAN TAHUN
                $content['order_id']                = $data->transaction_id;

                $created_at                         = date('Y-m-d', strtotime($data->created_at));
                $created_at                         = explode('-', $created_at);
                $tgl_indo                           = $created_at[2] . ' ' . $this->bulan[(int)$created_at[1]] . ' ' . $created_at[0];
                $content['order_date']              = $tgl_indo; // 28 Agustus 2021

                $shipping_type_mail = explode(' ', $data->shipper_service_type);
                $shipping_mail = strtoupper($shipping_type_mail[0]);

                $content['service']                 = $shipping_mail . ' - ' . strtoupper($data->shipper_service_type); // JNE - JNE YES
                $content['resi_number']             = $awb;
                $content['payment_method']          = $data->payment_method;

                $content['item_name']               = $order_details[0]->product_name;
                $content['item_price']              = 'Rp' . number_format($order_details[0]->price_per_item, 0, ',', '.');
                $content['item_quantity']           = $order_details[0]->qty;
                $content['item_weight']             = number_format(($data->shipment_total_weight / 1000), 1);;
                $content['item_variant']            = $order_details[0]->variant_name;
                $content['item_images']             = env('MAIN_URL') .'/'. $order_details[0]->product_image;

                $content['subtotal']                = 'Rp' . number_format($order_details[0]->price_subtotal, 0, ',', '.');
                $content['shipping_fee']            = 'Rp' . number_format($data->price_shipping, 0, ',', '.');
                $content['insurance_shipping_fee']  = 'Rp' . number_format($data->insurance_shipping_fee, 0, ',', '.');
                $content['total_price']             = 'Rp' . number_format($data->price_total, 0, ',', '.');

                $content['buyer_address']           = $data->wa_number;
                $content['buyer_address']           = $data->village_name . ', ' . $data->sub_district_name . ', ' . $data->city_name . ', ' . $data->province_name;
                if (isset($data->shipment_postal_code)) {
                    $content['buyer_address']      .= ' - ' . $data->shipment_postal_code;
                }
                if ($data->remarks) {
                    $content['buyer_address']      .= '<br> ' . $data->remarks;
                }

                $content['link_order_detail']       = env('MAIN_URL') .'/'. 'order-detail/' . $data->transaction_id;
                $content['link_order_history']      = env('MAIN_URL') .'/'. 'order-history';
                $content['link_web']                = env('MAIN_URL');
                $content['link_instagram']          = 'https://www.instagram.com/official.emasmini/';
                $email                              = $content['email'];

                $content['orders']                  = $order_per_seller;

                // SEND EMAIL
                Mail::send($email_template, ['data' => $content], function ($message) use ($email, $this_subject) {
                    if (env('APP_MODE', 'STAGING') == 'STAGING') {
                        $this_subject = '[STAGING] ' . $this_subject;
                    }

                    $message->subject($this_subject);
                    $message->to($email);
                });

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();

                $error_msg = $ex->getMessage() . ' in ' . $ex->getFile() . ' at line ' . $ex->getLine();
                Helper::error_logging($error_msg, $this->module_id, $id);

                if (env('APP_DEBUG') == false) {
                    $error_msg = lang('Oops, something went wrong please try again later.', $this->translations);
                }

                # ERROR
                return back()->withInput()->with('error', $error_msg);
            }


            return back()->with('success', 'Berhasil melakukan request pickup');
        }
    }

    public function send_wa($id, Request $request)
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
                ->route('admin.order')
                ->with('error', lang('Invalid #item ID, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        $data = order::select(
                'order.id',
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
                'product_item.image',

                'provinces.name as province_name',
                'cities.name as city_name',
                'sub_districts.name as sub_district_name',
                'villages.name as village_name',
                'villages.pos_code as village_postal_codes',

                'seller.store_name',
                'seller.fullname AS seller_name',
                'seller.phone_number AS seller_phone'
            )
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->join('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('id_provinces as provinces', 'order.shipment_province_code', 'provinces.code')
            ->leftJoin('id_cities as cities', 'order.shipment_district_code', 'cities.full_code')
            ->leftJoin('id_sub_districts as sub_districts', 'order.shipment_sub_district_code', 'sub_districts.full_code')
            ->leftJoin('id_villages as villages', 'order.shipment_village_code', 'villages.full_code')
            ->leftJoin('seller', 'order.seller_id', 'seller.id')
            ->where('order.id', $id)
            ->first();

        // dd($data);
            
        $urlDetail = route('web.order.order_detail', $data->transaction_id);
        $productImageUrl = env('APP_URL') . $data->image ?? 'https://yourdomain.com/default-image.jpg';

        $message = "📦 *Pesanan Baru!* 📦\n\n".
            "Halo {$data->buyer_fullname} 👋\n\n".
            "Terima kasih telah memesan di *{$data->seller_name}*!\n\n".
            "🧾 No. Transaksi: {$data->transaction_id}\n".
            "📦 Total: {$data->price_total}\n".
            "🔗 *Cek detail pesananmu di:\n".
            "👉 $urlDetail\n\n".
            "📞 Kontak Toko: 0{$data->seller_phone}\n\n".
            "Silakan tunggu, pesananmu sedang kami proses.🙏 \n\n";
            // "📸 Lihat produk di bawah ⬇️";

        $url_wablas = env('WABLAS_API_URL');

        // Kirim pesan text lebih dulu
        $sendText = Http::withHeaders([
            'Authorization' => env('WABLAS_API_KEY'),
        ])->post($url_wablas . '/api/send-message', [
            'phone' => $data->buyer_phone_number,
            'message' => $message,
            'secret' => false,
            'priority' => false,
        ]);

        // Kirim gambar (opsional)
        // $sendImage = Http::withHeaders([
        //     'Authorization' => env('WABLAS_API_KEY'),
        // ])->post($url_wablas . '/api/send-image', [
        //     'phone' => $data->buyer_phone_number,
        //     'caption' => "📦 Produk: {$data->product_item_name}",
        //     'image' => $productImageUrl, // URL gambar harus bisa diakses publik
        // ]);

        return redirect()->back()->with('success', 'Pesan WhatsApp berhasil dikirim ke '. $data->buyer_fullname . ' !!!');
    }

    public function print_invoice($id)
    {
        $data = order::select(
                'order.id',
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
            ->leftJoin('buyer', 'order.buyer_id', 'buyer.id')
            ->join('order_details', 'order.id', 'order_details.order_id')
            ->leftJoin('product_item_variant', 'order_details.product_id', 'product_item_variant.id')
            ->leftJoin('product_item', 'product_item_variant.product_item_id', 'product_item.id')
            ->leftJoin('id_provinces as provinces', 'order.shipment_province_code', 'provinces.code')
            ->leftJoin('id_cities as cities', 'order.shipment_district_code', 'cities.full_code')
            ->leftJoin('id_sub_districts as sub_districts', 'order.shipment_sub_district_code', 'sub_districts.full_code')
            ->leftJoin('id_villages as villages', 'order.shipment_village_code', 'villages.full_code')
            ->where('order.id', $id)
            ->first();

        if (is_null($data->shipping_number)) {
            $data->shipping_number = '-';
        }

        // CHECK IS DATA FOUND
        if (empty($data)) {
            # FAILED - DATA NOT FOUND
            return redirect()
                ->route('admin.order')
                ->with('error', lang('#item not found, please check your link again', $this->translations, ['#item' => $this->item]));
        }

        $order_details = order_details::where('order_id', $data->id)
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

        return view('admin.order.print_invoice', compact('data', 'seller', 'order_details'));
    }
}