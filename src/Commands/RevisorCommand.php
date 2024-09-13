<?php

namespace Indra\Revisor\Commands;

use Illuminate\Console\Command;

class RevisorCommand extends Command
{
    public $signature = 'laravel-revisor';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
