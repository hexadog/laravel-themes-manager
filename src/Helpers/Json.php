<?php

namespace Hexadog\ThemesManager\Helpers;

use Hexadog\ThemesManager\Exceptions\InvalidJsonException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class Json
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $path;

    /**
     * The laravel filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * The attributes collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $attributes;

    /**
     * The constructor.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(string $path, Filesystem $filesystem = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->attributes = collect($this->getAttributes());
    }

    /**
     * Handle magic method __get.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Handle call to __call method.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments = [])
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        return call_user_func_array([$this->attributes, $method], $arguments);
    }

    /**
     * Handle call to __toString method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getContents();
    }

    /**
     * Get filesystem.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set filesystem.
     */
    public function setFilesystem(Filesystem $filesystem): Json
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set path.
     */
    public function setPath(string $path): Json
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Make new instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public static function make(string $path, Filesystem $filesystem = null): Json
    {
        return new static($path, $filesystem);
    }

    /**
     * Get file content.
     */
    public function getContents(): string
    {
        return $this->filesystem->get($this->getPath());
    }

    /**
     * Get file contents as array.
     *
     * @throws InvalidJsonException
     */
    public function getAttributes(): array
    {
        $attributes = json_decode($this->getContents(), 1);

        // any JSON parsing errors should throw an exception
        if (json_last_error() > 0) {
            throw new InvalidJsonException('Error processing file: ' . $this->getPath() . '. Error: ' . json_last_error_msg());
        }

        return $attributes;
    }

    /**
     * Convert the given array data to pretty json.
     *
     * @param  array  $data
     * @return false|string
     */
    public function toJsonPretty(array $data = null)
    {
        return json_encode($data ?: $this->attributes, JSON_PRETTY_PRINT);
    }

    /**
     * Update json contents from array data.
     */
    public function update(array $data): bool
    {
        $this->attributes = collect(array_merge($this->attributes->toArray(), $data));

        return $this->save();
    }

    /**
     * Set a specific key & value.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function set($key, $value): Json
    {
        $attributes = $this->attributes->toArray();

        Arr::set($attributes, $key, $value);

        $this->attributes = collect($attributes);

        return $this;
    }

    /**
     * Save the current attributes array to the file storage.
     */
    public function save(): bool
    {
        return (bool) $this->filesystem->put($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Get the specified attribute from json file.
     *
     * @param  null  $default
     * @param  mixed  $key
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }
}
