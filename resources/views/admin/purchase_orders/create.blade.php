@extends('layouts.base', ['title' => 'Buat Purchase Order', 'mainClass' => 'layout-wide'])

@section('content')
<div style="width:min(1100px,100%);margin:0 auto;display:grid;gap:1rem;">
    <h1 style="margin-bottom:1.5rem;font-size:1.9rem;">Buat Purchase Order</h1>
    @include('admin.purchase_orders._form')
</div>
@endsection
