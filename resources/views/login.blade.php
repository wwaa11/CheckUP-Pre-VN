@extends('layouts.app')
@section('content')
    <div class="w-full md:w-2/4 m-auto">
        <div class="text-center m-auto p-6 text-3xl text-red-600">
            กรุณาเข้าสู่ระบบ
        </div>
        <div class="text-center mb-3">
            <input id="user" class="p-3 w-full border" placeholder="รหัสพนักงาน" type="text">
        </div>
        <div class="text-center mb-3">
            <input id="password" class="p-3 w-full border" placeholder="รหัสผ่าน" type="password">
        </div>
        <button class="w-full p-3 rounded bg-green-400" onclick="login()">Login</button>
    </div>
@endsection
@section('scripts')
    <script>
        async function login(){
            const formData = new FormData();
            formData.append('user', $('#user').val());
            formData.append('password',  $('#password').val());
            await axios.post("{{ env('APP_URL') }}/auth", formData, ).then((res) => {
                if (res.data.status == '1') {
                    window.location.href = '{{ env('APP_URL') }}/verify'
                }else{
                    Swal.fire({
                        title: res.data.text,
                        icon: "warning",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: true
                    });
                }
            })
        }
    </script>
@endsection
