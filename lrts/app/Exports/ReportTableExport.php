<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportTableExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    public function __construct(private readonly array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '12284C']]],
            2 => ['font' => ['italic' => true, 'color' => ['rgb' => '596579']]],
            3 => ['font' => ['italic' => true, 'color' => ['rgb' => '596579']]],
            5 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '12284C']],
                'alignment' => ['wrapText' => true, 'vertical' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = count($this->rows);
                if ($lastRow >= 7) {
                    $event->sheet->getDelegate()->getStyle('A' . $lastRow . ':' . $event->sheet->getDelegate()->getHighestColumn() . $lastRow)
                        ->getFont()->setBold(true);
                }
                $event->sheet->getDelegate()->getRowDimension(5)->setRowHeight(32);
                $event->sheet->getDelegate()->freezePane('A6');
            },
        ];
    }
}
