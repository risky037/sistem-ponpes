@extends('errors.layout')

@section('title', 'Akses Ditolak (403)')
@section('code', '403')
@section('heading', 'Akses Terbatas / Ditolak')
@section('message', $exception?->getMessage() ?: 'Anda tidak memiliki hak akses atau izin yang memadai untuk membuka halaman atau melakukan tindakan ini.')
