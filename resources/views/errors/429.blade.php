@extends('errors.layout')

@section('title', 'Too many attempts')
@section('code', 'Error 429')
@section('heading', 'Slow down a moment')
@section('body')
    We have had a lot of requests from you in a short time, so we are pausing for a minute. Wait
    a little and try again — this protects everyone's accounts, including yours.
@endsection
