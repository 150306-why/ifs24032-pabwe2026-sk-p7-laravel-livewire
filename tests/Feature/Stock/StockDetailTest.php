<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->member = User::factory()->create();
        $this->stock = Stock::factory()->create([
            'user_id' => $this->member->id,
            'name' => 'Pensil 2B',
            'sku' => 'PN-2B',
            'description' => 'Pensil **kayu** berkualitas',
            'quantity' => 10,
            'min_stock' => 5,
            'price' => 2500,
        ]);
    }

    private function page(?Stock $stock = null, ?User $user = null)
    {
        return Livewire::actingAs($user ?? $this->member)->test(StockDetail::class, ['stock' => $stock ?? $this->stock]);
    }

    public function test_shows_stock_information_and_rendered_markdown(): void
    {
        $this->page()
            ->assertSee('Pensil 2B')
            ->assertSee('PN-2B')
            ->assertSee('Rp 2.500')
            ->assertSee('<strong>kayu</strong>', false)
            ->assertSee('Belum ada cover');
    }

    public function test_empty_description_and_missing_price(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'description' => null, 'price' => null]);

        $this->page($stock)->assertSee('Belum ada deskripsi.');
    }

    public function test_markdown_is_sanitized(): void
    {
        $this->stock->update(['description' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

        $this->page($this->stock->fresh())
            ->assertDontSee('<script>', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_other_member_and_admin_cannot_open_detail(): void
    {
        $this->page($this->stock, User::factory()->create())->assertForbidden();
        $this->page($this->stock, User::factory()->admin()->create())->assertForbidden();
    }

    public function test_edit_stock(): void
    {
        $this->page()
            ->call('openEdit')
            ->assertSet('showEdit', true)
            ->assertSet('form.name', 'Pensil 2B')
            ->set('form.name', 'Pensil 3B')
            ->set('form.quantity', 3)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showEdit', false)
            ->assertSee('Pensil 3B');

        $fresh = $this->stock->fresh();
        $this->assertSame('Pensil 3B', $fresh->name);
        $this->assertSame(StockStatus::LOW_STOCK, $fresh->status);
    }

    public function test_edit_validation_and_cancel(): void
    {
        $this->page()
            ->call('openEdit')
            ->set('form.name', '')
            ->call('save')
            ->assertHasErrors(['form.name'])
            ->call('closeEdit')
            ->assertSet('showEdit', false);

        $this->assertSame('Pensil 2B', $this->stock->fresh()->name);
    }

    public function test_adjust_quantity_add_and_subtract(): void
    {
        $this->page()
            ->set('adjustAmount', 5)
            ->call('adjust', 1)
            ->assertSee('Quantity berhasil diperbarui.');
        $this->assertSame(15, $this->stock->fresh()->quantity);

        $this->page()->set('adjustAmount', 3)->call('adjust', -1);
        $this->assertSame(12, $this->stock->fresh()->quantity);
    }

    public function test_adjust_cannot_go_negative_or_use_invalid_amount(): void
    {
        $this->page()->set('adjustAmount', 11)->call('adjust', -1)->assertHasErrors(['adjustAmount']);
        $this->page()->set('adjustAmount', 0)->call('adjust', 1)->assertHasErrors(['adjustAmount']);
        $this->page()->set('adjustAmount', 'abc')->call('adjust', 1)->assertHasErrors(['adjustAmount']);

        $this->assertSame(10, $this->stock->fresh()->quantity);
    }

    public function test_upload_replace_and_remove_cover(): void
    {
        $this->page()
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->call('uploadCover')
            ->assertHasNoErrors()
            ->assertSee('Cover berhasil diunggah.');

        $path = $this->stock->fresh()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->page($this->stock->fresh())->call('removeCover')->assertSee('Cover berhasil dihapus.');

        $this->assertNull($this->stock->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cover_is_displayed_when_present(): void
    {
        $this->stock->update(['cover_image_path' => 'stock-covers/demo.jpg']);

        $this->page($this->stock->fresh())->assertSee('stock-covers/demo.jpg')->assertSee('Hapus cover');
    }

    public function test_cover_validation(): void
    {
        $this->page()->call('uploadCover')->assertHasErrors(['cover' => 'required']);

        $this->page()
            ->set('cover', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->call('uploadCover')
            ->assertHasErrors(['cover']);

        $this->page()
            ->set('cover', UploadedFile::fake()->image('besar.jpg')->size(5000))
            ->call('uploadCover')
            ->assertHasErrors(['cover']);
    }

    public function test_delete_redirects_to_list(): void
    {
        $this->page()
            ->call('confirmDelete')
            ->assertSet('showDelete', true)
            ->assertSee('Hapus barang?')
            ->call('cancelDelete')
            ->assertSet('showDelete', false)
            ->call('confirmDelete')
            ->call('delete')
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $this->stock->id]);
    }
}
