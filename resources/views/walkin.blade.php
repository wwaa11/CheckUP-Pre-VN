@extends('layouts.app')
@section('content')
    <div class="w-full md:w-2/4 m-auto">
        <div class="py-3 text-center" id="searchSection">
            <div style="color: #4db1ab" class="mb-1 text-center pb-3 text-3xl">
                @if (session('langSelect') == 'TH')
                    ตรวจสอบข้อมูล
                @else
                    Check information
                @endif
            </div>
            <img class="m-auto" width="150" src="{{ asset('images/check2.jpg') }}">
            <div><i class="fa-regular fa-address-card"></i>
                @if (session('langSelect') == 'TH')
                    หมายเลขบัตรประชาชน
                @else
                    Thai ID Card
                @endif
            </div>
            <div>Passport</div>
            <div><i class="fa-solid fa-phone"></i>
                @if (session('langSelect') == 'TH')
                    เบอร์โทรศัพท์
                @else
                    Mobile Phone
                @endif
            </div>
            <input type="text" id="inputSearch"
                placeholder="@if (session('langSelect') == 'TH') หมายเลขบัตรประชาชน, เบอร์โทรศัพท์ @else Thai ID Card, Mobile Number @endif"
                autocomplete="off"
                class="text-center bg-green-200 rounded w-3/4 p-3 m-3 border-2 border-blue-600 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
            <div id="btnOTPCheck" onclick="otpCheck()"
                class="w-3/4 cursor-pointer p-3 m-auto text-center rounded border-2 text-green-600 border-green-600">
                <i class="fa-solid fa-magnifying-glass"></i>
                @if (session('langSelect') == 'TH')
                    ค้นหา
                @else
                    Search
                @endif
            </div>
            <div id="btnOTPChecking" class="hidden w-3/4 p-3 m-auto text-center rounded bg-gray-200">
                @if (session('langSelect') == 'TH')
                    กำลังค้นหา
                @else
                    Searching...
                @endif
            </div>
        </div>
        <div class="hidden py-3 text-center" id="otpSection">
            <div style="font-size: 2rem; color: #4db1ab" class="mb-1 text-center pt-3 pb-3">
                @if (session('langSelect') == 'TH')
                    กรุณายืนยันตัวตน
                @else
                    Please verify your identity.
                @endif
            </div>
            <img class="m-auto" width="150" src="{{ asset('images/check.jpg') }}">
            <div class="text-center mb-1">
                <div>
                    @if (session('langSelect') == 'TH')
                        รหัส OTP ได้ส่งไปยังหมายเลขโทรศัพท์
                    @else
                        A OTP has been send to
                    @endif
                </div>
                <div>xx-xxxx-<span id="OTP_Phone"></span></div>
                <div class="">
                    <div class="mb-3">
                        @if (session('langSelect') == 'TH')
                            ยืนยันการส่งไปที่เบอร์
                        @else
                            Send OTP to number
                        @endif
                    </div>
                    <div id="resend_otp"
                        class="m-auto p-3 w-3/4 border-2 text-blue-600 border-blue-600 rounded cursor-pointer"
                        onclick="sendOTP()">
                        @if (session('langSelect') == 'TH')
                            ขอรับ OTP
                        @else
                            Request OTP
                        @endif
                    </div>
                    <div id="resend_otp_dis" class="hidden m-auto p-3 w-3/4 bg-gray-200 rounded">
                        @if (session('langSelect') == 'TH')
                            กรุณารอสักครู่
                        @else
                            Please wait...
                        @endif
                    </div>
                </div>
            </div>
            <input type="hidden" id="refID" value="-">
            <input
                class="mt-3 text-center bg-green-200 mb-3 m-auto w-3/4 placeholder:text-red-600 text-red-600 p-3 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500"
                type="text" pattern="\d*" maxlength="6" id="inputOpt" placeholder="OTP" autocomplete="one-time-code">
            <div class="mt-3">
                <div id="otp_resultcheck" onclick="resultCheck()"
                    class="m-auto p-3 w-3/4 border-2 text-green-600 border-green-600 rounded cursor-pointer">
                    @if (session('langSelect') == 'TH')
                        ยืนยัน
                    @else
                        Verify
                    @endif
                </div>
                <div id="otp_resultchecking" class="hidden m-auto p-3 w-3/4 bg-gray-200 rounded">
                    @if (session('langSelect') == 'TH')
                        กำลังตรวจสอบ
                    @else
                        Verifying..
                    @endif
                </div>
            </div>
            <div class="mt-3" id="cantOTP">
                <div onclick="selectItem('-','OTP')"
                    class="m-auto p-3 w-3/4 text-red-400 rounded cursor-pointer font-bold underline underline-offset-1">
                    @if (session('langSelect') == 'TH')
                        หมายเลขรับ OTP ไม่ถูกต้อง
                    @else
                        Phone number not correct.
                    @endif
                </div>
            </div>
        </div>
        <div class="hidden" id="resultSection">
            <div class="grid grid-cols-2 p-3 m-auto">
                <div onclick="searchAgain()" class="p-3 text-red-400 font-bold cursor-pointer">
                    <i class="fa-solid fa-angle-left"></i>
                    @if (session('langSelect') == 'TH')
                        ค้นหาอีกครั้ง
                    @else
                        Search Again
                    @endif
                </div>
                <div class="text-center p-3 font-bold">
                    <span class="">
                        @if (session('langSelect') == 'TH')
                            ผลการค้นหา
                        @else
                            Result
                        @endif
                    </span> : <span id="searchInput" class="text-blue-600"></span>
                </div>
            </div>
            <div id="searchResult"></div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        var myhn = null
        var mytype = null
        var lat = '-';
        var log = '-';

        $(document).ready(function() {
            navigator.geolocation.getCurrentPosition(success, error, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0,
            })
        });

        function success(pos) {
            const crd = pos.coords;
            lat = crd.latitude;
            log = crd.longitude;
        }

        function error(err) {
            Swal.fire({
                title: "Please allow location access and refresh page.",
                text: "โปรดอนุญาติการเข้าถึงตำแหน่ง และ ปิดเปิดใหม่อีกครั้ง",
                icon: "error",
                allowOutsideClick: false,
                showConfirmButton: false,
                showCancelButton: false,
            });
        }

        function searchAgain() {
            $('#inputSearch').val('');
            $('#inputOpt').val('');
            $('#btnOTPCheck').show();
            $('#btnOTPChecking').hide();
            $('#searchSection').show();
            $('#otpSection').hide();
            $('#resultSection').hide();
            $('#otp_resultcheck').show();
            $('#otp_resultchecking').hide();

        }

        async function sendOTP() {
            $('#inputOpt').val('');
            $('#resend_otp').hide();
            $('#resend_otp_dis').show();

            if ('{{ session('langSelect') }}' == "TH") {
                otp_resend = "ระบบได้ทำการส่ง OTP ไปยังหมายเลขที่ระบุแล้ว"
                confirm = "ตกลง"
            } else {
                otp_resend = "OTP Code has been sent."
                confirm = "Confirm"
            }
            var refID = $('#refID').val();

            setTimeout(function() {
                $('#resend_otp').show();
                $('#resend_otp_dis').hide();
            }, 15 * 1000);
            const formData = new FormData();
            formData.append('ref_id', refID);
            const res = await axios.post("{{ env('APP_URL') }}/walkin/sendotp", formData, {
                "Content-Type": "multipart/form-data"
            }).then((res) => {
                $('#inputOpt').attr("placeholder", 'Ref : ' + res.data.ref);
            })

            Swal.fire({
                title: otp_resend,
                icon: "info",
                confirmButtonText: confirm,
                confirmButtonColor: '#4db1ab',
            });
        }

        async function otpCheck() {
            $('#btnOTPCheck').toggle();
            $('#btnOTPChecking').toggle();
            var input = $('#inputSearch').val();
            if (input == '') {
                $('#btnOTPCheck').toggle();
                $('#btnOTPChecking').toggle();
                if ('{{ session('langSelect') }}' == "TH") {
                    text = "โปรดกรอกข้อมูลการค้นหา"
                    confirm = "ตกลง"
                } else {
                    text = "Please fill in your search information."
                    confirm = "Confirm"
                }
                Swal.fire({
                    title: text,
                    icon: "error",
                    confirmButtonText: confirm,
                    confirmButtonColor: '#d33328',
                });
            } else {

                const formData = new FormData();
                formData.append('input', input);
                formData.append('lat', lat);
                formData.append('log', log);
                const res = await axios.post("{{ env('APP_URL') }}/walkin/otp", formData, {
                    "Content-Type": "multipart/form-data"
                }).then((res) => {
                    if (res.data.status == 'success') {
                        $('#refID').val(res.data.refid);
                        $('#searchSection').hide();
                        $('#otpSection').show();
                        $('#OTP_Phone').html(res.data.phone);
                        $('#inputOpt').attr("placeholder", 'Ref : ' + res.data.ref);
                    } else if (res.data.status == 'distant') // Not in range
                    {
                        $('#btnOTPCheck').toggle();
                        $('#btnOTPChecking').toggle();
                        if ('{{ session('langSelect') }}' == "TH") {
                            text_title = "ไม่อยู่ในระยะที่สามารถรับคิวได้"
                        } else {
                            text_title = "The queue cannot be received within the distance."
                        }
                        Swal.fire({
                            title: text_title,
                            icon: "error",
                            allowOutsideClick: false,
                            confirmButtonColor: "#4db1ab",
                            showConfirmButton: true,
                        });
                    } else if (res.data.status == 'phone') // No Phone Found
                    {
                        $('#searchInput').html(res.data.search)
                        $('#searchResult').html(res.data.result)
                        $('#searchSection').hide();
                        $('#resultSection').show();
                    }
                })
            }
        }

        async function resultCheck() {
            $('#otp_resultcheck').hide();
            $('#otp_resultchecking').show();
            if ('{{ session('langSelect') }}' == "TH") {
                otp_text = "โปรดกรอก OTP"
                otp_text_noid = "ไม่พบ Referance OTP ในระบบ"
                otp_text_match = "รหัส OTP ไม่ถูกต้อง"
                confirm = "ตกลง"
            } else {
                otp_text = "Please fill in OTP code."
                otp_text_noid = "Not found Referance OTP"
                otp_text_match = "OTP code invalid."
                confirm = "Confirm"
            }

            var otp = $('#inputOpt').val();
            if (otp == '') {
                $('#otp_resultcheck').show();
                $('#otp_resultchecking').hide();
                Swal.fire({
                    title: otp_text,
                    icon: "error",
                    confirmButtonText: confirm,
                    confirmButtonColor: '#d33328',
                });
            } else {
                var input = $('#inputSearch').val();
                var refID = $('#refID').val();

                const formData = new FormData();
                formData.append('input', input);
                formData.append('otp', otp);
                formData.append('ref', refID);
                const res = await axios.post("{{ env('APP_URL') }}/walkin/result", formData, {
                    "Content-Type": "multipart/form-data"
                }).then((res) => {
                    if (res.data.status == 'otpid') {
                        $('#otp_resultcheck').show();
                        $('#otp_resultchecking').hide();
                        Swal.fire({
                            title: otp_text_noid,
                            icon: "error",
                            confirmButtonText: confirm,
                            confirmButtonColor: '#d33328',
                        });
                    } else if (res.data.status == 'otpnotmatch') {
                        $('#otp_resultcheck').show();
                        $('#otp_resultchecking').hide();
                        Swal.fire({
                            title: otp_text_match,
                            icon: "error",
                            confirmButtonText: confirm,
                            confirmButtonColor: '#d33328',
                        });
                        $('#inputOpt').val('');
                        $('#inputOpt').focus();
                    } else if (res.data.status == 'success') {
                        $('#inputOpt').val('');
                        $('#searchSection').hide();
                        $('#otpSection').hide();
                        $('#resultSection').show();
                        $('#searchInput').html(res.data.search)
                        $('#searchResult').html(res.data.result)
                    }
                })
            }
        }

        async function selectItem(hn, type) {
            myhn = hn
            mytype = type

            if (type == "OTP") {
                hn = 'walkin' + $('#inputSearch').val();
            }
            if ('{{ session('langSelect') }}' == "TH") {
                t_title = "ยืนยันการรับคิว"
                btn_confirm = "รับคิว"
                btn_can = "ยกเลิก"
                wait = "กรุณารอสักครู่"
                if (type == 'M' || type == "OTP") {
                    t_text = 'หากท่านต้องการเข้ารับบริการ กรุณากดรับคิว เพื่อรอรับบริการต่อจากผู้ป่วยนัด'
                } else {
                    t_text = '';
                }
            } else {
                t_title = "Confirm to get queue"
                btn_confirm = "Confirm"
                btn_can = "Cancel"
                wait = "Please, wait."
                if (type == 'M' || type == "OTP") {
                    t_text =
                        'If you want to receive services Please accept the queue. Please wait to receive service after patient\'s appointment.'
                } else {
                    t_text = '';
                }
            }
            $('#sleItem').prop('onclick', null);

            Swal.fire({
                title: t_title,
                text: t_text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: btn_confirm,
                confirmButtonColor: "#4db1ab",
                cancelButtonText: btn_can,
                cancelButtonColor: "#adb5bd"
            }).then(async result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: wait,
                        icon: "warning",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false
                    });

                    const formData = new FormData();
                    formData.append('hn', hn);
                    const res = await axios.post("{{ env('APP_URL') }}/walkin/genQueue", formData, {
                        "Content-Type": "multipart/form-data"
                    }).then((res) => {
                        window.location.href = '{{ env('APP_URL') }}/walkin/viewqueue/' + hn
                    })
                } else {
                    window.location.reload()
                }
            });

        }
    </script>
@endsection
