<form action="{{ $action }}" method="POST" class="form-stack">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <label class="field">
        <span>Nama kategori</span>
        <input type="text" name="name" value="{{ old('name', $category->name) }}" maxlength="100" required>
    </label>
    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="120" required>
    </label>
    <label class="field">
        <span>Deskripsi <small>(opsional)</small></span>
        <textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea>
    </label>
    <label class="checkbox-field">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $category->exists ? $category->is_active : true))>
        <span>Kategori aktif dan ditampilkan</span>
    </label>
    <div class="form-actions">
        <button class="button button-primary" type="submit">Simpan kategori</button>
        <a class="button button-muted" href="{{ route('manage.categories.index') }}">Batal</a>
    </div>
</form>
