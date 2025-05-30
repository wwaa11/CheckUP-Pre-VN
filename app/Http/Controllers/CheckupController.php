<?php
namespace App\Http\Controllers;

use App\Jobs\ProcessGenerateQueue;
use App\Models\Master;
use App\Models\Number;
use App\Models\OTP;
use App\Models\Time;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CheckupController extends Controller
{
    private $LocationDistant = 0.5;

    public function test()
    {

    }
    public function dispatchGenerate()
    {
        ProcessGenerateQueue::dispatch();

        return response()->json('success', 200);
    }
    private function lang($text)
    {
        if (session("langSelect") == 'ENG') {
            switch ($text) {
                case "range_check":
                    $trans_text = "Checking location.";
                    break;
                case "not_in_range":
                    $trans_text = "Not in the range where you can accept the queue.";
                    break;
                case "now_range":
                    $trans_text = "Current distance from check-in point";
                    break;
                case "name":
                    $trans_text = "Name";
                    break;
                case "dob":
                    $trans_text = "Date of Birth";
                    break;
                case "number":
                    $trans_text = "Number";
                    break;
                case "check":
                    $trans_text = "Check";
                    break;
                case "not_found":
                    $trans_text = "Not found!";
                    break;
                case "checkin_walkin":
                    $trans_text = "Click to receive the queue, No appointment.";
                    break;
                case "app_no":
                    $trans_text = "Appointment Number";
                    break;
                case "app_date":
                    $trans_text = "Appointment Date";
                    break;
                case "app_time":
                    $trans_text = "Appointment Time";
                    break;
                case "no_app":
                    $trans_text = "No Appointment";
                    break;
                case "check_app":
                    $trans_text = "Check Appointment";
                    break;
                case "get_queue":
                    $trans_text = "Receive queue";
                    break;
                case "already_queue":
                    $trans_text = "Already received the queue";
                    break;
                case "cantCheckLocation":
                    $trans_text = "Check Location Error, Please refresh.";
                    break;
                case "checkup":
                    $trans_text = "Check UP Center : Building B floor 12.";
                    break;
                default:
                    $trans_text = $text;
                    break;
            }
        } else {
            switch ($text) {
                case "range_check":
                    $trans_text = "กำลังเช็คสถานที่";
                    break;
                case "not_in_range":
                    $trans_text = "ไม่อยู่ในระยะที่สามารถกดรับคิวได้";
                    break;
                case "now_range":
                    $trans_text = "ระยะปัจจุบันห่างจากจุดเช็คอิน";
                    break;
                case "name":
                    $trans_text = "ชื่อ";
                    break;
                case "dob":
                    $trans_text = "วันเกิด";
                    break;
                case "number":
                    $trans_text = "หมายเลข";
                    break;
                case "check":
                    $trans_text = "ตรวจสอบ";
                    break;
                case "not_found":
                    $trans_text = "ไม่พบข้อมูล";
                    break;
                case "checkin_walkin":
                    $trans_text = "กดเพื่อรับคิวไม่ได้นัด";
                    break;
                case "app_no":
                    $trans_text = "หมายเลขนัด";
                    break;
                case "app_date":
                    $trans_text = "วันที่นัด";
                    break;
                case "app_time":
                    $trans_text = "เวลานัด";
                    break;
                case "no_app":
                    $trans_text = "ไม่มีคิวนัดวันนี้";
                    break;
                case "check_app":
                    $trans_text = "เช็คนัด";
                    break;
                case "get_queue":
                    $trans_text = "กดเพื่อรับคิว";
                    break;
                case "already_queue":
                    $trans_text = "รับคิวไปแล้ว";
                    break;
                case "cantCheckLocation":
                    $trans_text = "ไม่สามารถเช็คตำแหน่งได้ โปรดลองอีกครั้ง";
                    break;
                case "checkup":
                    $trans_text = "ศูนย์ตรวจสุขภาพ : อาคาร B ชั้น 12.";
                    break;
                default:
                    $trans_text = $text;
                    break;
            }
        }

        return $trans_text;
    }
    private function formatName($first, $last)
    {
        mb_internal_encoding('UTF-8');
        $setname = mb_substr($first, 1);
        $setlast = mb_substr($last, 1);
        if (str_contains($setname, '\\')) {
            $setname = explode("\\", $setname);
            $setname = $setname[1] . $setname[0];
        }
        $fullname = $setname . " " . $setlast;

        return $fullname;
    }
    public function changeLang(Request $request)
    {
        $lang = $request->lang;
        if ($lang == 'TH') {
            session()->put('langSelect', "TH");
        } elseif ($lang == "ENG") {
            session()->put('langSelect', "ENG");
        }

        return response()->json('success', 200);
    }
    public function latlogCheck($input_lat, $input_lon)
    {
        $base_lat      = "13.7530601";
        $base_lon      = "100.5688306";
        $theta         = $input_lon - $base_lon;
        $dist          = sin(deg2rad($input_lat)) * sin(deg2rad($base_lat)) + cos(deg2rad($input_lat)) * cos(deg2rad($base_lat)) * cos(deg2rad($theta));
        $dist          = acos($dist);
        $dist          = rad2deg($dist);
        $miles         = $dist * 60 * 1.1515;
        $outputDistant = $miles * 1.609344;

        return $outputDistant;
    }
    public function checkLocation(Request $request)
    {
        $hn = $request->hn;
        if ($request->lat == '-' || $request->log == '-') {
            $html = '<div class="text-center cursor-pointer p-3 font-bold rounded border-red-600 text-red-600 mt-3 text-3xl">' . $this->lang('cantCheckLocation') . '</div>';

            return response()->json(['status' => 'success', 'html' => $html], 200);
        }

        $myApp = DB::connection('SSB')
            ->table('HNAPPMNT_HEADER')
            ->whereDate('HNAPPMNT_HEADER.AppointDateTime', date('Y-m-d'))
            ->where('HNAPPMNT_HEADER.Clinic', '1800')
            ->where('HNAPPMNT_HEADER.HN', $hn)
            ->orderBy('HNAPPMNT_HEADER.AppointDateTime', 'ASC')
            ->first();

        if ($myApp !== null) {
            $outputDistant = $this->latlogCheck($request->lat, $request->log);

            if ($outputDistant > $this->LocationDistant) {
                $html = '<div class="text-center cursor-pointer p-3 font-bold rounded border-red-600 text-red-600 mt-3 text-3xl">';
                $html .= '<div>' . $this->lang('not_in_range') . '</div>';
                $html .= '<div>' . $this->lang('now_range') . ' : ' . round($outputDistant, 1) . ' Km</div>';
                $html .= '</div>';
            } else {
                $findAleadry = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $hn)->whereNull('success_by')->first();
                ($findAleadry !== null)
                ? $html = '<div class="text-center cursor-pointer p-3 font-bold rounded border-red-600 text-red-600 mt-3 text-3xl">' . $this->lang('already_queue') . '</div>'
                : $html = '<div class="text-center cursor-pointer p-3 mt-3"><button type="button" onclick="selectItem(\'' . $hn . '\')" class="rounded text-yellow-300 bg-[#37beaf] p-3 w-full md:w-1/2 shadow text-4xl font-bold">' . $this->lang('get_queue') . '</button></div>';
            }
        } else {
            $html = '<div class="text-center cursor-pointer p-3 font-bold rounded border-red-600 text-red-600 mt-3 text-3xl">' . $this->lang('no_app') . '</div>';
        }

        return response()->json(['status' => 'success', 'html' => $html], 200);
    }

    public function genQueue($typeQueue, $hn)
    {
        $getNumber = Number::where('date', date('Y-m-d'))->lockForUpdate()->first();
        if ($getNumber == null) {
            $newDate       = new Number;
            $newDate->date = date('Y-m-d');
            $newDate->save();
            $getNumber = Number::where('date', date('Y-m-d'))->lockForUpdate()->first();
        }
        $number                = $getNumber->$typeQueue + 1;
        $queueNumber           = $typeQueue . str_pad($number, 3, '0', STR_PAD_LEFT);
        $getNumber->$typeQueue = $number;
        $getNumber->save();

        Log::channel('daily')->notice($hn . ' prepare ' . $queueNumber . ' to ' . $typeQueue);
        $arrayQueue       = Time::where('station', 'checkup')->where('type', $typeQueue)->first();
        $arrayQueue->list = json_decode($arrayQueue->list);
        $temp_list        = $arrayQueue->list;
        if (! in_array($queueNumber, $temp_list)) {
            array_push($temp_list, $queueNumber);
            $arrayQueue->list = json_encode($temp_list);
            $arrayQueue->save();
            Log::channel('daily')->notice($hn . ' success ' . $queueNumber . ' to ' . $typeQueue);
        } else {
            Log::channel('daily')->notice($hn . ' err ' . $queueNumber . ' alerdy in ' . $typeQueue);

            return 'duplicate';
        }

        return $queueNumber;
    }
    public function requestQueue(Request $request)
    {
        ini_set('max_execution_time', '10');
        Log::channel('daily')->notice($request->hn . ' request ' . $request->headers->get('referer'));
        $getNumber = RateLimiter::attempt(
            $request->hn,
            5,
            function () use ($request) {
                $hn             = $request->hn;
                $iswalkinNodata = 0;
                if (substr($hn, 0, 6) == "walkin") {
                    $hn             = substr($hn, 6);
                    $iswalkinNodata = 1;
                }
                $findMaster = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $hn)->whereNull('success_by')->first();
                if ($findMaster !== null) {
                    Log::channel('daily')->notice($hn . ' send ' . $findMaster->number);

                    return response()->json('success Queue Number :' . $findMaster->number, 200);
                }
                // Check Walkin
                if ($iswalkinNodata == 1) {
                    // Get Queue M
                    $master           = new Master;
                    $master->app      = null;
                    $master->check_in = date('Y-m-d H:i:s');
                    $master->hn       = $hn;
                    $master->name     = 'Walkin';
                    $master->lang     = 2;
                    $master->number   = null;
                    $master->type     = 'M';
                    $master->add_time = date('H:i');
                    $master->save();
                } else {
                    // $startQuery = date('Y-m-d H:i:s');

                    Log::channel('daily')->notice($hn . ' Query : Start : ' . date('Y-m-d H:i:s'));
                    $hnDetail = DB::connection('SSB')
                        ->table('HNPAT_INFO')
                        ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
                        ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
                        ->whereNull('HNPAT_INFO.FileDeletedDate')
                        ->where('HNPAT_INFO.HN', $hn)
                        ->where('HNPAT_ADDRESS.SuffixTiny', 1)
                        ->select(
                            'HNPAT_INFO.HN',
                            'HNPAT_INFO.BirthDateTime',
                            'HNPAT_INFO.NationalityCode',
                            'HNPAT_REF.RefNo',
                            'HNPAT_ADDRESS.MobilePhone'
                        )
                        ->first();
                    Log::channel('daily')->notice($hn . ' -> : Info Success : ' . date('Y-m-d H:i:s'));

                    $hnpatName = DB::connection('SSB')
                        ->table('HNPAT_NAME')
                        ->where('HNPAT_NAME.HN', $hn)
                        ->first();
                    Log::channel('daily')->notice($hn . ' -> : Name Success : ' . date('Y-m-d H:i:s'));

                    $myApp = DB::connection('SSB')
                        ->table('HNAPPMNT_HEADER')
                        ->whereDate('HNAPPMNT_HEADER.AppointDateTime', date('Y-m-d'))
                        ->where('HNAPPMNT_HEADER.Clinic', '1800')
                        ->where('HNAPPMNT_HEADER.HN', $hn)
                        ->orderBy('HNAPPMNT_HEADER.AppointDateTime', 'ASC')
                        ->first();
                    Log::channel('daily')->notice($hn . ' -> : Appointment Success : ' . date('Y-m-d H:i:s'));

                    // $endQuery = date('Y-m-d H:i:s');
                    // if($startQuery !== $endQuery){
                    //     Log::channel('daily')->notice($hn . ' Query Slow :SKIP : '.date('Y-m-d H:i:s') );

                    //     return 'slow';
                    // }

                    Log::channel('daily')->notice($hn . ' Query : Success : ' . date('Y-m-d H:i:s'));
                    if ($myApp == null) {
                        $master           = new Master;
                        $master->app      = 'WALKIN';
                        $master->check_in = date('Y-m-d H:i:s');
                        $master->hn       = $hn;
                        $master->name     = $this->formatName($hnpatName->FirstName, $hnpatName->LastName);
                        $master->lang     = ($hnDetail->NationalityCode == 'THA') ? 1 : 2;
                        $master->number   = null;
                        $master->type     = 'M';
                        $master->add_time = date('H:i');
                        $master->dob      = $hnDetail->BirthDateTime;
                        $master->save();
                    } else // Get Appoint
                    {
                        $queueU = ['A1', 'A2', 'A3', 'A4', 'A7', 'A10', 'AI', 'AB2', 'AB3', 'AG2', 'AG3', 'A31', 'A129'];
                        if (in_array($myApp->AppmntProcedureCode1, $queueU) || in_array($myApp->AppmntProcedureCode2, $queueU) || in_array($myApp->AppmntProcedureCode3, $queueU) || in_array($myApp->AppmntProcedureCode4, $queueU) || in_array($myApp->AppmntProcedureCode5, $queueU)) {
                            $code = 'U';
                        } else {
                            $time  = strtotime($myApp->AppointDateTime);
                            $hours = date('H', $time);
                            switch ($hours) {
                                case '7':
                                    $code = 'A';
                                    break;
                                case '8':
                                    $code = 'B';
                                    break;
                                case '9':
                                    $code = 'C';
                                    break;
                                case '10':
                                    $code = 'D';
                                    break;
                                case '11':
                                    $code = 'E';
                                    break;
                                case '12':
                                    $code = 'H';
                                    break;
                                case '13':
                                    $code = 'V';
                                    break;
                                case 'U':
                                    $code = 'U';
                                    break;
                                default:
                                    $code = 'M';
                                    break;
                            }
                        }

                        $master           = new Master;
                        $master->app      = $myApp->AppointmentNo;
                        $master->check_in = date('Y-m-d H:i:s');
                        $master->hn       = $hn;
                        $master->name     = $this->formatName($hnpatName->FirstName, $hnpatName->LastName);
                        $master->lang     = ($hnDetail->NationalityCode == 'THA') ? 1 : 2;
                        $master->number   = null;
                        $master->type     = $code;
                        $master->add_time = date('H:i');
                        $master->dob      = $hnDetail->BirthDateTime;
                        $master->save();
                    }
                }

                return 'created master';
            }
        );
        if (! $getNumber) {
            Log::channel('daily')->notice($request->hn . ' request failed. Too many request.');

            return response()->json('too many request :', 429);
        } else if ($getNumber == 'slow') {
            Log::channel('daily')->notice($request->hn . ' request Skip.');

            return response()->json('Server slightly slow, please try again!', 409);
        } else if ($getNumber == 'duplicate') {
            Log::channel('daily')->notice($request->hn . ' request duplicate Skip.');

            return response()->json('Please, try again!', 409);
        } else if ($getNumber == 'created master') {
            Log::channel('daily')->notice($request->hn . ' request success.');

            return response()->json('Created transcation success!', 200);
        } else {
            Log::channel('daily')->notice($request->hn . ' request success.');

            return response()->json('Unknow Error!', 500);
        }
    }

    public function smsView($hashHN)
    {
        $text = (object) [
            'checkup'     => $this->lang('checkup'),
            'name'        => $this->lang('name'),
            'app_no'      => $this->lang('app_no'),
            'app_date'    => $this->lang('app_date'),
            'app_time'    => $this->lang('app_time'),
            'range_check' => $this->lang('range_check'),
        ];

        $getHN = DB::connection('SMS')
            ->table('TB_HAS_HN')
            ->where('hasHN', $hashHN)
            ->first();

        if ($getHN == null) {
            $hn = $hashHN;
        } else {
            $hn = $getHN->HN;
        }

        $data = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $hn)->first();
        if ($data !== null) {

            return view('myQueue')->with(compact('data'));
        }

        $hnDetail = DB::connection('SSB')
            ->table('HNPAT_INFO')
            ->leftjoin('HNPAT_NAME', 'HNPAT_INFO.HN', '=', 'HNPAT_NAME.HN')
            ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
            ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
            ->whereNull('HNPAT_INFO.FileDeletedDate')
            ->where('HNPAT_INFO.HN', $hn)
            ->where('HNPAT_ADDRESS.SuffixTiny', 1)
            ->where('HNPAT_NAME.SuffixSmall', 0)
            ->select(
                'HNPAT_INFO.HN',
                'HNPAT_INFO.BirthDateTime',
                'HNPAT_INFO.NationalityCode',
                'HNPAT_NAME.FirstName',
                'HNPAT_NAME.LastName',
                'HNPAT_REF.RefNo',
                'HNPAT_ADDRESS.MobilePhone'
            )
            ->first();
        if ($hnDetail == null) {
            $hnDetail = (object) [
                'name'    => 'No Data',
                'HN'      => $hn,
                'appNo'   => 'No Data',
                'appDate' => 'No Data',
                'appTime' => 'No Data',
            ];

            return view('sms')->with(compact('hnDetail', 'text'));
        }

        $hnDetail->name = $this->formatName($hnDetail->FirstName, $hnDetail->LastName);
        ($hnDetail->NationalityCode == 'THA') ? session()->put('langSelect', "TH") : session()->put('langSelect', "ENG");

        $myApp = DB::connection('SSB')
            ->table('HNAPPMNT_HEADER')
            ->whereDate('HNAPPMNT_HEADER.AppointDateTime', date('Y-m-d'))
            ->where('HNAPPMNT_HEADER.Clinic', '1800')
            ->where('HNAPPMNT_HEADER.HN', $hn)
            ->orderBy('HNAPPMNT_HEADER.AppointDateTime', 'ASC')
            ->first();

        if ($myApp !== null) {
            $strTime           = strtotime($myApp->AppointDateTime);
            $hnDetail->appNo   = $myApp->AppointmentNo;
            $hnDetail->appDate = date('d M Y', $strTime);
            $hnDetail->appTime = date('H', $strTime) . ':00';
        } else {
            $hnDetail->appNo   = $this->lang('no_app');
            $hnDetail->appDate = date('d M Y');
            $hnDetail->appTime = '-';
        }

        return view('sms')->with(compact('hnDetail', 'text'));
    }
    public function myQueue($hn)
    {
        if (substr($hn, 0, 6) == "walkin") {
            $hn = substr($hn, 6);
        }
        $data = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $hn)->whereNull('success_by')->first();
        if ($data == null) {

            return redirect(env('APP_URL') . '/walkin');
        }

        return view('myQueue')->with(compact('data'));
    }

    public function genOTP()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $otp_ref    = $characters[rand(0, strlen($characters) - 1)] . mt_rand(100, 999);
        $otp_code   = mt_rand(100000, 999999);

        $result = (object) [
            'ref'  => $otp_ref,
            'code' => $otp_code,
        ];

        return $result;
    }
    public function sendSMS($phone, $ref, $code)
    {
        $field = '{"destination": "' . $phone . '","country": "TH","clientMessageId": "SMS-001","text": "Your OTP =' . $code . '(Ref. Code:' . $ref . ') Do not disclose this OTP with anyone.","scheduled": null}';
        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://192.168.99.6:8090/api/8x8/sms/sendSMS',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $field,
            CURLOPT_HTTPHEADER     => [
                'API_KEY: ' . env('API_SMS') . '',
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
    }
    public function walkinSendOTP(Request $request)
    {
        $genOTP                       = $this->genOTP();
        $findOTP                      = OTP::find($request->ref_id);
        ($findOTP == null) ? $findOTP = new OTP : null;
        $findOTP->ref                 = $genOTP->ref;
        $findOTP->code                = $genOTP->code;
        $findOTP->sendDate            = date('Y-m-d H:i:s');
        $findOTP->save();

        $this->sendSMS($findOTP->phone, $findOTP->ref, $findOTP->code);

        return response()->json(['status' => 'success', 'ref' => $findOTP->ref]);
    }
    public function walkin()
    {
        if (session('langSelect') == null) {
            session()->put('langSelect', "TH");
        }

        return view('walkin');
    }
    public function walkinOTP(Request $request)
    {
        $masterHN = strip_tags($request->input);
        if ($request->lat == '-' || $request->log == '-') {

            return abort(400);
        }
        $outputDistant = $this->latlogCheck($request->lat, $request->log);
        if ($outputDistant > $this->LocationDistant) {

            return response()->json(['status' => 'distant', 'distant' => $outputDistant]);
        }

        $getHN = DB::connection('SSB')
            ->table('HNPAT_INFO')
            ->leftjoin('HNPAT_NAME', 'HNPAT_INFO.HN', '=', 'HNPAT_NAME.HN')
            ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
            ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
            ->whereNull('HNPAT_INFO.FileDeletedDate')
            ->where('HNPAT_ADDRESS.SuffixTiny', 1)
            ->where('HNPAT_NAME.SuffixSmall', 0)
            ->where(function ($query) use ($masterHN) {
                $query->where('HNPAT_REF.RefNo', $masterHN)
                    ->orwhere('HNPAT_ADDRESS.MobilePhone', $masterHN);
            })
            ->select(
                'HNPAT_ADDRESS.MobilePhone'
            )
            ->groupBy(
                'HNPAT_ADDRESS.MobilePhone'
            )
            ->first();

        if ($getHN !== null) {
            if ($getHN->MobilePhone !== null) {
                $genOTP = $this->genOTP();

                $findOTP                      = OTP::where('phone', $getHN->MobilePhone)->first();
                ($findOTP == null) ? $findOTP = new OTP : null;
                $findOTP->phone               = $getHN->MobilePhone;
                $findOTP->ref                 = '-';
                $findOTP->code                = $genOTP->code;
                $findOTP->sendDate            = date('Y-m-d H:i:s');
                $findOTP->save();

                return response()->json(['status' => 'success', 'phone' => substr($getHN->MobilePhone, -4), 'refid' => $findOTP->id, 'ref' => '-']);
            }
        }

        $html             = '';
        $findWalkinMaster = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $masterHN)->where('name', 'Walkin')->whereNull('success_by')->first();
        if ($findWalkinMaster !== null) {
            $html .= '<div class="shadow m-3 p-3">';
            $html .= '<div class="grid grid-cols-2">';
            $html .= '<span class="mb-2">' . $this->lang('name') . '</span>';
            $html .= '<span class="mb-2">' . $findWalkinMaster->name . ' ( ' . $findWalkinMaster->hn . ' )</span>';
            $html .= '<span class="mb-2">' . $this->lang('dob') . '</span>';
            $html .= '<span class="mb-2">-</span>';
            $html .= '<span class="mb-2">' . $this->lang('number') . '</span>';
            $html .= '<span class="mb-2 text-queuenumber text-end">' . $findWalkinMaster->number . '</span>';
            $html .= '</div>';
            $html .= '<a href="walkin/viewqueue/' . $findWalkinMaster->hn . '">';
            $html .= '<div class="border-2 text-green-600 border-green-600 rounded-l p-2 text-center">' . $this->lang('check') . '</div>';
            $html .= '</a>';
            $html .= '</div>';
        } else {
            $html .= '<div id="sleItem" onclick="selectItem(\'walkin' . $masterHN . '\',\'M\')" class="row m-3 p-3 text-center" style="border: #ff7735 3px solid; font-size: 1.5rem; color: #ff7735; cursor: pointer;">';
            $html .= '<div>' . $this->lang('not_found') . '</div>';
            $html .= '<div>' . $this->lang('checkin_walkin') . '</div>';
            $html .= '</div>';
        }

        return response()->json(['status' => 'phone', 'search' => $masterHN, 'result' => $html]);
    }
    public function walkinResult(Request $request)
    {
        // Check OTP
        $otp     = strip_tags($request->otp);
        $findOTP = OTP::whereDate('sendDate', date('Y-m-d'))->where('id', $request->ref)->first();
        if ($findOTP == null) {

            return response()->json(['status' => 'otpid', 'result' => 'not found OTP ID']);
        } elseif ($findOTP->code !== $otp) {

            return response()->json(['status' => 'otpnotmatch', 'result' => 'notmatch']);
        } else {
            $masterHN = $request->input;
            // Search queue
            $html             = '';
            $findWalkinMaster = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $masterHN)->where('name', 'Walkin')->whereNull('success_by')->first();
            if ($findWalkinMaster !== null) {
                $html .= '<div class="shadow p-3 m-3">';
                $html .= '  <div class="grid grid-cols-2">';
                $html .= '      <span class="mb-2">' . $this->lang('name') . '</span>';
                $html .= '      <span class="mb-2">' . $findWalkinMaster->name . ' ( ' . $findWalkinMaster->hn . ' )</span>';
                $html .= '      <span class="mb-2">' . $this->lang('dob') . '</span>';
                $html .= '      <span class="mb-2">-</span>';
                $html .= '      <span class="mb-2">' . $this->lang('number') . '</span>';
                $html .= '      <span class="mb-2 text-end font-bold text-red-600 text-2xl">' . $findWalkinMaster->number . '</span>';
                $html .= '  </div>';
                $html .= '<a href="walkin/viewqueue/' . $findWalkinMaster->hn . '">';
                $html .= '  <div class="border-2 text-green-600 bg-green-600 rounded-l p-2 text-center">' . $this->lang('check') . '</div>';
                $html .= '</a>';
                $html .= '</div>';
            }
            // Search for HN
            $getHN = DB::connection('SSB')
                ->table('HNPAT_INFO')
                ->leftjoin('HNPAT_NAME', 'HNPAT_INFO.HN', '=', 'HNPAT_NAME.HN')
                ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
                ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
                ->whereNull('HNPAT_INFO.FileDeletedDate')
                ->where('HNPAT_ADDRESS.SuffixTiny', 1)
                ->where('HNPAT_NAME.SuffixSmall', 0)
                ->where(function ($query) use ($masterHN) {
                    $query->where('HNPAT_REF.RefNo', $masterHN)
                        ->orwhere('HNPAT_ADDRESS.MobilePhone', $masterHN);
                })
                ->select(
                    'HNPAT_INFO.HN',
                    'HNPAT_INFO.BirthDateTime',
                    'HNPAT_NAME.FirstName',
                    'HNPAT_NAME.LastName',
                )
                ->groupBy(
                    'HNPAT_INFO.HN',
                    'HNPAT_INFO.BirthDateTime',
                    'HNPAT_NAME.FirstName',
                    'HNPAT_NAME.LastName',
                )
                ->get();
            if (count($getHN) == 0) {
                $html .= '<div id="sleItem" onclick="selectItem(\'walkin' . $masterHN . '\',\'M\')" class="row m-3 p-3 text-center" style="border: #ff7735 3px solid; font-size: 1.5rem; color: #ff7735; cursor: pointer;">';
                $html .= '<div>' . $this->lang('not_found') . '</div>';
                $html .= '<div>' . $this->lang('checkin_walkin') . '</div>';
                $html .= '</div>';
            } else {
                // Check in Master
                foreach ($getHN as $item) {
                    $dob                            = strtotime($item->BirthDateTime);
                    $checkMaster                    = Master::whereDate('check_in', date('Y-m-d'))->where('hn', $item->HN)->whereNull('success_by')->first();
                    $getHashHN                      = DB::connection('SMS')->table('TB_HAS_HN')->where('HN', $item->HN)->first();
                    ($getHashHN !== null) ? $hashHN = $getHashHN->hasHN : $hashHN = $item->HN;
                    if ($checkMaster == null) {
                        $myApp = DB::connection('SSB')
                            ->table('HNAPPMNT_HEADER')
                            ->whereDate('HNAPPMNT_HEADER.AppointDateTime', date('Y-m-d'))
                            ->where('HNAPPMNT_HEADER.Clinic', '1800')
                            ->where('HNAPPMNT_HEADER.HN', $item->HN)
                            ->first();

                        $html .= '<div class="shadow p-3 m-3">';
                        $html .= '<div class="grid grid-cols-2">';
                        $html .= '<span class="mb-2">' . $this->lang('name') . '</span>';
                        $html .= '<span class="mb-2">' . $this->formatName($item->FirstName, $item->LastName) . ' ( ' . $item->HN . ' )</span>';
                        $html .= '<span class="mb-2">' . $this->lang('dob') . '</span>';
                        $html .= '<span class="mb-2">' . date('d M Y', $dob) . ' ( ' . (date('Y', $dob) + 543) . ')' . '</span>';
                        $html .= '<span class="mb-2">' . $this->lang('app_no') . '</span>';
                        if ($myApp !== null) {
                            $html .= '<span class="text-end font-bold text-red-600 text-2xl mb-2">' . $myApp->AppointmentNo . '</span>';
                        } else {
                            $html .= '<span class="text-end font-bold text-red-600 text-2xl mb-2">' . $this->lang('no_app') . '</span>';
                        }
                        $html .= '</div>';

                        if ($myApp == null) {
                            $html .= '<a href="walkin/viewapp/' . $hashHN . '">';
                            $html .= '<div class="m-3 border-2 text-blue-600 border-blue-600 rounded-l p-2 text-center cursor-pointer">' . $this->lang('check_app') . '</div>';
                            $html .= '</a>';
                        }
                        $html .= '<div id="sleItem"';
                        if ($myApp !== null) {
                            $html .= 'onclick="selectItem(\'' . $item->HN . '\',\'A\')"';
                        } else {
                            $html .= 'onclick="selectItem(\'' . $item->HN . '\',\'M\')"';
                        }
                        $html .= 'class="m-3 border-2 text-green-600 border-green-600 rounded-l p-2 text-center cursor-pointer">' . $this->lang('get_queue');
                        $html .= '</div>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="shadow p-3 m-3">';
                        $html .= '  <div class="grid grid-cols-2">';
                        $html .= '    <span >' . $this->lang('name') . '</span>';
                        $html .= '    <span >' . $this->formatName($item->FirstName, $item->LastName) . ' ( ' . $item->HN . ' )</span>';
                        $html .= '    <span >' . $this->lang('dob') . '</span>';
                        $html .= '    <span >' . date('d M Y', $dob) . ' ( ' . (date('Y', $dob) + 543) . ' )' . '</span>';
                        $html .= '    <span >' . $this->lang('number') . '</span>';
                        $html .= '    <span class="text-end font-bold text-red-600 text-2xl mb-3">' . $checkMaster->number . '</span>';
                        $html .= '  </div>';
                        $html .= '  <a href="walkin/viewqueue/' . $item->HN . '">';
                        $html .= '  <div class="border-2 text-green-600 border-green-600 rounded-l p-2 text-center">' . $this->lang('check') . '</div>';
                        $html .= '  </a>';
                        $html .= '</div>';
                    }
                }
            }

            return response()->json(['status' => 'success', 'search' => $masterHN, 'result' => $html]);
        }
    }
    public function myAPP($hashHN)
    {
        $getHN = DB::connection('SMS')
            ->table('TB_HAS_HN')
            ->where('hasHN', $hashHN)
            ->first();

        if ($getHN == null) {
            $hn = $hashHN;

            return redirect(env('APP_URL') . '/walkin');
        } else {
            $hn = $getHN->HN;
        }

        $hnData = DB::connection('SSB')
            ->table('HNPAT_INFO')
            ->leftjoin('HNPAT_NAME', 'HNPAT_INFO.HN', '=', 'HNPAT_NAME.HN')
            ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
            ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
            ->whereNull('HNPAT_INFO.FileDeletedDate')
            ->where('HNPAT_INFO.HN', $hn)
            ->where('HNPAT_ADDRESS.SuffixTiny', 1)
            ->where('HNPAT_NAME.SuffixSmall', 0)
            ->select(
                'HNPAT_INFO.HN',
                'HNPAT_INFO.BirthDateTime',
                'HNPAT_INFO.NationalityCode',
                'HNPAT_NAME.FirstName',
                'HNPAT_NAME.LastName',
                'HNPAT_REF.RefNo',
                'HNPAT_ADDRESS.MobilePhone'
            )
            ->first();

        $hnData->Fullname = $this->lang('name') . ' ' . $this->formatName($hnData->FirstName, $hnData->LastName) . ' ( ' . $hn . ' )';
        $strTime          = strtotime($hnData->BirthDateTime);
        $hnData->Data     = $this->lang('dob') . ' ' . date('d M Y', $strTime) . ' ( ' . (date('Y', $strTime) + 543) . ' ) ';

        $myApp = DB::connection('SSB')
            ->table('HNAPPMNT_HEADER')
            ->leftJoin('DNSYSCONFIG', function ($join) {
                $join->on('DNSYSCONFIG.CtrlCode', '=', DB::raw('42203'));
                $join->on('DNSYSCONFIG.Code', '=', 'HNAPPMNT_HEADER.Clinic');
            })
            ->leftjoin('HNDOCTOR_MASTER', 'HNAPPMNT_HEADER.Doctor', '=', 'HNDOCTOR_MASTER.Doctor')
            ->whereDate('HNAPPMNT_HEADER.AppointDateTime', '>=', date('Y-m-d'))
            ->where('HNAPPMNT_HEADER.HN', $hn)
            ->whereNull('cxlReasonCode')
            ->select(
                'HNAPPMNT_HEADER.HN',
                'HNAPPMNT_HEADER.AppointmentNo',
                'HNAPPMNT_HEADER.AppointDateTime',
                'DNSYSCONFIG.EnglishName AS ClinicEN',
                'DNSYSCONFIG.LocalName AS ClinicTH',
                'HNDOCTOR_MASTER.LocalName AS DocTH',
                'HNDOCTOR_MASTER.EnglishName AS DocEN',
            )
            ->get();

        mb_internal_encoding('UTF-8');
        foreach ($myApp as $item) {
            $item->DocTH      = mb_substr($item->DocTH, 1);
            $item->DocEN      = mb_substr($item->DocEN, 1);
            $item->ClinicTH   = mb_substr($item->ClinicTH, 1);
            $item->ClinicEN   = mb_substr($item->ClinicEN, 1);
            $item->AppStrTime = strtotime($item->AppointDateTime);
        }

        return view('myApp')->with(compact('hnData', 'myApp'));
    }

    public function login()
    {
        return view('login');
    }
    public function auth(Request $request)
    {
        $response = Http::withHeaders([
            'token' => env('API_KEY'),
        ])->post('http://172.20.1.12/dbstaff/api/auth', [
            "userid"   => $request->user,
            "password" => $request->password,
        ])->object();

        if ($response->status == 1) {
            session(['userid' => $response->user->userid, 'name' => $response->user->name]);

            $user = User::firstOrCreate([
                'userid' => $response->user->userid,
                'name'   => $response->user->name,
            ]);

            if (Auth::loginUsingId($user->id)) {

                return response()->json(['status' => 1, 'text' => 'Authentication Success!'], 200);
            } else {

                return response()->json(['status' => 0, 'text' => 'Authentication Success , User not found!'], 200);
            }

        }

        return response()->json(['status' => 0, 'text' => 'Authentication Failed!'], 200);
    }
    public function verify(Request $request)
    {
        if (Auth::check()) {

            return view('verify');
        }

        return view('login');
    }
    public function verifyData(Request $request)
    {
        $input = strip_tags($request->input);
        $html  = '';
        $data  = Master::whereDate('check_in', date('Y-m-d'))
            ->where('hn', $input)
            ->whereNull('success_by')
            ->first();
        if ($data !== null) {
            $html .= '<tr>';
            $html .= '<td class="p-3 border border-gray-600">' . $data->hn . '</td>';
            $html .= '<td class="p-3 border border-gray-600">' . $data->name . '</td>';
            $html .= '<td class="p-3 border border-gray-600">' . $data->app . '</td>';
            $html .= '<td class="p-3 border border-gray-600 text-center text-red-600 font-bold">';
            if ($data->number == null) {
                $html .= 'ระบบกำลังสร้างคิว <div>กรุณากด search อีกครั้ง</div>';
            } else {
                $html .= $data->number;
            }
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            $data = DB::connection('SSB')
                ->table('HNPAT_INFO')
                ->leftjoin('HNPAT_NAME', 'HNPAT_INFO.HN', '=', 'HNPAT_NAME.HN')
                ->leftjoin('HNPAT_REF', 'HNPAT_INFO.HN', '=', 'HNPAT_REF.HN')
                ->leftjoin('HNPAT_ADDRESS', 'HNPAT_INFO.HN', '=', 'HNPAT_ADDRESS.HN')
                ->whereNull('HNPAT_INFO.FileDeletedDate')
                ->where('HNPAT_ADDRESS.SuffixTiny', 1)
                ->where('HNPAT_NAME.SuffixSmall', 0)
                ->where(function ($query) use ($input) {
                    $query->where('HNPAT_REF.RefNo', $input)
                        ->orwhere('HNPAT_INFO.HN', $input)
                        ->orwhere('HNPAT_ADDRESS.MobilePhone', $input);
                })
                ->select(
                    'HNPAT_INFO.HN',
                    'HNPAT_INFO.BirthDateTime',
                    'HNPAT_NAME.FirstName',
                    'HNPAT_NAME.LastName',
                )
                ->groupBy(
                    'HNPAT_INFO.HN',
                    'HNPAT_INFO.BirthDateTime',
                    'HNPAT_NAME.FirstName',
                    'HNPAT_NAME.LastName',
                )
                ->get();
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $data = Master::whereDate('check_in', date('Y-m-d'))
                        ->where('hn', $row->HN)
                        ->whereNull('success_by')
                        ->first();
                    if ($data !== null) {
                        $html .= '<tr>';
                        $html .= '<td class="p-3 border border-gray-600">' . $data->hn . '</td>';
                        $html .= '<td class="p-3 border border-gray-600">' . $data->name . '</td>';
                        $html .= '<td class="p-3 border border-gray-600">' . $data->app . '</td>';
                        $html .= '<td class="p-3 border border-gray-600 text-center text-red-600 font-bold">' . $data->number . '</td>';
                        $html .= '</tr>';
                    } else {
                        $name = $this->formatName($row->FirstName, $row->LastName);
                        $app  = DB::connection('SSB')->table('HNAPPMNT_HEADER')
                            ->whereDate('HNAPPMNT_HEADER.AppointDateTime', date('Y-m-d'))
                            ->where('HNAPPMNT_HEADER.Clinic', '1800')
                            ->where('HNAPPMNT_HEADER.HN', $row->HN)
                            ->first();

                        $html .= '<tr>';
                        $html .= '<td class="p-3 border border-gray-600">' . $row->HN . '</td>';
                        $html .= '<td class="p-3 border border-gray-600">' . $name . '</td>';
                        if ($app == null) {
                            $html .= '<td class="p-3 border border-gray-600 text-red-600">ไม่มีนัด</td>';
                            $html .= '<td class="p-3 border border-gray-600 text-center"><div class="m-3 border-2 text-green-600 border-green-600 rounded-l p-2 text-center cursor-pointer" onclick="selectItem(\'' . $row->HN . '\',\'M\')">รับคิว</div></td>';
                        } else {
                            $html .= '<td class="p-3 border border-gray-600 text-red-600">' . $app->AppointmentNo . '</td>';
                            $html .= '<td class="p-3 border border-gray-600 text-center"><div class="m-3 border-2 text-green-600 border-green-600 rounded-l p-2 text-center cursor-pointer" onclick="selectItem(\'' . $row->HN . '\',\'M\')">รับคิว</div></td>';
                        }
                        $html .= '</tr>';
                    }
                }
            } else {
                $html .= '<tr>';
                $html .= '<td class="p-3 border border-gray-600">' . $input . '</td>';
                $html .= '<td class="p-3 border border-gray-600">walkin</td>';
                $html .= '<td class="p-3 border border-gray-600"></td>';
                $html .= '<td class="p-3 border border-gray-600"><div id="sleItem" onclick="selectItem(\'walkin' . $input . '\',\'M\')" class="m-3 border-2 text-green-600 border-green-600 rounded-l p-2 text-center cursor-pointer" >รับคิว</div></td>';
                $html .= '</tr>';
            }

        }

        return response()->json(['status' => 'success', 'result' => $html], 200);
    }
}
