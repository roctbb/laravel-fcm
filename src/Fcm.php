<?php

namespace Kawankoding\Fcm;

/**
 * Class Fcm
 * @package Kawankoding\Fcm
 */
class Fcm
{
    const ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    protected $recipients;
    protected $topic;
    protected $data;
    protected $notification;
    protected $timeToLive;
    protected $priority;
    protected $package;
    protected $apns;
    protected $android;
    protected $payload;

    protected $serverKey;

    protected $responseLogEnabled = false;

    public function __construct($serverKey)
    {
        $this->serverKey = $serverKey;
        $this->apns = null;
        $this->android = null;
    }

    public function to($recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }

    public function apns($apns) {
        $this->apns = $apns;
        return $this;
    }

    public function android($android) {
        $this->android = $android;
        return $this;
    }

    public function toTopic($topic)
    {
        $this->topic = $topic;

        return $this;
    }

    public function data($data = [])
    {
        $this->data = $data;

        return $this;
    }

    public function notification($notification = [])
    {
        $this->notification = $notification;

        return $this;
    }

    public function priority(string $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function timeToLive($timeToLive)
    {
        if ($timeToLive < 0) {
            $timeToLive = 0; // (0 seconds)
        }
        if ($timeToLive > 2419200) {
            $timeToLive = 2419200; // (28 days)
        }

        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    public function getSentPayload() {
        return $this->payload;
    }

    public function enableResponseLog($enable = true)
    {
        $this->responseLogEnabled = $enable;

        return $this;
    }

    public function send()
    {
        $payloads = [
            'content_available' => true,
            'mutable_content' => true,
            'priority' => isset($this->priority) ? $this->priority : 'high',
            'data' => $this->data,
            'notification' => $this->notification,
        ];

        if ($this->android) {
            $payloads['android'] = $this->android; 
        }

        if ($this->apns) {
            $payloads['apns'] = $this->apns; 
        }
        
        if(!empty($this->package))
        {
            $payloads['restricted_package_name'] = $this->package;
        }

        if ($this->topic) {
            $payloads['to'] = "/topics/{$this->topic}";
        } else {
            $payloads['registration_ids'] = $this->recipients;
        }

        if ($this->timeToLive !== null && $this->timeToLive >= 0) {
            $payloads['time_to_live'] = (int) $this->timeToLive;
        }

        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json',
        ];

        $this->payload = $payloads;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloads));
        $response = curl_exec($ch);

        if ($this->responseLogEnabled) {
            logger('laravel-fcm', ['response' => $response]);
        }

        $result = json_decode($response, true);
        curl_close($ch);

        return $result;
    }

    
}
