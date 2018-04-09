<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;


use App\Post;

use Exception;
use Illuminate\Console\Command;




class ServeApplicationCommand extends Command
{

    protected $signature = "serve";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create simple php server";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            `php -S localhost:8000 -t public`;
        } catch (Exception $e) {
            $this->error("An error occurred");
        }
    }
}
