<?php

namespace OwowAgency\AutomatedApiDocs\Commands;

use Illuminate\Console\Command;
use OwowAgency\AutomatedApiDocs\Parsers\Parser;

class GenerateApiDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the API Documentation from the JSON file.';

    /**
     * The list of all parsers.
     *
     * @var \OwowAgency\AutomatedApiDocs\Parsers\Parser[]
     */
    private $parsers;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->initializeParsers();
    }

    /**
     * Get all parsers from the configuration and initialize the classes.
     *
     * @return void
     */
    protected function initializeParsers()
    {
        $config = config('automated-api-docs');

        $parsers = config('automated-api-docs.parsers', []);

        foreach ($parsers as $class) {
            $parser = new $class($config);

            if ($parser instanceof Parser) {
                $this->parsers[] = $parser;
            }
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (count($this->parsers) === 0) {
            return $this->warn('No parsers are set in the "automated-api-docs.parsers" config.');
        }

        $file = config('automated-api-docs.temporary_path');

        $collections = json_decode(file_get_contents($file));

        foreach ($this->parsers as $parser) {
            $parser->handle($collections);
        }

        unlink($file);
    }
}
