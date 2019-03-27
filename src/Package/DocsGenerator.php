<?php

namespace OwowAgency\AutomatedApiDocs;

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use OwowAgency\AutomatedApiDocs\Parsers\ApibParser;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait DocsGenerator
{
    /**
     * @var \OwowAgency\AutomatedApiDocs\Docs
     */
    protected static $docs;

    /**
     * Indicates if the call should be monitored.
     *
     * @var bool
     */
    protected $shouldMonitor = false;

    /**
     * Should the next call be monitored.
     *
     * @return $this
     */
    public function monitor()
    {
        $this->shouldMonitor = true;

        return $this;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     * @return \Illuminate\Foundation\Testing\TestResponse
     *
     * @throws \ReflectionException
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (! $this->shouldMonitor) {
            return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        }

        if (is_null(static::$docs)) {
            static::$docs = new Docs();
        }

        $this->shouldMonitor = false;

        $kernel = $this->app->make(HttpKernel::class);

        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($uri), $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        if ($this->followRedirects) {
            $response = $this->followRedirects($response);
        }

        // The only difference from the parent call method. We need this to
        // actually monitor the call.
        static::$docs->monitorCall($parameters, $request, $response);

        $kernel->terminate($request, $response);

        return $this->createTestResponse($response);
    }

    /**
     * Export the documentation array to a JSON file.
     *
     * @param  array  $config
     * @return void
     */
    public function exportDocsToJson($config)
    {
        $file = $config['temporary_path'] ?? __DIR__ . '/Parsers/docs.json';

        if (! file_exists($file)) {
            touch($file);
        }

        $documentationInfo = optional(static::$docs)->toArray();

        if (is_null($documentationInfo) || count($documentationInfo) === 0) {
            return;
        }

        file_put_contents($file, json_encode($documentationInfo));
    }
}