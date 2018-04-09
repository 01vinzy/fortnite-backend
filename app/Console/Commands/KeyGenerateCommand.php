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




class KeyGenerateCommand extends Command
{

    protected $signature = "key:generate";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a random application key.";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
          $envFile = base_path('.env');
          $str = file_get_contents($envFile);

          $oldValue = env('APP_KEY');

          $newValue = bin2hex(random_bytes(64));

          $str = str_replace("APP_KEY={$oldValue}", "APP_KEY={$newValue}", $str);
          file_put_contents($envFile, $str);
          // $fp = fopen($envFile, 'w');
          // fwrite($fp, $str);
          // fclose($fp);
        } catch (Exception $e) {
            $this->error("An error occurred");
        }
    }
}
