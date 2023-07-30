<?php

namespace App\Console\Commands;

use App\Mail\AlertMail;
use App\Mail\DeviceAlertMail;
use App\Models\Devices;
use App\Models\Log;
use App\Models\Ports;
use App\Models\Servers;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use MongoDB\Driver\Exception\Exception;
use Throwable;

class CheckTemp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-temp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    //return true if port work
    function checkTemp($device)
    {
        return $device->temp > $device->mailSettings->temp;
    }

    function checkHumidity($device)
    {
        return $device->humidity > $device->mailSettings->humidity;
    }

    function sendMailtoUsers($details)
    {
        //getting all users on database
        $users = User::all();

        //send mail to all users
        foreach ($users as $user) {
            if (optional($user->user_login_permission)->is_allowed) {
                try {
                    // send mail
                    Mail::to($user->email)->send(new DeviceAlertMail($details));
                } catch (Throwable $e) {

                }

            }
        }
    }

    protected function asDateTime()
    {
        $currentTime = Carbon::now("Europe/Istanbul");
        return $currentTime->toDateTimeString();
    }


    public function handle()
    {
        set_time_limit(59);
        $details = [
            'name' => '',
            'type' => '',
            'temp' =>'',
            'humidity' =>'',
            'updated_at' => "",
        ];

        $deviceData = Devices::all();

        foreach ($deviceData as $item) {

            if($this->checkTemp($item)){
                $details['name'] = $item->name;
                $details['updated_at'] = $this->asDateTime();
                $details['temp'] = $item->temp;
                $details['type'] = "Temperature";
                $this->sendMailtoUsers($details);
            }

            if($this->checkHumidity($item)){
                $details['name'] = $item->name;
                $details['updated_at'] = $this->asDateTime();
                $details['humidity'] = $item->humidity;
                $details['type'] ="Humidity";
                $this->sendMailtoUsers($details);
            }

        }


    }
}
