@extends('layouts.app')
@section('content')
    <div class="w-full md:w-2/4 m-auto">
        <div class="text-center m-auto p-6 text-3xl text-red-600">
            Check Information
        </div>
        <div class="flex m-auto gap-3">
            <input id="search" autocomplete="off" placeholder="HN,PHONE,IDCARD" type="text"
                class="flex-grow p-3 border-2 border-blue-600 w-full rounded">
            <button class="rounded border-2 border-blue-600 text-blue-600 p-3" onclick="search()">Search</button>
        </div>
        <div class="mt-6">
            <table class="table border-collapse border border-gray-600 p-3 w-full">
                <thead>
                    <tr>
                        <th class="p-3 border border-gray-600">HN</th>
                        <th class="p-3 border border-gray-600">Name</th>
                        <th class="p-3 border border-gray-600">App No.</th>
                        <th class="p-3 border border-gray-600">Number</th>
                    </tr>
                </thead>
                <tbody id="inputResult">
                    <tr class="hidden">
                        <td class="p-3 border border-gray-600">999999</td>
                        <td class="p-3 border border-gray-600">Test Test</td>
                        <td class="p-3 border border-gray-600">walkin</td>
                        <td class="p-3 border border-gray-600 text-center text-red-600 font-bold">M0001</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        async function search() {
            var input = $('#search').val()
            var wait = '<tr><td colspan="4" class="p-3 text-center text-red-600 text-3xl">Searching</td></tr>'
            $('#inputResult').html(wait);

            const formData = new FormData();
            formData.append('input', input);
            await axios.post("{{ env('APP_URL') }}/verify/data", formData, ).then((res) => {
                if (res.data.status == 'success') {
                    $('#inputResult').html(res.data.result);
                }
            })
        }
        async function selectItem(hn, type) {
            myhn = hn
            Swal.fire({
                title: 'รับคิว HN : ' + hn,
                icon: "warning",
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonText: 'รับคิว',
                confirmButtonColor: "green",
                cancelButtonText: 'ยกเลิก',
                cancelButtonColor: "#adb5bd"
            }).then(async result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'รอสักครู่..',
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
                        swal.close()
                        setTimeout(function() {
                            search()
                        }, 1000);

                    }).catch(function(error) {
                        Swal.fire({
                            title: 'Error',
                            text: error,
                            icon: "warning",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: true
                        });
                        setTimeout(function() {
                            search()
                        }, 1000);
                    });
                }
            });
        }
    </script>
@endsection
