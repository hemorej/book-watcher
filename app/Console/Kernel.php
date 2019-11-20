<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Book;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookAvailable;
use Illuminate\Support\Str;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function(){
            $context = stream_context_create(array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );
            $books = Book::where('available', false)->get();

            foreach($books as $book)
            {
                try{
                    $page = file_get_contents($book->url, false, $context);
                }catch(\Exception $e){

                    continue;
                }

                if(Str::contains($book->url, 'mackbooks')){
                    if(Str::contains($page, 'Available to pre-order')){
                        $book->available = false;
                        $book->save();
                    }else{
                        $book->available = true;
                        $book->save();
                        
                        Mail::to(env('NOTIFICATION_RECIPIENT'))->send(new BookAvailable($book));
                    }
                }else{
                    preg_match('/<div class=\"headline headline\-left\">(.*?)<\/div>/s', $page, $matches);
                
                    if(Str::contains($matches[0], 'Not yet published')){
                        $book->available = false;
                        $book->save();
                    }elseif(Str::contains($matches[0], 'Free shipping')){
                        $book->available = true;
                        $book->save();
                        
                        Mail::to(env('NOTIFICATION_RECIPIENT'))->send(new BookAvailable($book));
                    }
                }

                $page = null;
            }


        })->dailyAt('09:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
