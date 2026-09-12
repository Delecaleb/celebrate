@extends('errors.layout')

@section('title', 'Not allowed')
@section('code', 'Error 403')
@section('heading', "You can't open this one")
@section('body')
    This page belongs to someone else, or it is set to private. If you think you should have
    access, ask the person who created it to share the link with you again.
@endsection
