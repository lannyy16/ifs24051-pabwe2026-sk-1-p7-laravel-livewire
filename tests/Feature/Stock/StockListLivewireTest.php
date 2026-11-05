<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    private function lw()
    {
        return Livewire::actingAs($this->user)->test(StockList::class);
    }

    public function test_renders_only_own_stocks(): void
    {
        Stock::factory()->create(['user_id' => $this->user->id, 'name' => 'Milik Saya']);
        Stock::factory()->create(['name' => 'Milik Orang']);

        $this->lw()->assertSee('Milik Saya')->assertDontSee('Milik Orang');
    }

    public function test_search_and_status_filter(): void
    {
        Stock::factory()->withQuantity(50)->create(['user_id' => $this->user->id, 'name' => 'Apel']);
        Stock::factory()->withQuantity(0)->create(['user_id' => $this->user->id, 'name' => 'Jeruk']);

        $this->lw()->set('search', 'Apel')->assertSee('Apel')->assertDontSee('Jeruk')
            ->set('search', '')->set('status', 'out_of_stock')->assertSee('Jeruk')->assertDontSee('Apel');
    }

    public function test_create_stock_with_cover_and_auto_status(): void
    {
        $this->lw()->call('create')->assertSet('showForm', true)
            ->set('name', 'Pulpen')->set('sku', 'PLP-1')->set('quantity', '3')->set('min_stock', '5')
            ->set('unit', 'pak')->set('price', '12000')->set('description', '**bagus**')
            ->set('cover', UploadedFile::fake()->image('c.jpg'))
            ->call('save')->assertHasNoErrors()->assertSet('showForm', false);

        $stock = Stock::firstWhere('name', 'Pulpen');
        $this->assertSame($this->user->id, $stock->user_id);
        $this->assertSame('low_stock', $stock->status->value);
        Storage::disk('public')->assertExists($stock->cover_image_path);
    }

    public function test_create_validation(): void
    {
        $this->lw()->call('create')->set('name', '')->set('quantity', '-1')->call('save')
            ->assertHasErrors(['name', 'quantity']);
    }

    public function test_edit_and_update_stock(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->user->id, 'price' => null, 'sku' => null, 'description' => null]);

        $this->lw()->call('edit', $stock->id)->assertSet('editingId', $stock->id)->assertSet('price', '')
            ->set('name', 'Baru')->set('quantity', '0')
            ->set('cover', UploadedFile::fake()->image('x.jpg'))
            ->call('save')->assertHasNoErrors();

        $stock->refresh();
        $this->assertSame('Baru', $stock->name);
        $this->assertSame('out_of_stock', $stock->status->value);
        $this->assertNotNull($stock->cover_image_path);
    }

    public function test_edit_loads_existing_price(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->user->id, 'price' => 1500]);

        $this->lw()->call('edit', $stock->id)->assertSet('price', '1500.00');
    }

    public function test_cannot_edit_delete_or_adjust_other_users_stock(): void
    {
        $other = Stock::factory()->create();

        $this->lw()->call('edit', $other->id)->assertForbidden();
        $this->lw()->call('delete', $other->id)->assertForbidden();
        $this->lw()->call('adjust', $other->id, 1)->assertForbidden();
        $this->lw()->call('create')->set('editingId', $other->id)->set('name', 'X')->call('save')->assertForbidden();
        $this->assertDatabaseHas('stocks', ['id' => $other->id]);
    }

    public function test_delete_stock_removes_cover(): void
    {
        Storage::disk('public')->put('covers/a.jpg', 'x');
        $stock = Stock::factory()->create(['user_id' => $this->user->id, 'cover_image_path' => 'covers/a.jpg']);

        $this->lw()->call('delete', $stock->id);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing('covers/a.jpg');
    }

    public function test_adjust_quantity_syncs_status(): void
    {
        $stock = Stock::factory()->withQuantity(6, 5)->create(['user_id' => $this->user->id]);

        $this->lw()->call('adjust', $stock->id, -1);
        $this->assertSame('low_stock', $stock->refresh()->status->value);

        $this->lw()->call('adjust', $stock->id, -100);
        $this->assertSame(0, $stock->refresh()->quantity);
        $this->assertSame('out_of_stock', $stock->status->value);

        $this->lw()->call('adjust', $stock->id, 50);
        $this->assertSame('available', $stock->refresh()->status->value);
    }

    public function test_close_form(): void
    {
        $this->lw()->call('create')->call('closeForm')->assertSet('showForm', false);
    }

    public function test_export_and_template_download(): void
    {
        Stock::factory()->create(['user_id' => $this->user->id]);

        $this->lw()->call('export')->assertFileDownloaded('stok-barang.xlsx');
        $this->lw()->call('downloadTemplate')->assertFileDownloaded('template-stok.xlsx');
    }

    public function test_import_excel(): void
    {
        $content = (new StockExcelService)->template();
        $file = UploadedFile::fake()->createWithContent('stok.xlsx', $content);

        $this->lw()->set('importFile', $file)->call('import')->assertHasNoErrors();

        $this->assertDatabaseHas('stocks', ['user_id' => $this->user->id, 'name' => 'Contoh Barang']);
    }

    public function test_import_rejects_wrong_type_and_unreadable_file(): void
    {
        $this->lw()->set('importFile', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))
            ->call('import')->assertHasErrors('importFile');

        $this->lw()->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel'))
            ->call('import')->assertHasErrors('importFile');
    }

    public function test_ai_generate_select_and_save(): void
    {
        $this->app->instance(AiClientInterface::class, $this->aiStub([
            new GeneratedStockItem('Kopi', 'KP-1', 'Enak', 10, 'kg', 50000.0, 3),
            new GeneratedStockItem('Teh'),
        ]));

        $this->lw()->call('openAi')->assertSet('showAi', true)
            ->set('aiPrompt', 'warung kopi')->call('generateAi')
            ->assertSet('aiSelected', [0, 1])
            ->set('aiSelected', ['1'])
            ->call('saveAi')->assertSet('showAi', false);

        $this->assertDatabaseHas('stocks', ['user_id' => $this->user->id, 'name' => 'Teh']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Kopi']);
    }

    public function test_ai_error_and_empty_selection(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willThrowException(new RuntimeException('AI gagal'));
        $this->app->instance(AiClientInterface::class, $stub);

        $this->lw()->call('openAi')->set('aiPrompt', 'abc')->call('generateAi')
            ->assertSet('aiError', 'AI gagal')->assertSet('aiItems', [])
            ->call('saveAi')->assertSet('aiError', 'Pilih minimal satu item untuk disimpan.');

        $this->lw()->call('openAi')->call('generateAi')->assertHasErrors('aiPrompt');
        $this->lw()->call('openAi')->call('closeAi')->assertSet('showAi', false);
    }

    /** @param array<int, GeneratedStockItem> $items */
    private function aiStub(array $items): AiClientInterface
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willReturn($items);

        return $stub;
    }
}
