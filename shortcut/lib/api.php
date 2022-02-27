<?php

namespace shortcut;

class Api
{
    protected $api_key = null;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function get($url)
    {   
        // curl -X GET \ -H "Content-Type: application/json" \ -H "Shortcut-Token: 621b5b1d-719d-461f-a212-a4731bbd4668" -L "https://api.app.shortcut.com/api/v3/stories/20444"
        $curl = $this->makeShortcutCurl($url);

        try {
            $response = curl_exec($curl);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return json_decode($response);
    }

    private function makeShortcutCurl($url)
    {
        $headers = [
            'Shortcut-Token: ' . $this->api_key,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        return $ch;
    }
}