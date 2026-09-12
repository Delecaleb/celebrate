@extends('errors.layout')

@section('title', 'Something broke')
@section('code', 'Error 500')
@section('heading', 'Something broke on our side')
@section('body')
    This one is ours, not yours. It has been logged and we will look at it. If you were in the
    middle of sending or withdrawing money, nothing was taken — check your wallet before trying
    again.
@endsection

@section('reference')
    Contact <a href="mailto:{{ config('seo.legal.email') }}">{{ config('seo.legal.email') }}</a>
    @if (request()->header('X-Request-Id'))
        and quote <code>{{ request()->header('X-Request-Id') }}</code>
    @endif
@endsection
