@extends('errors.layout')

@section('title', 'Session expired')
@section('code', 'Error 419')
@section('heading', 'That page sat open too long')
@section('body')
    For your security we expire forms that have been idle for a while. Nothing you had saved is
    lost — go back, reload the page and submit it again.
@endsection

@section('secondary')
    <a href="{{ route('login') }}" class="btn btn-quiet">Sign in again</a>
@endsection
