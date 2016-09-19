<?php

namespace Rebing\GraphQL\Support;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Fluent;

class Field extends Fluent {

    /**
     * Override this in your queries or mutations
     * to provide custom authorization
     */
    public function authorize(array $args)
    {
        return true;
    }

    public function attributes()
    {
        return [];
    }

    public function type()
    {
        return null;
    }

    public function args()
    {
        return [];
    }

    protected function getResolver()
    {
        if(!method_exists($this, 'resolve'))
        {
            return null;
        }

        $resolver = [$this, 'resolve'];
        $authorize = [$this, 'authorize'];
        return function() use ($resolver, $authorize)
        {
            $args = func_get_args();

            // Replace the context argument with 'selects and relations'
            // $args[3] is ResolveInfo
            if(isset($args[3]))
            {
                $fields = new SelectFields($args[3], $resolver[0]->type());
                $args[2] = $fields;
            }

            // Authorize
            if(call_user_func_array($authorize, [$args[1]]) != true)
            {
                throw new HttpResponseException(new Response('Forbidden', 403));
            }

            return call_user_func_array($resolver, $args);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'args' => $this->args()
        ], $attributes);

        $type = $this->type();
        if(isset($type))
        {
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if(isset($resolver))
        {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key]:null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]);
    }
    
}
