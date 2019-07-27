<?php

namespace Fygarciaj\Passport;

use Carbon\Carbon;

class TokenRepository
{
    protected $connection;


    public function __construct()
    {
        $this->connection = Passport::getConnection();
    }

    public function connection($conn)
    {
        $this->connection = $conn;
    }


    /**
     * Creates a new Access Token.
     *
     * @param  array  $attributes
     * @return \Fygarciaj\Passport\Token
     */
    public function create($attributes)
    {
        $token = Passport::token();
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        return $token->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return \Fygarciaj\Passport\Token
     */
    public function find($id)
    {
        $token = Passport::token();
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        return $token->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Fygarciaj\Passport\Token|null
     */
    public function findForUser($id, $userId)
    {
        $token = Passport::token();
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        return $token->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        $token = Passport::token();
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        return $token->where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Fygarciaj\Passport\Client  $client
     * @return \Fygarciaj\Passport\Token|null
     */
    public function getValidToken($user, $client)
    {
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);
        return $client
                    ->tokens()
                    ->whereUserId($user->getKey())
                    ->where('revoked', 0)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
    }

    /**
     * Store the given token instance.
     *
     * @param  \Fygarciaj\Passport\Token  $token
     * @return void
     */
    public function save(Token $token)
    {
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id)
    {
        $token = Passport::token();
        $this->connection = Passport::getConnection();
        $token->setConnection($this->connection);
        return $token->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param  string  $id
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($id)
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Fygarciaj\Passport\Client  $client
     * @return \Fygarciaj\Passport\Token|null
     */
    public function findValidToken($user, $client)
    {
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);
        return $client->tokens()
                      ->whereUserId($user->getKey())
                      ->where('revoked', 0)
                      ->where('expires_at', '>', Carbon::now())
                      ->latest('expires_at')
                      ->first();
    }
}
