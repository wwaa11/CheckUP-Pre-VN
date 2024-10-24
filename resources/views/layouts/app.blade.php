<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Praram9 CheckUP</title>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.js') }}"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('font-awesome/css/all.min.css') }}">
    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<style>
    @font-face {
        font-family: 'Prompt';
        src: url({{ asset('font/Prompt.ttf') }});
    }
</style>
<body style="font-family: 'Prompt', sans-serif; background-color: #fff">
    <div class="flex flex-row-reverse">
        <select class="rounded m-1 p-1 border border-gray-400" id="langSelecter" onchange="langSelect()">
            <option autocomplete="off" @if (session('langSelect') == 'TH') selected @endif value="TH">TH</option>
            <option autocomplete="off" @if (session('langSelect') == 'ENG') selected @endif value="ENG">ENG</option>
        </select>
    </div>
    <div class="flex">
        <div class="text-center m-auto">
            <img width="100" src="{{ asset('images/logo.png') }}">
        </div>
    </div>
    @yield('content')
</body>
<script>
    function langSelect() {
        lang = $('#langSelecter').val();
        const formData = new FormData();
        formData.append('lang', lang);
        const res = axios.post("/changeLang", formData, {
            "Content-Type": "multipart/form-data"
        }).then((res) => {
            window.location.reload()
        })
    }
</script>
@yield('scripts')

</html>
