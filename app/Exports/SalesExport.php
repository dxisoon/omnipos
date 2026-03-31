<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    protected string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        return Sale::with('items')
            ->whereDate('created_at', $this->date)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Sale ID',
            'Date',
            'Items',
            'Subtotal (MYR)',
            'Discount (MYR)',
            'Tax (MYR)',
            'Total (MYR)',
            'Payment Method',
            'Status',
            'Receipt Token',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->created_at->format('Y-m-d H:i'),
            $sale->items->sum('quantity'),
            number_format($sale->subtotal, 2),
            number_format($sale->discount_amount, 2),
            number_format($sale->tax_amount, 2),
            number_format($sale->total_amount, 2),
            $sale->payment_method,
            $sale->payment_status,
            $sale->receipt_token,
        ];
    }
}