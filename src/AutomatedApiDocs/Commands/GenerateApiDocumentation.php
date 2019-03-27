<?php

namespace OwowAgency\AutomatedApiDoc\Commands;

use Illuminate\Console\Command;
use OwowAgency\AutomatedApiDoc\Parsers\ApibParser;

class GenerateApiDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documentation:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the API Documentation from the JSON file.';

    /**
     * @var ApibParser
     */
    private $parser;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->parser = new ApibParser(config('automated-api-docs'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = config('automated-api-docs.temporary_path');

        $this->parser->handle(file_get_contents($file));

        unlink($file);
    }
}
