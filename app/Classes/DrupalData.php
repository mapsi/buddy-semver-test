<?php

namespace App\Classes;

use App\Exceptions\DrupalDataException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Iterator;
use Illuminate\Support\Str;

class DrupalData implements Iterator
{
    private $client;

    private $entity_type;

    private $bundle;

    private $filters;

    private $fields;

    private $position = 0;

    private $items;

    private $included;

    private $next_link;

    private $error_counter = 0;

    /**
     * @param string The URL to send request to.
     */
    public function __construct(string $endpoint, string $entity_type, string $bundle, \GuzzleHttp\Cookie\CookieJar $cookies)
    {


        $this->client = new Client([
            'base_uri' => $endpoint,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'cookies' => $cookies,
        ]);
        $this->entity_type = $entity_type;
        $this->bundle = $bundle;
    }

    /**
     * only allows one filter why is not known
     *
     * @param string $name     The name of the filter
     * @param string $field    The field to filter.
     * @param mixed  $value    The value to filter against.
     * @param string $operator The comparison operator.
     * @return $this
     */
    public function addCustomFilter(string $name, string $field, $value, string $operator = '=')
    {
        $this->filters = [
            $name => [
                'condition' => [
                    'path' => $field,
                    'operator' => $operator,
                    'value' => $value,
                ],
            ],
        ];

        return $this;
    }

    /**
     * only allows one filter why is not known
     *
     * @param string $field    The field to filter.
     * @param mixed  $value    The value to filter against.
     * @param string $operator The comparison operator.
     * @return $this
     */
    public function addFilter(string $field, $value, string $operator = '=')
    {
        $this->filters = [
            $field => [
                'operator' => $operator,
                'value' => $value,
            ],
        ];

        return $this;
    }

    /**
     * @param string $field    The field to filter.
     * @param mixed  $value    The value to filter against.
     * @param string $operator The comparison operator.
     * @return $this
     */
    public function setFieldFilter(string $field, $value, string $operator = '=')
    {
        $this->filters[$field] = [
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param array $fields The field names we want included.
     * @return $this
     */
    public function withFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * {@inheritdoc }
     */
    public function rewind()
    {
        $options = [];

        if ($this->fields) {
            $options['query']['include'] = implode(',', $this->fields);
        }

        if ($this->filters) {
            $options['query']['filter'] = $this->filters;
        }

        $this->fetch($this->entity_type . '/' . $this->bundle, $options);
    }

    /**
     * {@inheritdoc }
     * We want to hydrate the relationships with the entity from the included array.
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * {@inheritdoc }
     */
    public function key()
    {
        return $this->items[$this->position]['id'];
    }

    /**
     * {@inheritdoc }
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * {@inheritdoc }
     */
    public function valid()
    {
        if ($this->position >= count($this->items)) { // If we've gone past the number of items in the working set.
            if (! $this->next_link) { // If there are no more pages
                return false;
            }

            $this->fetch($this->next_link);
        }

        return array_key_exists($this->position, $this->items);
    }

    protected function fetch(string $url, array $options = [])
    {
        try {
            $response = $this->client->get($url, $options);

            if (app_is_running_in_console_with_debug()) {
                logger($response->getBody() . '', ['normal operations']);
            }
        } catch (RequestException $exception) {
            $this->error_counter++;

            if ($this->error_counter >= 3) {
                logger($exception->getResponse()->getBody() . '', ['erroring out']);
                // Caveat: We only provide the last error. Tt's probably the same.
                throw new DrupalDataException('Could not fetch Drupal data after ' . $this->error_counter . ' ' . Str::plural('attempt', $this->error_counter) . ': ' . $exception->getMessage());
            }

            sleep(5);

            $this->fetch($url, $options);

            return;
        }

        $this->error_counter = 0;

        $data = json_decode($response->getBody(), true);

        if (array_key_exists('included', $data)) {
            $included = array_combine(array_column($data['included'], 'id'), $data['included']);

            foreach ($included as &$element) {
                $element = static::populateRelations($included, $element);
            }

            foreach ($data['data'] as &$item) {
                $item = static::populateRelations($included, $item);
            }
        }

        $this->items = $data['data'];
        $this->next_link = $data['links']['next'] ?? null;
        $this->position = 0;
    }

    protected static function populateRelations($included, $element)
    {
        if (array_key_exists('relationships', $element)) {
            foreach ($element['relationships'] as &$relationship) {
                if (! empty($relationship['data'])) {
                    if (isset($relationship['data']['id'])) {
                        $uuid = $relationship['data']['id'];

                        if (array_key_exists($uuid, $included)) {
                            $relationship['data'] = static::populateRelations($included, $included[$uuid]);
                        }
                    } else {
                        foreach ($relationship['data'] as &$item) {
                            $uuid = $item['id'];

                            if (array_key_exists($uuid, $included)) {
                                $item = static::populateRelations($included, $included[$uuid]);
                            }
                        }
                    }
                }
            }
        }

        return $element;
    }
}
