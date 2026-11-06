<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnexpectedValueException;

class StockExcelService
{
    public const HEADERS = [
        'name',
        'sku',
        'quantity',
        'unit',
        'price',
        'min_stock',
        'status',
        'description',
    ];

    /** @param Collection<int, Stock> $stocks */
    public function export(Collection $stocks): string
    {
        $rows = $stocks->map(fn (Stock $stock) => [
            $stock->name,
            $stock->sku,
            $stock->quantity,
            $stock->unit,
            $stock->price,
            $stock->min_stock,
            $stock->status->value,
            $stock->description,
        ])->all();

        return $this->build($rows);
    }

    public function template(): string
    {
        return $this->build([
            [
                'Contoh Barang',
                'SKU-001',
                10,
                'pcs',
                15000,
                5,
                'available',
                'Deskripsi opsional',
            ],
        ]);
    }

    private function assertExcelFile(string $path): void
    {
        $file = fopen($path, 'rb');

        if ($file === false) {
            throw new UnexpectedValueException(
                'File Excel tidak dapat dibuka.'
            );
        }

        try {
            $signature = fread($file, 8);
        } finally {
            fclose($file);
        }

        $isXlsx = is_string($signature)
            && str_starts_with($signature, "PK\x03\x04");

        $isXls = $signature === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

        if (! $isXlsx && ! $isXls) {
            throw new UnexpectedValueException(
                'Format file Excel tidak valid.'
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function import(string $path): array
    {
        $this->assertExcelFile($path);

        $spreadsheet = IOFactory::load($path);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, false);

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                array_shift($data) ?? []
            );

            $header = array_map(
                fn ($value) => $value === 'qty' ? 'quantity' : $value,
                $header
            );

            $rows = [];

            foreach ($data as $line) {
                $row = [];

                foreach ($header as $index => $key) {
                    $row[$key] = $line[$index] ?? null;
                }

                if (trim((string) ($row['name'] ?? '')) === '') {
                    continue;
                }

                $rows[] = [
                    'name' => (string) $row['name'],
                    'sku' => $row['sku'] ?? null,
                    'description' => $row['description'] ?? null,
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'unit' => $row['unit'] ?? null,
                    'price' => $row['price'] ?? null,
                    'min_stock' => (int) ($row['min_stock'] ?? 5),
                ];
            }

            return $rows;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function build(array $rows): string
    {
        $spreadsheet = new Spreadsheet();

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stok');
            $sheet->fromArray(self::HEADERS, null, 'A1');
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);

            if ($rows !== []) {
                $sheet->fromArray($rows, null, 'A2');
            }

            foreach (range('A', 'H') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'stk');

            if ($tmp === false) {
                throw new UnexpectedValueException(
                    'Gagal membuat file sementara.'
                );
            }

            try {
                (new Xlsx($spreadsheet))->save($tmp);

                $content = file_get_contents($tmp);

                if ($content === false) {
                    throw new UnexpectedValueException(
                        'Gagal membaca file Excel.'
                    );
                }

                return $content;
            } finally {
                if (file_exists($tmp)) {
                    unlink($tmp);
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }
}
