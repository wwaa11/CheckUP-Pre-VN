@extends('layouts.app')
@section('content')
<div class="m-auto w-full md:w-3/4 p-3">
    <div class="p-3 shadow-lg m-3">
        <div class="text-blue-600 font-bold text-xl">@if(session("langSelect")=="TH") รายละเอียด @else Information @endif</div>
        <div class="">{{$hnData->Fullname}}</div>
        <div class="">{{$hnData->Data}}</div>
    </div>
    <div class="p-3 shadow-lg m-3">
        <div class="text-blue-600 font-bold text-xl">@if(session("langSelect")=="TH") นัดของฉัน @else My Appointment @endif </div>
        @foreach ($myApp as $item)
        <div class="grid rounded-lg grid-cols-2 bg-gray-100 m-3 p-3">
            <div class="col-6 col-md-4">@if(session("langSelect")=="TH") หมาายเลขนัด @else Appointment No @endif : </div>
            <div class="col-6 col-md-8">{{$item->AppointmentNo}}</div>
            <div class="col-6 col-md-4">@if(session("langSelect")=="TH") วันนัด @else Appointment Date @endif : </div>
            <div class="col-6 col-md-8">{{ date('d M Y', $item->AppStrTime) }}</div>
            <div class="col-6 col-md-4">@if(session("langSelect")=="TH") เวลานัด @else Appointment Time  @endif : </div>
            <div class="col-6 col-md-8">{{ date('H:i', $item->AppStrTime) }}</div>
            @if(session("langSelect")=="TH")
            <div class="col-6 col-md-4">คลินิค : </div>
            <div class="col-6 col-md-8">{{ $item->ClinicTH }}</div>
            <div class="col-6 col-md-4">แพทย์ : </div>
            <div class="col-6 col-md-8">{{ $item->DocTH }}</div>
            @else
            <div class="col-6 col-md-4">Clinic : </div>
            <div class="col-6 col-md-8">{{ $item->ClinicEN }}</div>
            <div class="col-6 col-md-4">Doctor : </div>
            <div class="col-6 col-md-8">{{ $item->DocEN }}</div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
@section('scripts')
@endsection