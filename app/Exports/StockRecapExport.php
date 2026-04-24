<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\DB;

use App\Models\product_item;

class StockRecapExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, Responsable
{
    use Exportable;

    public string $fileName;
    protected ?array $period;

    public function __construct(?array $period = null)
    {
        $this->period = $period;  // [start, end] as Y-m-d
        $this->fileName = 'rekap-stok-produk-' . now()->format('Y-m-d') . '.xlsx';
    }

    public function query()
    {
        $query = product_item::select(
            'product_item.name',
            'product_category.name as category',
            'seller.store_name as store_name',
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

        if ($this->period) {
            $query->whereBetween('product_item.created_at', $this->period);
        }

        return $query;
    }

    public function map($row): array
    {
        $total_qty = $row->global_stock == 1
        ? $row->global_qty
        : $row->variant_qty_total;

        $status = ($total_qty <= 0) ? 'Habis' : 'Aman';

        return [
            $status,
            $row->name,
            $row->category,
            $row->store_name,
            $row->global_stock,
            $row->global_qty,
            $row->global_qty_booked,
            $row->global_qty_sold,
            $row->variant_qty_total,
            $row->variant_qty_booked_total,
            $row->variant_qty_sold_total,
            $row->total_qty,
            $row->total_qty_booked,
            $row->total_qty_sold,
            $row->sku_variants
        ];
    }

    public function headings(): array
    {
        return [
            'Status',
            'Nama Produk',
            'Kategori',
            'Toko',
            'Global Stock',
            'Global Qty',
            'Global Booked',
            'Global Sold',
            'Variant Qty',
            'Variant Booked',
            'Variant Sold',
            'Total Qty',
            'Total Booked',
            'Total Sold',
            'SKU Variants'
        ];
    }
}