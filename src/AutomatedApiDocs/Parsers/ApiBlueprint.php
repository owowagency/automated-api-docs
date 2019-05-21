<?php

namespace OwowAgency\AutomatedApiDocs\Parsers;

use Illuminate\Support\Str;

class ApiBlueprint extends Parser
{
    /**
     * The API Blueprint structure.
     *
     * @var string
     */
    private $content;

    /**
     * Execute the parser and transform to correct format.
     *
     * @param  array  $collections
     * @return void
     */
    public function handle($collections)
    {
        $this->content = "";

        foreach ($collections as $collection) {
            $this->createGroup($collection);
        }

        $this->saveContent($this->content);
    }

    /**
     * Add a new route group to the API Blueprint content.
     *
     * @param  object  $collection
     * @return void
     */
    protected function createGroup($collection)
    {
        $this->content .= "# Group /{$collection->collection_name}\n\n";

        foreach ($collection->paths as $path) {
            $this->addApiPath($collection->collection_name, $path);
        }
    }

    /**
     * Add a new path to the API Blueprint content.
     *
     * @param  string  $collectionName
     * @param  object  $path
     * @return void
     */
    protected function addApiPath($collectionName, $path)
    {
        $this->content .= "## {$collectionName} [/{$path->uri}]\n\n";

        $this->addRouteParameters($path->route_parameters);

        foreach ($path->routes as $route) {
            $this->addRoute($path->uri, $route);
        }
    }

    /**
     * Add the optional route parameters to the API Blueprint content.
     *
     * @param  object  $parameters
     * @return void
     */
    protected function addRouteParameters($parameters)
    {
        if (count($parameters) > 0) {
            $this->content .= "+ Parameters\n\n";

            foreach ($parameters as $parameter) {
                $this->content .= "    + {$parameter} (no type yet) - No description\n\n";
            }

            $this->content .= "\n\n";
        }

    }

    /**
     * Add a new route to the API Blueprint content.
     *
     * @param  string  $pathUri
     * @param  object  $route
     * @return void
     */
    protected function addRoute($pathUri, $route)
    {
        $this->content .= "### /{$pathUri} [{$route->method}]\n\n";
        $this->content .= "{$route->summary}\n\n";

        foreach ($route->items as $key => $routeItem) {
            $this->addRouteItem($routeItem);
        }
    }

    /**
     * Add a route item to the API Blueprint content.
     *
     * @param  object  $routeItem
     * @return void
     */
    protected function addRouteItem($routeItem)
    {
        $this->content .= "+ Request (application/json)\n\n";
        $this->content .= "    + Headers\n\n";
        $this->content .= "            Accept: application/json\n\n";

        $segments = ['request_body', 'request_rules', 'response_body'];

        foreach ($segments as $segment) {
            $method = 'add' . Str::studly($segment);

            if (method_exists($this, $method)) {
                $this->$method($routeItem);
            }
        }
    }

    /**
     * Add the request body to the API Blueprint content.
     *
     * @param  object  $routeItem
     * @return void
     */
    protected function addRequestBody($routeItem)
    {
        if (! is_null($routeItem->request_body)) {
            $requestBody = json_encode($routeItem->request_body);

            $this->content .= "    + Body\n\n";
            $this->content .= "            {$requestBody}\n\n";
        }
    }

    /**
     * Add the request rules to the API Blueprint content.
     *
     * @param  object  $routeItem
     * @return void
     */
    protected function addRequestRules($routeItem)
    {
        if (! is_null($routeItem->request_rules)) {
            $requestRules = json_encode($routeItem->request_rules);

            $this->content .= "    + Schema\n\n";
            $this->content .= "            {$requestRules}\n\n";
        }
    }

    /**
     * Add the response body to the API Blueprint content.
     *
     * @param  object  $routeItem
     * @return void
     */
    protected function addResponseBody($routeItem)
    {
        $responseBody = json_encode($routeItem->response_body);

        if ($routeItem->code == 204) {
            $responseBody = '';
        }

        $this->content .= "+ Response {$routeItem->code} (application/json)\n\n";
        $this->content .= "        {$responseBody}\n\n";
    }

    /**
     * Get the file extension of the docs format.
     *
     * @return string
     */
    public function getExtension()
    {
        return 'apib';
    }
}
