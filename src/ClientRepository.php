<?php

namespace Fygarciaj\Passport;

use RuntimeException;
use Illuminate\Support\Str;

class ClientRepository
{

    /**
     * Assing the connection
     *
     * @var [string]
     */
    protected $connection;


    /**
     * Construct the class
     *
     * @param string $connection
     */
    public function __construct()
    {
         $this->connection = Passport::getConnection();
    }



    /**
     * Set connection for class
     *
     * @param [string] $conn
     * @return void
     */
    public function connection($conn)
    {
        $this->connection = $conn;
    }



    /**
     * Get a client by the given ID.
     *
     * @param  int  $id
     * @return \Fygarciaj\Passport\Client|null
     */
    public function find($id)
    {
        $client = Passport::client();
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int  $id
     * @return \Fygarciaj\Passport\Client|null
     */
    public function findActive($id)
    {
        // $this->connection = Passport::getConnection();

        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     *
     * @param  int  $clientId
     * @param  mixed  $userId
     * @return \Fygarciaj\Passport\Client|null
     */
    public function findForUser($clientId, $userId)
    {
        $client = Passport::client();
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);

        return $client
                    ->where($client->getKeyName(), $clientId)
                    ->where('user_id', $userId)
                    ->first();
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {

        $client = Passport::client();
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);

        return  $client->where('user_id', $userId)
                    ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeForUser($userId)
    {
        return $this->forUser($userId)->reject(function ($client) {
            return $client->revoked;
        })->values();
    }

    /**
     * Get the personal access token client for the application.
     *
     * @return \Fygarciaj\Passport\Client
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient()
    {
        if (Passport::$personalAccessClientId) {
            return $this->find(Passport::$personalAccessClientId);
        }

        $client = Passport::personalAccessClient();
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);

        if (! $client->exists()) {
            throw new RuntimeException('Personal access client not found. Please create one.');
        }

        return $client->orderBy($client->getKeyName(), 'desc')->first()->client;
    }

    /**
     * Store a new client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @param  bool  $personalAccess
     * @param  bool  $password
     * @return \Fygarciaj\Passport\Client
     */
    public function create($userId, $name, $redirect, $personalAccess = false, $password = false)
    {
        $client = Passport::client();
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);

        $client->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'secret' => Str::random(40),
            'redirect' => $redirect,
            'personal_access_client' => $personalAccess,
            'password_client' => $password,
            'revoked' => false,
        ]);

        $client->save();

        return $client;
    }

    /**
     * Store a new personal access token client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @return \Fygarciaj\Passport\Client
     */
    public function createPersonalAccessClient($userId, $name, $redirect)
    {
        return tap($this->create($userId, $name, $redirect, true), function ($client) {
            $accessClient = Passport::personalAccessClient();
            $this->connection = Passport::getConnection();
            $accessClient->setConnection($this->connection);
            $accessClient->client_id = $client->id;
            $accessClient->save();
        });
    }

    /**
     * Store a new password grant client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @return \Fygarciaj\Passport\Client
     */
    public function createPasswordGrantClient($userId, $name, $redirect)
    {
        return $this->create($userId, $name, $redirect, false, true);
    }

    /**
     * Update the given client.
     *
     * @param  Client  $client
     * @param  string  $name
     * @param  string  $redirect
     * @return \Fygarciaj\Passport\Client
     */
    public function update(Client $client, $name, $redirect)
    {
        $this->connection = Passport::getConnection();
        $client->on($this->connection)->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     *
     * @param  \Fygarciaj\Passport\Client  $client
     * @return \Fygarciaj\Passport\Client
     */
    public function regenerateSecret(Client $client)
    {
        $this->connection = Passport::getConnection();
        $client->on($this->connection)->forceFill([
            'secret' => Str::random(40),
        ])->save();

        return $client;
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id)
    {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     *
     * @param  \Fygarciaj\Passport\Client  $client
     * @return void
     */
    public function delete(Client $client)
    {
        $this->connection = Passport::getConnection();
        $client->setConnection($this->connection);
        $client->on($this->connection)->tokens()->update(['revoked' => true]);

        $client->on($this->connection)->forceFill(['revoked' => true])->save();
    }
}
