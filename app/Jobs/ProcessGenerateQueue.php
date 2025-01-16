<?php

namespace App\Jobs;

use App\Models\Master;
use App\Models\Number;
use App\Models\Time;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessGenerateQueue implements ShouldQueue
{
    use Queueable;
    public $tries = 50;
    public $backoff = 3;

    public function middleware(): array
    {
        return [new WithoutOverlapping('Generate Number')];
    }

    public function uniqueId(): string
    {
        return 'Generate Number';
    }

    public function __construct()
    {

    }

    public function handle(): void
    {
        $masters = Master::whereDate('created_at', date('Y-m-d'))
            ->whereNull('number')
            ->orderBy('check_in', 'asc')
            ->get();

        foreach($masters as $item){
            $getNumber = Number::where('date', date('Y-m-d'))->first();
            if ($getNumber == null) {
                $newDate = new Number;
                $newDate->date = date('Y-m-d');
                $newDate->save();
                $getNumber = Number::where('date', date('Y-m-d'))->first();
            }
            $type = $item->type;
            $number = $getNumber->$type + 1;
            $queueNumber = $type . str_pad($number, 3, '0', STR_PAD_LEFT);
            $getNumber->$type = $number;
            $getNumber->save();

            $arrayQueue = Time::where('station', 'checkup')->where('type', $type)->first();
            $arrayQueue->list = json_decode($arrayQueue->list);
            $temp_list = $arrayQueue->list;
            if (!in_array($queueNumber, $temp_list)) {
                array_push($temp_list, $queueNumber);
                $arrayQueue->list = json_encode($temp_list);
                $arrayQueue->save();
                Log::channel('daily')->notice($item->hn.' generate ' . $queueNumber . ' to ' . $type);

                $item->number = $queueNumber;
                $item->save();
            }
        }
        
        ProcessGenerateQueue::dispatch()->delay(1);
    }

    public function failed(?Throwable $exception): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.line.me/v2/bot/message/push',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "to": "U3d7ba4f0386437906a68612c1cce5eba",
            "messages":[
                {
                    "type":"text",
                    "text":"Services Error",
                    "quickReply": {
                        "items": [
                        {
                            "type": "action",
                            "action": {
                                "type": "uri",
                                "label": "Generate Number Error",
                                "uri": "https://pr9webhub.praram9.com/checkup/serviceStart"
                            }
                        }
                        ]
                    }
                }
            ]
        }',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.env('LINE_Token').'','Content-Type: application/json'
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
    }
}
