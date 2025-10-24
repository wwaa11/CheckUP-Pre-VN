<?php
namespace App\Jobs;

use App\Models\Master;
use App\Models\Number;
use App\Models\Time;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessGenerateQueue implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;
    public $tries   = 50;
    public $backoff = 3;

    public function uniqueId(): string
    {
        return 'Generate Number';
    }

    public function handle(): void
    {
        $masters = Master::whereDate('created_at', date('Y-m-d'))
            ->whereNull('number')
            ->orderBy('check_in', 'asc')
            ->get();

        foreach ($masters as $item) {
            $getNumber = Number::where('date', date('Y-m-d'))->first();
            if ($getNumber == null) {
                $newDate       = new Number;
                $newDate->date = date('Y-m-d');
                $newDate->save();
                $getNumber = Number::where('date', date('Y-m-d'))->first();
            }
            $type             = $item->type;
            $number           = $getNumber->$type + 1;
            $queueNumber      = $type . str_pad($number, 3, '0', STR_PAD_LEFT);
            $getNumber->$type = $number;
            $getNumber->save();

            $arrayQueue       = Time::where('station', 'checkup')->where('type', $type)->first();
            $arrayQueue->list = json_decode($arrayQueue->list);
            $temp_list        = $arrayQueue->list;
            if (! in_array($queueNumber, $temp_list)) {
                array_push($temp_list, $queueNumber);
                $arrayQueue->list = json_encode($temp_list);
                $arrayQueue->save();
                Log::channel('generate')->info($item->hn . ' generate ' . $queueNumber . ' to ' . $type);

                $item->number = $queueNumber;
                $item->save();
            }
        }

        ProcessGenerateQueue::dispatch()->delay(1);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('services')->error('ProcessGenerateQueue failed: ' . $exception->getMessage());
        ProcessGenerateQueue::dispatch()->delay(10);
    }
}
