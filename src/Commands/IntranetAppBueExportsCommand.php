<?php

namespace Hwkdo\IntranetAppBueExports\Commands;

use Illuminate\Console\Command;

class IntranetAppBueExportsCommand extends Command
{
    public $signature = 'intranet-app-bue-exports';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
