<?php

namespace shortcut;

class Api
{
    protected $api_key = null;
    const SHORTCUT_API_URL = 'https://api.app.shortcut.com/api/v3/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Perfoms a GET request to the Shortcut API using CURL.
     *
     * @param string $url Endpoint URL to poll.
     * @throws \Exception
     * @return object|bool JSON object on success or false on fail.
     */
    public function get($url)
    {
        $curl = $this->makeShortcutCurl($url);

        try {
            $response = curl_exec($curl);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        curl_close($curl);
        return json_decode($response);
    }

    /**
     * Creates a CURL session and adds headers.
     *
     * @param string $url Endpoint URL to poll.
     * @return \CurlHandle
     */
    private function makeShortcutCurl($url)
    {
        $headers = [
            'Shortcut-Token: ' . $this->api_key,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return $ch;
    }

    /**
     * Get all stories from a specified integration.
     *
     * @param integer $id Iteration ID.
     * @return object|bool JSON object on success or false on fail.
     */
    public function getStoriesFromIteration($id)
    {
        $url = self::SHORTCUT_API_URL . "iterations/$id/stories";
        return $this->get($url);
    }

    /**
     * Get all story history events from a specified story.
     *
     * @param integer $id Story ID.
     * @return object|bool JSON object on success or false on fail.
     */
    public function getStoryHistory($id)
    {
        $url = self::SHORTCUT_API_URL . "stories/$id/history";
        return $this->get($url);
    }

    public function getIteration($id)
    {
        $url = self::SHORTCUT_API_URL . "iterations/$id";
        return $this->get($url);
    }

    public function getMembers()
    {
        $url = self::SHORTCUT_API_URL . "members";
        return $this->get($url);
    }
}
