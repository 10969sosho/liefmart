@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Edit Kategori Produk</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('product-categories.update', $productCategory->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-control-label">Nama Kategori Produk <span class="text-danger">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" type="text" 
                                        id="name" name="name" value="{{ old('name', $productCategory->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sub_brand_id" class="form-control-label">Sub Brand <span class="text-danger">*</span></label>
                                    <select class="form-control @error('sub_brand_id') is-invalid @enderror" 
                                        id="sub_brand_id" name="sub_brand_id" required>
                                        <option value="">Pilih Sub Brand</option>
                                        @foreach($subBrands as $subBrand)
                                            <option value="{{ $subBrand->id }}" {{ old('sub_brand_id', $productCategory->sub_brand_id) == $subBrand->id ? 'selected' : '' }}>
                                                {{ $subBrand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sub_brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-control-label">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" 
                                name="description" rows="3">{{ old('description', $productCategory->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                {{ old('is_active', $productCategory->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('product-categories.index') }}" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 