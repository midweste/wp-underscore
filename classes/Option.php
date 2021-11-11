<?php

namespace _;

class Option
{
    private $name;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public static function create(string $name)
    {
        return new self($name);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function default()
    {
        return [];
    }

    public function exists($site_wide = false)
    {
        global $wpdb;
        return $wpdb->query("SELECT * FROM " . ($site_wide ? $wpdb->base_prefix : $wpdb->prefix) . "options WHERE option_name ='{$this->name}' LIMIT 1");
    }

    public function clear(): bool
    {
        $save = $this->save($this->default());
        return (is_null($save)) ? false : true;
    }

    public function save($value)
    {
        $current = $this->load();
        if ($value == $current) {
            return $value;
        }
        $result = update_option($this->getName(), $value, false);
        if ($result === false) {
            throw new \Exception(sprintf('Option %s could not be saved', $this->getName()));
        }
        return $value;
    }

    public function set(string $key, $value): self
    {
        $options = $this->load();
        $options[$key] = $value;
        $this->save($options);
        return $this;
    }

    public function get(string $key, $default = null)
    {
        $options = $this->load();
        $value = (!isset($options[$key])) ? $default : $options[$key];
        return $value;
    }

    public function load()
    {
        return get_option($this->getName(), $this->default());
    }

    public function findByValue(string $field, $value): ?array
    {
        $data = $this->load();
        foreach ($data as $record) {
            if ($record[$field] == $value) {
                return $record;
            }
        }
        return null;
    }
}
