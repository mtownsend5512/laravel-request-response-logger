<?php

return [

    /**
     * The model used to manage the database table for storing request and response logs.
     */
    'logging_model' => \Mtownsend\RequestResponseLogger\Models\RequestResponseLog::class,

    /**
     * When logging requests and responses, should the logging action be
     * passed off to the queue (true) or run synchronously (false)?
     */
    'logging_should_queue' => false,

    /**
     * If stored json should be transformed into an array when retrieved from the database.
     * Set to `false` to receive as a regular php object.
     */
    'get_json_values_as_array' => true,

];
