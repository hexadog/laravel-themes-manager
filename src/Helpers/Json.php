<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Helpers;

use Hexadog\ThemesManager\Exceptions\InvalidJsonException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class Json
{
    /**
     * The file path.
     */
    protected string $path;

    /**
     * The laravel filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * The attributes collection.
     */
    protected Collection $attributes;

    /**
     * The constructor.
     */
    public function __construct(string $path, ?Filesystem $filesystem = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem ? $filesystem : new Filesystem;
        $this->attributes = collect($this->getAttributes());
    }

    /**
     * Handle magic method __get.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Handle call to __call method.
     */
    public function __call(string $method, array $arguments = []): mixed
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        return call_user_func_array([$this->attributes, $method], $arguments);
    }

    /**
     * Handle call to __toString method.
     */
    public function __toString(): string
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
     */
    public static function make(string $path, ?Filesystem $filesystem = null): Json
    {
        return new self($path, $filesystem);
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
        $attributes = json_decode($this->getContents(), true);

        // any JSON parsing errors should throw an exception
        if (json_last_error() > 0) {
            throw new InvalidJsonException('Error processing file: ' . $this->getPath() . '. Error: ' . json_last_error_msg());
        }

        return $attributes;
    }

    /**
     * Convert the given array data to pretty json.
     */
    public function toJsonPretty(?array $data = null): false|string
    {
        return json_encode($data ? $data : $this->attributes, JSON_PRETTY_PRINT);
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
     */
    public function set(string $key, mixed $value): Json
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
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }
}
