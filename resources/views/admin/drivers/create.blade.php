@extends('layouts.base', ['title' => 'Tambah Driver', 'mainClass' => 'layout-wide'])

@section('content')
<div style="width:min(800px,100%);margin:0 auto;display:grid;gap:1rem;">
    <h1 style="font-size:1.9rem;margin-bottom:0;">Tambah Driver</h1>
    @include('admin.drivers._form')
</div>
@endsection
