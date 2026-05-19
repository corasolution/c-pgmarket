<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ProductImportTemplate implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [
            ['Wireless Earbuds Pro', 'កាស Wireless Pro', 'Electronics', 'active',  'Premium earbuds 30hr battery.',    'EAR-BLK-001', 29.99, 'USD', 50, 'Color:Black'],
            ['Wireless Earbuds Pro', 'កាស Wireless Pro', 'Electronics', 'active',  'Premium earbuds 30hr battery.',    'EAR-WHT-001', 29.99, 'USD', 30, 'Color:White'],
            ['Khmer Silk Scarf',     'កន្សែងសូត្រ',       'Fashion',     'active',  'Handwoven traditional silk.',      'SILK-RED-001', 15.00, 'USD', 100, 'Color:Red'],
            ['Khmer Silk Scarf',     'កន្សែងសូត្រ',       'Fashion',     'draft',   'Handwoven traditional silk.',      'SILK-BLU-001', 15.00, 'USD', 80,  'Color:Blue'],
            ['Ceramic Rice Bowl',    '',                  'Home & Living','draft',  'Hand-painted ceramic bowl set.',   'BOWL-SET-001', 12.50, 'USD', 200, ''],
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['name_en', 'name_km', 'category', 'status', 'description_en', 'sku', 'price', 'currency', 'stock', 'options'];
    }

    public function styles(Worksheet $sheet): array
    {
        $required = ['A', 'C', 'F', 'G', 'I'];
        $optional = ['B', 'D', 'E', 'H', 'J'];

        foreach ($required as $col) {
            $sheet->getStyle("{$col}1")
                ->getFill()->setFillType('solid')
                ->getStartColor()->setARGB('FFFBBF24');

            $sheet->getStyle("{$col}1")
                ->getFont()->setBold(true);
        }

        foreach ($optional as $col) {
            $sheet->getStyle("{$col}1")
                ->getFill()->setFillType('solid')
                ->getStartColor()->setARGB('FFD1FAE5');

            $sheet->getStyle("{$col}1")
                ->getFont()->setBold(true);
        }

        $sheet->getStyle('A1:J1')
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $lastRow = count($this->array()) + 1;
        $sheet->getStyle("A2:J{$lastRow}")
            ->getAlignment()->setWrapText(true);

        return [];
    }

    public function title(): string
    {
        return 'Import Template';
    }
}
