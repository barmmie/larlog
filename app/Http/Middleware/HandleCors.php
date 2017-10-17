<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
class HandleCors
{

    private $options;

    public function __construct()
    {
        $this->options = [
            'allowedOrigins' => true,
            'supportsCredentials' => false,
            'allowedHeaders' => true,
            'exposedHeaders' => [],
            'allowedMethods' => true,
            'maxAge' => 0,
        ];
    }
    /**
     * Handle an incoming request. 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->isCorsRequest($request)) {
            return $next($request);
        }
        if ($this->isPreflightRequest($request)) {
            $response = $next($request);
            return $this->addPreflightRequestHeaders($response, $request);
        }
        if (! $this->isActualRequestAllowed($request)) {
            return new LaravelResponse('Not allowed.', 403);
        }
        // Add the headers on the Request Handled event as fallback in case of exceptions
        
        $response = $next($request);
        return $this->addHeaders($request, $response);
    }
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function addHeaders(Request $request, Response $response)
    {
        // Prevent double checking
        if (! $response->headers->has('Access-Control-Allow-Origin')) {
            $response = $this->addActualRequestHeaders($response, $request);
        }
        return $response;
    }

    protected function isCorsRequest($request)
    {
        return $request->headers->has('Origin') && !$this->isSameHost($request);
    }

    private function isSameHost(Request $request)
    {
        return $request->headers->get('Origin') === $request->getSchemeAndHttpHost();
    }

    private function isPreflightRequest(Request $request)
    {

        return $this->isCorsRequest($request)
            && $request->getMethod() === 'OPTIONS'
            && $request->headers->has('Access-Control-Request-Method');
    }

    public function addPreflightRequestHeaders(Response $response, Request $request)
    {
        if (true !== $check = $this->checkPreflightRequestConditions($request)) {
            return $check;
        }
        if ($this->options['supportsCredentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        $allowOrigin = $this->options['allowedOrigins'] === true && !$this->options['supportsCredentials']
            ? '*'
            : $request->headers->get('Origin');
        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        if ($this->options['maxAge']) {
            $response->headers->set('Access-Control-Max-Age', $this->options['maxAge']);
        }
        $allowMethods = $this->options['allowedMethods'] === true
            ? strtoupper($request->headers->get('Access-Control-Request-Method'))
            : implode(', ', $this->options['allowedMethods']);
        $response->headers->set('Access-Control-Allow-Methods', $allowMethods);
        $allowHeaders = $this->options['allowedHeaders'] === true
            ? $request->headers->get('Access-Control-Request-Headers')
            : implode(', ', $this->options['allowedHeaders']);
        $response->headers->set('Access-Control-Allow-Headers', $allowHeaders);
        return $response;
    }

    private function checkPreflightRequestConditions(Request $request)
    {
        if (!$this->checkOrigin($request)) {
            return $this->createBadRequestResponse(403, 'Origin not allowed');
        }
        if (!$this->checkMethod($request)) {
            return $this->createBadRequestResponse(405, 'Method not allowed');
        }
        // if allowedHeaders has been set to true ('*' allow all flag) just skip this check
        if ($this->options['allowedHeaders'] !== true && $request->headers->has('Access-Control-Request-Headers')) {
            $allowedHeaders = array_map('strtolower', $this->options['allowedHeaders']);
            $headers = strtolower($request->headers->get('Access-Control-Request-Headers'));
            $requestHeaders = explode(',', $headers);
            foreach ($requestHeaders as $header) {
                if (!in_array(trim($header), $allowedHeaders)) {
                    return $this->createBadRequestResponse(403, 'Header not allowed');
                }
            }
        }
        return true;
    }

    private function checkMethod(Request $request)
    {
        if ($this->options['allowedMethods'] === true) {

            return true;
        }
        $requestMethod = strtoupper($request->headers->get('Access-Control-Request-Method'));
        return in_array($requestMethod, $this->options['allowedMethods']);
    }

    private function createBadRequestResponse($code, $reason = '')
    {
        return new Response($reason, $code);
    }

    private function checkOrigin(Request $request)
    {
        if ($this->options['allowedOrigins'] === true) {

            return true;
        }
        
        return false;
    }

    private function addActualRequestHeaders(Response $response, Request $request)
    {
        if (!$this->checkOrigin($request)) {
            return $response;
        }
        $allowOrigin = $this->options['allowedOrigins'] === true && !$this->options['supportsCredentials']
            ? '*'
            : $request->headers->get('Origin');
        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        if (!$response->headers->has('Vary')) {
            $response->headers->set('Vary', 'Origin');
        } else {
            $response->headers->set('Vary', $response->headers->get('Vary') . ', Origin');
        }
        if ($this->options['supportsCredentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($this->options['exposedHeaders']) {
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }
        return $response;
    }


    private function isActualRequestAllowed(Request $request)
    {
        return $this->checkOrigin($request);
    }


}