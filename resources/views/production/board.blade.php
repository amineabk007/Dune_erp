@extends('layouts.app')

@section('title', $title)

@section('content')
    @livewire('production-board', ['destination' => $destination])
@endsection
