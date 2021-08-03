<?php

namespace Mtownsend\RequestResponseLogger\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ArrayOrText implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value = null, $attributes = [])
    {
        if ($this->isJson($value)) {
            $value = json_decode($value, config('log-requests-and-responses.get_json_values_as_array'));
        }
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value = null, $attributes = [])
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return $value;
    }

    /**
     * Checks if the string is valid json.
     *
     * @param  string  $string
     * @return boolean
     */
    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
