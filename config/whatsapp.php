<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Sender Number
    |--------------------------------------------------------------------------
    |
    | The phone number used as the sender for WhatsApp notifications sent by
    | the synchronization and transaction modules.
    |
    */

    'sender_number' => env('WA_SENDER_NUMBER', '6281234567890'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Key
    |--------------------------------------------------------------------------
    |
    | The API key used to authenticate with the WhatsApp messaging gateway
    | at https://connect.labelin.co/send-message.
    |
    */

    'api_key' => env('WA_API_KEY', ''),

];
