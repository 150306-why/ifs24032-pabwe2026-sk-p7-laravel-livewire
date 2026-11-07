@php($inputClass = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200')
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="form-name" class="block text-sm font-medium">Nama Barang</label>
        <input id="form-name" type="text" wire:model="form.name" class="{{ $inputClass }}">
        @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-sku" class="block text-sm font-medium">SKU</label>
        <input id="form-sku" type="text" wire:model="form.sku" class="{{ $inputClass }}">
        @error('form.sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-unit" class="block text-sm font-medium">Unit</label>
        <input id="form-unit" type="text" wire:model="form.unit" class="{{ $inputClass }}">
        @error('form.unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-quantity" class="block text-sm font-medium">Quantity</label>
        <input id="form-quantity" type="number" min="0" wire:model.blur="form.quantity" class="{{ $inputClass }}">
        @error('form.quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-min-stock" class="block text-sm font-medium">Minimum Stock</label>
        <input id="form-min-stock" type="number" min="0" wire:model.blur="form.min_stock" class="{{ $inputClass }}">
        @error('form.min_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-price" class="block text-sm font-medium">Harga (Rp)</label>
        <input id="form-price" type="number" min="0" step="0.01" wire:model="form.price" class="{{ $inputClass }}">
        @error('form.price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="form-status" class="block text-sm font-medium">Status (otomatis)</label>
        <select id="form-status" disabled class="{{ $inputClass }} bg-slate-100 text-slate-600">
            <option>{{ $form->previewStatus()->label() }}</option>
        </select>
        <p class="mt-1 text-xs text-slate-600">Dihitung dari quantity dan minimum stock.</p>
    </div>
    <div class="sm:col-span-2">
        <span id="form-description-label" class="block text-sm font-medium">Deskripsi (Markdown)</span>
        <div x-data="{ failed: false }">
            <div wire:ignore class="mt-1" role="group" aria-labelledby="form-description-label" x-show="!failed"
                 x-init="
                    window.loadToastEditor().then((Editor) => {
                        const editor = new Editor({
                            el: $refs.editor,
                            height: '260px',
                            initialEditType: 'markdown',
                            previewStyle: 'tab',
                            usageStatistics: false,
                            initialValue: $wire.get('form.description') ?? '',
                        });
                        editor.on('change', () => $wire.set('form.description', editor.getMarkdown(), false));
                    }).catch(() => { failed = true; });
                 ">
                <div x-ref="editor"></div>
            </div>
            <textarea x-show="failed" style="display: none" id="form-description-fallback" rows="8"
                      aria-label="Deskripsi (Markdown)" wire:model="form.description"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
        </div>
        @error('form.description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
