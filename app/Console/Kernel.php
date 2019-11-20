<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Book;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookAvailable;

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
            $books = Book::where('available', false)->get();

            foreach($books as $book)
            {
                $page = file_get_contents($book->url);
                preg_match('/<div class=\"headline headline\-left\">(.*?)<\/div>/s', $page, $matches);

                if(str_contains($matches[0], 'Not yet published')){
                    $book->available = false;
                    $book->save();
                }elseif(str_contains($matches[0], 'Free shipping')){
                    $book->available = true;
                    $book->save();

                    Mail::to('jerome.arfouche@gmail.com')->send(new BookAvailable($book));
                }
                $page = null;
            }

        })->dailyAt('9:00');
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
