<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="form-stack">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if (auth()->user()->role === 'admin')
        <label class="field">
            <span>Kantin</span>
            <select name="merchant_id" required>
                <option value="">Pilih kantin</option>
                @foreach ($merchantProfiles as $merchantProfile)
                    <option value="{{ $merchantProfile->id }}" @selected((string) old('merchant_id', $product->merchant_id) === (string) $merchantProfile->id)>
                        {{ $merchantProfile->stall_name }}
                    </option>
                @endforeach
            </select>
        </label>
    @endif
    <label class="field">
        <span>Nama menu</span>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" maxlength="150" required>
    </label>
    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" maxlength="180" required>
    </label>
    <label class="field">
        <span>Kategori</span>
        <select name="category_id" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                    {{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}
                </option>
            @endforeach
        </select>
    </label>
    <label class="field">
        <span>Deskripsi <small>(opsional)</small></span>
        <textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea>
    </label>
    <div class="form-row">
        <label class="field">
            <span>Harga (Rp)</span>
            <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0" max="9999999999.99" step="0.01" required>
        </label>
    </div>
    <div class="field">
        <span>Foto menu <small>(JPG, PNG, atau WebP, maksimal 2 MB)</small></span>
        @if ($product->image)
            <img class="product-image-preview" src="{{ Storage::disk('public')->url($product->image) }}" alt="Foto {{ $product->name }}">
            <label class="checkbox-field"><input type="checkbox" name="remove_image" value="1"><span>Hapus foto saat ini</span></label>
        @endif
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </div>
    <label class="checkbox-field">
        <input type="checkbox" name="is_available" value="1" @checked((bool) old('is_available', $product->exists ? $product->is_available : true))>
        <span>Menu tersedia dan tampil di katalog</span>
    </label>
    <div class="form-actions">
        <button class="button button-primary" type="submit">Simpan menu</button>
        <a class="button button-muted" href="{{ route($routePrefix.'.index') }}">Batal</a>
    </div>
</form>
