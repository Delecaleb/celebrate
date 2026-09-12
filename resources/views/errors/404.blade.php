@extends('errors.layout')

@section('title', 'Page not found')
@section('code', 'Error 404')
@section('heading', "That page isn't here")
@section('body')
    The link may be mistyped, or the celebration it pointed to has been taken down by whoever
    created it. Nothing has gone wrong on your side.
@endsection

@section('secondary')
    <a href="{{ route('stories') }}" class="btn btn-quiet">Browse celebrations</a>
@endsection
