<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\{
    FromQuery, WithHeadings, WithMapping, ShouldAutoSize, Exportable
};
use App\Models\product_item;
use Illuminate\Support\Facades\DB;

class ProductSummaryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, Responsable
{
    use Exportable;

    public string $fileName;

    protected ?array $period;

    public function __construct(?array $period = null)
    {
        $this->period   = $period;                                  // [start, end] dalam format Y‑m‑d
        $this->fileName = 'rekap-produk-' . now()->format('Y-m-d') . '.xlsx';
    }

    public function query()
    {
        $q = $this->baseQuery();                                    // query builder yg sama dengan DataTable
        if ($this->period) {
            $q->whereBetween('order.created_at', $this->period);
        }
        return $q;
    }

    public function headings(): array
    {
        return [
            'Produk', 'Penjual', 'Harga', 'Stok', 'Total Order',
            'Unit Terjual', 'Omzet (Rp)', '% Terjual'
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->seller_name,
            $row->price,
            $row->global_stock
                ? "Gbl: $row->qty | Sold: $row->qty_sold | Booked: $row->qty_booked"
                : "Var: $row->variant_qty | Sold: $row->variant_qty_sold | Booked: $row->variant_qty_booked",
            $row->total_order,
            $row->unit_terjual,
            $row->omzet,
            number_format($row->percentage, 2) . '%',
        ];
    }

    // 👇 pindahkan kembarannya dari controller ke sini agar DRY
    protected function baseQuery()
    {
        return product_item::select(
                'product_item.id',
                'product_item.name',
                'seller.store_name as seller_name',
                'default_variant.price',
                'product_item.global_stock',
                'product_item.qty',
                'product_item.qty_booked',
                'product_item.qty_sold',
                DB::raw('SUM(coalesce(product_item_variant.qty,0)) as variant_qty'),
                DB::raw('SUM(coalesce(product_item_variant.qty_booked,0)) as variant_qty_booked'),
                DB::raw('SUM(coalesce(product_item_variant.qty_sold,0)) as variant_qty_sold'),
                DB::raw('COUNT(DISTINCT order.id)  as total_order'),
                DB::raw('SUM(coalesce(order_details.qty,0)) as unit_terjual'),
                DB::raw('SUM(coalesce(order_details.qty*order_details.price_per_item,0)) as omzet'),
                DB::raw('
                    IF(product_item.global_stock = 1,
                        (product_item.qty_sold+product_item.qty_booked) /
                        NULLIF((product_item.qty_sold+product_item.qty_booked+product_item.qty),0)*100,
                        (SUM(coalesce(product_item_variant.qty_sold,0))+SUM(coalesce(product_item_variant.qty_booked,0))) /
                        NULLIF((SUM(coalesce(product_item_variant.qty_sold,0))+SUM(coalesce(product_item_variant.qty_booked,0))+SUM(coalesce(product_item_variant.qty,0))),0)*100
                    ) as percentage'
                )
            )
            ->leftJoin('seller', 'product_item.seller_id', '=', 'seller.id')
            ->leftJoin('product_item_variant as default_variant', function ($j) {
                $j->on('product_item.id','=','default_variant.product_item_id')
                   ->where('default_variant.is_default',1)
                   ->whereNull('default_variant.deleted_at');
            })
            ->leftJoin('product_item_variant', function ($j) {
                $j->on('product_item.id','=','product_item_variant.product_item_id')
                   ->whereNull('product_item_variant.deleted_at');
            })
            ->leftJoin('order_details','order_details.product_id','=','product_item.id')
            ->leftJoin('order', function ($j) {
                $j->on('order.id','=','order_details.order_id')
                   ->whereNull('order.deleted_at');
            })
            ->where('product_item.published_status',1)
            ->where('product_item.approval_status',1)
            ->groupBy(
                'product_item.id','product_item.name','seller_name',
                'default_variant.price','product_item.global_stock',
                'product_item.qty','product_item.qty_booked','product_item.qty_sold'
            );
    }
}
