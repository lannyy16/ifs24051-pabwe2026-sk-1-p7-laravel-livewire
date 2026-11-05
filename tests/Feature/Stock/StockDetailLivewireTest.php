<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->stock = Stock::factory()->withQuantity(10)->create([
            'user_id' => $this->user->id, 'description' => '# Judul', 'price' => null, 'sku' => null,
        ]);
    }

    private function lw()
    {
        return Livewire::actingAs($this->user)->test(StockDetail::class, ['stock' => $this->stock]);
    }

    public function test_renders_markdown(): void
    {
        $this->lw()->assertSee('Judul')->assertSeeHtml('<h1>Judul</h1>');
    }

    public function test_renders_without_description(): void
    {
        $this->stock->update(['description' => null]);

        $this->lw()->assertSee('Belum ada deskripsi.');
    }

    public function test_other_user_is_forbidden(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(StockDetail::class, ['stock' => $this->stock])->assertForbidden();
    }

    public function test_edit_save_and_cancel(): void
    {
        $this->lw()->call('startEdit')->assertSet('editing', true)->assertSee('Deskripsi (Markdown)')
            ->call('cancelEdit')->assertSet('editing', false)
            ->call('startEdit')->set('name', 'Diubah')->set('description', 'Teks baru')->set('price', '2500')->set('quantity', '2')
            ->call('save')->assertSet('editing', false);

        $this->stock->refresh();
        $this->assertSame('Diubah', $this->stock->name);
        $this->assertSame('low_stock', $this->stock->status->value);
    }

    public function test_save_validation(): void
    {
        $this->lw()->call('startEdit')->set('name', '')->call('save')->assertHasErrors('name');
    }

    public function test_adjust_quantity(): void
    {
        $this->lw()->call('adjust', -10)->assertSet('quantity', '0');
        $this->assertSame('out_of_stock', $this->stock->refresh()->status->value);
    }

    public function test_cover_upload_and_remove(): void
    {
        $component = $this->lw()->set('cover', UploadedFile::fake()->image('a.jpg'))->call('saveCover');
        $path = $this->stock->refresh()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $component->assertSee('Hapus cover')->call('removeCover');
        $this->assertNull($this->stock->refresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cover_must_be_image(): void
    {
        $this->lw()->set('cover', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))
            ->call('saveCover')->assertHasErrors('cover');
    }

    public function test_delete_redirects_to_list(): void
    {
        $this->lw()->call('delete')->assertRedirect(route('stocks.index'));
        $this->assertDatabaseMissing('stocks', ['id' => $this->stock->id]);
    }
}
