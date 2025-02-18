@extends('layouts.app')
@section('content')
    <style>
        .bg-praram9 {
            border-radius: 10px;
            color: #fff;
            background: rgb(55, 190, 175);
            background: linear-gradient(117deg, rgba(55, 190, 175, 1) 0%, rgba(11, 162, 171, 1) 91%);
        }
    </style>
    <div class="mb-3 mt-6 text-center text-2xl font-bold">
        @if (session('langSelect') == 'TH')
            <div>ศูนย์ตรวจสุขภาพ : อาคาร B ชั้น 12.</div>
        @else
            <div>Check UP Center : Building B floor 12.</div>
        @endif
    </div>
    <div class="flex p-3">
        <div class="w-full md:w-1/2 m-auto my-6 p-3 md:p-6 bg-praram9">
            <div class="grid grid-cols-2 p-3 ">
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        ชื่อ
                    @else
                        Name
                    @endif
                </div>
                <div class="mb-1">{{ $data->name }}</div>
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        HN
                    @else
                        HN
                    @endif
                </div>
                <div class="mb-1">{{ $data->hn }}</div>
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        หมาายเลขนัด
                    @else
                        Appointment No
                    @endif
                </div>
                <div class="mb-1">{{ $data->app }}</div>
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        เวลาที่กดรับคิว
                    @else
                        Check in time
                    @endif
                </div>
                <div class="mb-1 text-red-600 font-bold">{{ $data->add_time }}</div>
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        เวลาที่เรียกคิว
                    @else
                        Call time
                    @endif
                </div>
                <div class="mb-1 text-red-600 font-bold">{{ $data->call_time }}</div>
            </div>
            <div class="text-center p-6">
                <div class="">
                    @if (session('langSelect') == 'TH')
                        หมายเลขคิวของคุณ
                    @else
                        Number
                    @endif
                </div>
                <div class="text-yellow-300 text-6xl font-bold">
                    @if ($data->number == null)
                        @if (session('langSelect') == 'TH')
                            ระบบกำลังรับหมายเลขคิว
                        @else
                            Please, wait.
                        @endif
                    @else
                        {{ $data->number }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            if ('{{ $data->number }}' == '') {
                setTimeout(function() {
                    refresh()
                }, 3 * 1000);
            } else if ('{{ $data->call_time }}' == '') {
                setTimeout(function() {
                    refresh()
                }, 30 * 1000);
            } else {
                setTimeout(function() {
                    refresh()
                }, 10 * 60 * 1000);
            }
        });

        function refresh() {
            window.location.reload();
        }
    </script>
@endsection
