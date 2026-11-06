<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use UnexpectedValueException;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tmp(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_export_has_headers_and_rows(): void
    {
        $stock = Stock::factory()->create([
            'name' => 'Beras',
            'sku' => 'B1',
            'quantity' => 8,
        ]);

        $path = $this->tmp(
            (new StockExcelService)->export(collect([$stock]))
        );

        $rows = IOFactory::load($path)
            ->getActiveSheet()
            ->toArray();

        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Beras', $rows[1][0]);
        $this->assertSame('B1', $rows[1][1]);
    }

    public function test_export_empty_collection(): void
    {
        $path = $this->tmp(
            (new StockExcelService)->export(collect())
        );

        $rows = IOFactory::load($path)
            ->getActiveSheet()
            ->toArray();

        unlink($path);

        $this->assertCount(1, $rows);
    }

    public function test_template_round_trips_through_import(): void
    {
        $service = new StockExcelService;

        $path = $this->tmp($service->template());

        $rows = $service->import($path);

        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame('Contoh Barang', $rows[0]['name']);
        $this->assertSame(10, $rows[0]['quantity']);
    }

    public function test_import_accepts_qty_alias_skips_blank_names_and_defaults(): void
    {
        $sheet = new Spreadsheet;

        $sheet->getActiveSheet()->fromArray([
            ['Name', 'QTY'],
            ['Pensil', 4],
            ['', 9],
            [null, null],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'xl');

        (new Xlsx($sheet))->save($path);

        $rows = (new StockExcelService)->import($path);

        unlink($path);

        $this->assertCount(1, $rows);

        $this->assertSame([
            'name' => 'Pensil',
            'sku' => null,
            'description' => null,
            'quantity' => 4,
            'unit' => null,
            'price' => null,
            'min_stock' => 5,
        ], $rows[0]);
    }

    public function test_import_empty_sheet(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');

        (new Xlsx(new Spreadsheet))->save($path);

        $this->assertSame(
            [],
            (new StockExcelService)->import($path)
        );

        unlink($path);
    }

    public function test_import_rejects_invalid_excel_format(): void
    {
        $path = $this->tmp('This is not an Excel file');

        try {
            $this->expectException(UnexpectedValueException::class);
            $this->expectExceptionMessage(
                'Format file Excel tidak valid.'
            );

            (new StockExcelService)->import($path);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function test_import_rejects_missing_file(): void
    {
        $path = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'missing-excel-'
            . uniqid()
            . '.xlsx';

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'File Excel tidak dapat dibuka.'
        );

        (new StockExcelService)->import($path);
    }

    public function test_template_throws_when_temporary_file_cannot_be_created(): void
    {
        $service = new class extends StockExcelService {
            protected function createTemporaryFile(): string|false
            {
                return false;
            }
        };

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Gagal membuat file sementara.'
        );

        $service->template();
    }

    public function test_template_throws_when_temporary_file_cannot_be_read(): void
    {
        $service = new class extends StockExcelService {
            protected function readTemporaryFile(string $path): string|false
            {
                return false;
            }
        };

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Gagal membaca file Excel.'
        );

        $service->template();
    }
}
