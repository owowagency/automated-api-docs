<?php

namespace OwowAgency\AutomatedApiDocs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Validation\ValidationException;

class Docs implements Arrayable
{
    /**
     * All the collection data.
     *
     * @var array
     */
    private $collections = [];

    /**
     * @param  array  $parameters
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     *
     * @throws \ReflectionException
     */
    public function monitorCall($parameters, $request, $response)
    {
        $route = $request->route();

        $controllerMethod = $this->getReflectionMethodFromController($route);

        $uri = $route->uri();
        $collectionName = $this->getCollectionNameFromUri($uri);
        $method = $request->method();
        $httpCode = $response->getStatusCode();

        // Create all the keys so that we can easily set the information.
        $routeParametersKey = sprintf('%s.paths.%s.route_parameters', $collectionName, $uri);
        $docKey = sprintf('%s.paths.%s.routes.%s.summary', $collectionName, $uri, $method);
        $requestBodyKey = sprintf('%s.paths.%s.routes.%s.items.%s.request_body', $collectionName, $uri, $method, $httpCode);
        $requestRulesKey = sprintf('%s.paths.%s.routes.%s.items.%s.request_rules', $collectionName, $uri, $method, $httpCode);
        $responseBodyKey = sprintf('%s.paths.%s.routes.%s.items.%s.response_body', $collectionName, $uri, $method, $httpCode);

        data_set($this->collections, $routeParametersKey, $route->parameterNames());
        data_set($this->collections, $docKey, $this->getDescriptionFromDocs($controllerMethod->getDocComment()));
        data_set($this->collections, $requestBodyKey, $parameters);
        data_set($this->collections, $requestRulesKey, $this->getRulesFromRequest($controllerMethod));
        data_set($this->collections, $responseBodyKey, json_decode($response->getContent(), true));
    }

    /**
     * Create the proper json format so that it's more generic and other
     * languages can easily use the JSON format.
     *
     * @return array
     */
    protected function formatToGenericJson()
    {
        $collections = [];

        foreach ($this->collections as $collectionName => $attributes) {
            $collections[] = [
                'collection_name' => $collectionName,
                'paths' => $this->getCollectionItems($attributes['paths']),
            ];
        }

        return $collections;
    }

    /**
     * Create the second level of the collection, which are the uri's.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getCollectionItems($attributes)
    {
        $items = [];

        foreach ($attributes as $uri => $item) {
            $items[] = [
                'uri' => $uri,
                'route_parameters' => $item['route_parameters'] ?? [],
                'routes' => $this->getRoutes($item['routes']),
            ];
        }

        return $items;
    }

    /**
     * Create the third level of the collection, which are the actual routes.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getRoutes($attributes)
    {
        $routes = [];

        foreach ($attributes as $method => $route) {
            $routes[] = [
                'method' => $method,
                'summary' => $route['summary'],
                'items' => $this->getRouteItems($route['items']),
            ];
        }

        return $routes;
    }

    /**
     * Create the fourth level of the collection, which are the requests and
     * responses for the route.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getRouteItems($attributes)
    {
        $routeItems = [];

        foreach ($attributes as $code => $routeItem) {
            $routeItems[] = [
                'code' => $code,
                'request_body' => $routeItem['request_body'] ?? null,
                'request_rules' => $routeItem['request_rules'] ?? null,
                'response_body' => $routeItem['response_body'] ?? [],
            ];
        }

        return $routeItems;
    }

    /**
     * Get the request rules via the controller method.
     *
     * @param  \ReflectionMethod  $method
     * @return array
     */
    protected function getRulesFromRequest($method)
    {
        foreach ($method->getParameters() as $parameter) {
            $name = strtolower($parameter->getName());

            if (str_contains($name, 'request')) {
                $requestName = $parameter->getType()->getName();

                try {
                    $formRequest = app($requestName);

                    if (method_exists('rules', $formRequest)) {
                        return $formRequest->rules();
                    }
                } catch (ValidationException $e) {
                    return $e->validator->getRules();
                }
            }
        }

        return null;
    }

    /**
     * Get the collection name from the URI.
     *
     * @param  string  $uri
     * @return string
     */
    protected function getCollectionNameFromUri($uri)
    {
        // Remove the API version from the uri.
        $uri = preg_replace('/^\/?(v[0-9]+)\/?/i', '', $uri);

        preg_match('/^\/?([a-zA-Z0-9\._-]+)\/?/', $uri, $matches);

        if (count($matches) >= 2) {
            return $matches[1];
        }

        return 'Unknown';
    }

    /**
     * Go over each line of the PHPDoc and get all the relevant information
     * which can be used as actual documentation for the route.
     *
     * @param  string  $phpDoc
     * @return string
     */
    private function getDescriptionFromDocs($phpDoc)
    {
        $description = '';

        $lines = preg_split("/((\r?\n)|(\r\n?))/", $phpDoc);

        foreach($lines as $line) {
            preg_match('/^\s*?\*\s(.*)$/', $line, $matches);

            if (count($matches) >= 2) {
                $docs = $matches[1];

                if (! str_contains($docs, ['@param', '@return', '@throws'])) {
                    $description .= $docs . ' ';
                }
            }
        }

        return rtrim($description, ' ');
    }

    /**
     * Get the reflection method from the controller which is used in the given
     * route. This can be used to get all parameters and other useful
     * information about the route call.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \ReflectionMethod
     *
     * @throws \ReflectionException
     */
    private function getReflectionMethodFromController($route)
    {
        $controller = get_class($route->getController());
        $method = $route->getActionMethod();

        if ($method == $controller) {
            $method = '__invoke';
        }

        $rc = new \ReflectionClass($controller);

        return $rc->getMethod($method);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->formatToGenericJson();
    }
}
