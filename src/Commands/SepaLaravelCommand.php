<?php

namespace SepaLaravel\SepaLaravel\Commands;

use Illuminate\Console\Command;

class SepaLaravelCommand extends Command
{
    public $signature = 'sepa-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
