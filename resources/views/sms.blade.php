@extends('layouts.app')
@section('content')
    <style>
        .bg-praram9 {
            border-radius: 10px;
            color: #fff;
            background: #37beaf;
            background: linear-gradient(117deg, rgba(55, 190, 175, 1) 0%, rgba(11, 162, 171, 1) 91%);
        }
    </style>
    <div class="mb-3 mt-6 text-center text-2xl font-bold">
        <div>{{ $text->checkup }}</div>
    </div>
    <div class="flex p-3">
        <div class="m-auto mt-6 shadow-lg p-6 mb-6 w-full md:w-3/4 bg-praram9 text-center">
            <div class="mb-3">{{ $text->name }}</div>
            <div class="mb-3">{{ $hnDetail->name }} ( {{ $hnDetail->HN }} )</div>
            <div class="mb-3">{{ $text->app_no }}</div>
            <div class="mb-3">{{ $hnDetail->appNo }}</div>
            <div class="mb-3">{{ $text->app_date }}</div>
            <div class="mb-3">{{ $hnDetail->appDate }}</div>
            <div class="mb-3">{{ $text->app_time }}</div>
            <div class="mb-3">{{ $hnDetail->appTime }}</div>
            <div class="m-auto flex-grow mt-6" id="checkLo">
                <div class="text-center cursor-pointer p-3 font-bold rounded border-blue-600 text-yellow-300 mt-3 text-3xl">
                    {{ $text->range_check }}
                </div>
            </div>
            <div class="hidden m-auto flex-grow mt-6">
                <div class="text-center cursor-pointer p-3 mt-3">
                    <button type="button" class="rounded text-yellow-300 bg-[#37beaf] p-3 w-full md:w-1/2 shadow text-4xl font-bold">TEST</button>
                </div>
            </div>
            {{-- <div class="text-5xl">&nbsp;</div> --}}
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const sleep = (delay) => new Promise((resolve) => setTimeout(resolve, delay))
        Array.prototype.random = function() {
            return this[Math.floor((Math.random() * this.length))];
        }
        var lat = '-';
        var log = '-';

        $(document).ready(function() {
            navigator.geolocation.getCurrentPosition(success, error, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0,
            })

            setTimeout(function() {
                checkLocation()
            }, 1 * 500);
        });

        function success(pos) {
            const crd = pos.coords;
            lat = crd.latitude;
            log = crd.longitude;
        }

        function error(err) {
            Swal.fire({
                title: "Please allow location access.",
                text: "โปรดอนุญาตการเข้าถึงตำแหน่ง และ ปิดเปิดใหม่อีกครั้ง",
                icon: "error",
                allowOutsideClick: false,
                showConfirmButton: false,
                showCancelButton: false,
            });
        }

        async function checkLocation() {
            if (lat == '-' || log == '-') {
                setTimeout(function() {
                    checkLocation()
                }, 1 * 1000);
            } else {
                const formData = new FormData();
                formData.append('hn', '{{ $hnDetail->HN }}');
                formData.append('lat', lat);
                formData.append('log', log);
                const res = await axios.post("{{ env('APP_URL') }}/checkLocation", formData, {
                    "Content-Type": "multipart/form-data"
                }).then((res) => {
                    $('#checkLo').html(res.data.html)
                })
            }
        }

        async function selectItem(hn) {
            if ('{{ session('langSelect') }}' == "TH") {
                text = "กรุณารอสักครู่"
                err = "กรุณาลองอีกครั้ง"
            } else {
                text = "Please, wait."
                err = "Err.Try again."
            }

            $('#sleItem').hide();
            Swal.fire({
                title: text,
                icon: "warning",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });

            const formData = new FormData();
            formData.append('hn', hn);
            const res = await axios.post("{{ env('APP_URL') }}/sms/genQueue", formData, {
                "Content-Type": "multipart/form-data"
            }).then((res) => {
                if (res.status == 200) {
                    Swal.fire({
                        title: 'Success.',
                        icon: "success",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false
                    });
                    window.location.href = '{{ env('APP_URL') }}/sms/viewqueue/' + hn
                } else {
                    Swal.fire({
                        title: 'Error.',
                        icon: "error",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false
                    });
                }
            }).catch(function(error) {
                Swal.fire({
                    title: err,
                    icon: "error",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
            });
        }
    </script>
@endsection
