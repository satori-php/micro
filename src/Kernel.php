<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2017 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Micro;

use Satori\Application\KernelInterface;

/**
 * Microframework kernel.
 * Includes dependency injection container and event dispatcher.
 */
class Kernel implements KernelInterface
{
    /**
     * @var callable[] Contains services.
     *                 $this->services as callable[string] and
     *                 $this->services[$id] as callable.
     */
    private $services = [];

    /**
     * @var mixed[] Contains parameters.
     *              $this->parameters as mixed[string] and
     *              $this->parameters[$key] as mixed.
     */
    private $parameters = [];

    /**
     * @var array[] Contains subscription keys.
     *              $this->events as array[string] and
     *              $this->events[$event] as string[int] and
     *              $this->events[$event][$i] as string.
     */
    private $events = [];

    /**
     * @var callable[] Contains subscriptions.
     *                 $this->subscriptions as callable[string] and
     *                 $this->subscriptions[$callbackKey] as callable.
     */
    private $subscriptions = [];

    /**
     * Returns a service (object).
     *
     * @param string $id The unique name of the service (object).
     *
     * @throws \LogicException If the service (object) is not defined.
     *
     * @return object The service (object) instance.
     */
    public function __get(string $id): object
    {
        if (isset($this->services[$id])) {

            return $this->services[$id]($this);
        }
        throw new \LogicException(sprintf('Service (object) "%s" is not defined.', $id));
    }

    /**
     * Sets a service (object) definition.
     *
     * @param string   $id         The unique name of the service (object).
     * @param callable $definition The closure or invokable object.
     *
     * @return void
     */
    public function __set(string $id, callable $definition): void
    {
        if (ltrim($id, '_') !== $id) {
            $this->services[$id] = $definition;
        } else {
            $this->services[$id] = function (Application $container) use ($definition) {
                static $service;
                if (!isset($service)) {
                    $service = $definition($container);
                }

                return $service;
            };
        }
    }

    /**
     * Checks if a service (object) definition is set.
     *
     * @param string $id The unique name of the service (object).
     *
     * @return bool
     */
    public function __isset(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Checks if a parameter is set.
     *
     * @param string $key The unique key of the parameter.
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Returns a parameter.
     *
     * @param string $key The unique key of the parameter.
     *
     * @throws \LogicException If the parameter is not defined.
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->parameters)) {

            return $this->parameters[$key];
        }
        throw new \LogicException(sprintf('Parameter "%s" is not defined.', $key));
    }

    /**
     * Sets a parameter.
     *
     * @param string $key   The unique key of the parameter.
     * @param mixed  $value The value of the parameter.
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Removes a parameter.
     *
     * @param string $key The unique key of the parameter.
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->parameters[$key]);
    }

    /**
     * Adds a subscription.
     *
     * @param string   $event    The unique name of the event.
     * @param string   $listener The unique name of the listener.
     * @param callable $callback The closure or invokable object.
     *
     * @return void
     */
    public function subscribe(string $event, string $listener, callable $callback): void
    {
        $callbackKey = $event . ' ' . $listener;
        $this->events[$event][] = $callbackKey;
        $this->subscriptions[$callbackKey] = $callback;
    }

    /**
     * Notifies listeners about an event.
     *
     * @param string $event     The unique name of the event.
     * @param array  $arguments Arguments for event callbacks.
     *
     * @return void
     */
    public function notify(string $event, array $arguments = null): void
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $priorityKey => $callbackKey) {
                $output = $this->subscriptions[$callbackKey]($this, $arguments ?? []);
                if (isset($output['stop']) && true === $output['stop']) {
                    break;
                }
            }
        }
    }

    /**
     * Runs an application.
     *
     * @param string $id The unique name of the application engine.
     *
     * @return object
     */
    public function run(string $id): object
    {
        return $this->__get($id);
    }
}
