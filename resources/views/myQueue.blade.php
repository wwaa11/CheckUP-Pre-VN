@extends('layouts.app')
@section('content')
    <div class="flex">
        <div class="w-full md:w-1/2 m-auto my-6">
            <div class="grid grid-cols-2 p-3 shadow-lg">
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
                <div class="mb-1 text-blue-600 font-bold">{{ $data->add_time }}</div>
                <div class="mb-1">
                    @if (session('langSelect') == 'TH')
                        เวลาที่เรียกคิว
                    @else
                        Call time
                    @endif
                </div>
                <div class="mb-1 text-blue-600 font-bold">{{ $data->call_time }}</div>
            </div>
            <div class="text-center mt-5 shadow-lg p-6">
                <div class="">
                    @if (session('langSelect') == 'TH')
                        หมายเลขคิวของคุณ
                    @else
                        Number
                    @endif
                </div>
                <div class="text-green-600 text-6xl font-bold">
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
