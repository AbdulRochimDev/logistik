@extends('layouts.base', ['title' => 'Edit Item', 'mainClass' => 'layout-wide'])

@section('content')
<div style="width:min(800px,100%);margin:0 auto;display:grid;gap:1rem;">
    <h1 style="margin-bottom:1.5rem;font-size:1.9rem;">Edit Item</h1>
    @include('admin.items._form')
</div>
@endsection
