<?php

namespace Fygarciaj\Passport\Http\Middleware;

use Closure;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
use Fygarciaj\Passport\Exceptions\MissingScopeException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class CheckClientCredentialsForAnyScope
{
    /**
     * The Resource Server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * Create a new middleware instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @return void
     */
    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException|\Fygarciaj\Passport\Exceptions\MissingScopeException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = (new DiactorosFactory)->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        if ($this->validateScopes($psr, $scopes)) {
            return $next($request);
        }

        throw new MissingScopeException($scopes);
    }

    /**
     * Validate the scopes on the incoming request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $psr
     * @param  array  $scopes
     * @return bool
     */
    protected function validateScopes($psr, $scopes)
    {
        if (in_array('*', $tokenScopes = $psr->getAttribute('oauth_scopes'))) {
            return true;
        }

        foreach ($scopes as $scope) {
            if (in_array($scope, $tokenScopes)) {
                return true;
            }
        }

        return false;
    }
}
