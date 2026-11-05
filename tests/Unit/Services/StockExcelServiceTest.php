<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

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
        $stock = Stock::factory()->create(['name' => 'Beras', 'sku' => 'B1', 'quantity' => 8]);

        $path = $this->tmp((new StockExcelService)->export(collect([$stock])));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Beras', $rows[1][0]);
        $this->assertSame('B1', $rows[1][1]);
    }

    public function test_export_empty_collection(): void
    {
        $path = $this->tmp((new StockExcelService)->export(collect()));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
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
        $this->assertSame(['name' => 'Pensil', 'sku' => null, 'description' => null, 'quantity' => 4, 'unit' => null, 'price' => null, 'min_stock' => 5], $rows[0]);
    }

    public function test_import_empty_sheet(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        (new Xlsx(new Spreadsheet))->save($path);

        $this->assertSame([], (new StockExcelService)->import($path));
        unlink($path);
    }
}
