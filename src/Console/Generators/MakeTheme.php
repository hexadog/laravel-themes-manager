<?php

namespace Hexadog\ThemesManager\Console\Generators;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Hexadog\ThemesManager\Console\Commands\Traits\BlockMessage;
use Hexadog\ThemesManager\Console\Commands\Traits\SectionMessage;

class MakeTheme extends Command
{
    use BlockMessage;
    use SectionMessage;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Theme';

    /**
     * Config.
     *
     * @var \Illuminate\Support\Facades\Config
     */
    protected $config;
    
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create Theme Info.
     *
     * @var array
     */
    protected $theme = [];

    /**
     * Theme folder path.
     *
     * @var string
     */
    protected $themePath;
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Repository $config, Filesystem $filesystem)
    {
        $this->config = $config;
        $this->files = $filesystem;

        parent::__construct();
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->themePath = $this->config->get('themes-manager.directory', 'themes');
        
        $this->sectionMessage('Themes Manager', 'Create new Theme');
        if ($this->validateName()) {
            $this->askAuthor();
            $this->askDescription();
            $this->askVersion();
            $this->askParent();

            try {
                $this->generateTheme();

                $this->sectionMessage('Themes Manager', 'Theme successfully created');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }
    
    /**
     * Validate theme name provided.
     */
    protected function validateName()
    {
        $this->askName();

        if (Str::contains($this->theme['name'], '\\')) {
            $nameParts = explode('\\', str_replace('\\\\', '\\', $this->theme['name']));
            if (count($nameParts) === 2) {
                $this->theme['vendor'] = mb_strtolower($nameParts[0]);
                $this->theme['name'] = Str::kebab($nameParts[1]);
            } else {
                // ask for vendor
                $this->askVendor();
                $this->theme['name'] = Str::kebab($this->theme['name']);
            }
        } else {
            if (Str::contains($this->theme['name'], '/')) {
                list($vendor, $name) = explode('/', $this->theme['name']);
                $this->theme['vendor'] = mb_strtolower($vendor);
                $this->theme['name'] = Str::kebab($name);
            } else {
                $this->askVendor();
                $this->theme['name'] = Str::kebab($this->theme['name']);
            }
        }

        if (ThemesManager::has($this->theme['name'])) {
            $this->error("Theme with name {$this->theme['vendor']}/{$this->theme['name']} already exists!");

            return false;
        }

        return true;
    }

    /**
     * Generate Theme structure in target directory.
     *
     * @return void
     */
    private function generateTheme()
    {
        $this->sectionMessage('Files generation', 'start files generation process...');

        $basepath = base_path($this->themePath);

        $directory = $basepath . DIRECTORY_SEPARATOR . $this->theme['vendor'] . DIRECTORY_SEPARATOR . $this->theme['name'];

        /**
         * Make directory
         */
        if ($this->files->isDirectory($directory)) {
            throw new Exception("Theme {$this->theme['name']} already exists");
        } else {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $source = __DIR__ . '/../../../resources/stubs/_folder-structure';

        $this->files->copyDirectory($source, $directory, null);

        /**
         * Replace files placeholder
         */
        $files = $this->files->allFiles($directory);
        foreach ($files as $file) {
            $contents = $this->replacePlaceholders($file);
            $filePath = $directory . DIRECTORY_SEPARATOR . $file->getRelativePathname();

            $this->files->put($filePath, $contents);
        }
    }

    /**
     * Replace placeholders in generated file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return string
     */
    protected function replacePlaceholders($file)
    {
        $this->sectionMessage('File generation', "{$file->getPathName()}");

        $find = [
            'DummyAuthorName',
            'DummyAuthorEmail',
            'DummyDescription',
            'DummyName',
            'DummyParent',
            'DummyVendor',
            'DummyVersion'
        ];

        $replace = [
            Str::title(Arr::get($this->theme, 'author-name', '')),
            Arr::get($this->theme, 'author-email', ''),
            Arr::get($this->theme, 'description', ''),
            Arr::get($this->theme, 'name', ''),
            Arr::get($this->theme, 'parent', ''),
            Arr::get($this->theme, 'vendor', ''),
            Arr::get($this->theme, 'version', '1.0'),
        ];

        return str_replace($find, $replace, $file->getContents());
    }

    /**
     * Ask for theme author information.
     * Notice: if value is set in themes-manager.composer.author.name and themes-manager.composer.author.email config value
     * then this value will be used.
     *
     * @return void
     */
    protected function askAuthor()
    {
        $this->theme['author-name'] = $this->config->get('themes-manager.composer.author.name') ?? $this->ask('Author name');
        $this->theme['author-email'] = $this->config->get('themes-manager.composer.author.email') ?? $this->ask('Author email');
    }

    /**
     * Ask for theme description.
     *
     * @return void
     */
    protected function askDescription()
    {
        $this->theme['description'] = $this->ask('Description');
    }

    /**
     * Ask for theme name.
     *
     * @return void
     */
    protected function askName()
    {
        while (empty(Arr::get($this->theme, 'name', null))) {
            $this->theme['name'] = $this->ask('Theme Name');
            
            if (ThemesManager::has($this->theme['name'])) {
                $this->error("Theme with name {$this->theme['name']} already exists!");
                
                unset($this->theme['name']);
            }
        }
    }

    /**
     * Ask for parent theme name.
     */
    protected function askParent()
    {
        if ($this->confirm('Is it a child theme?')) {
            $this->theme['parent'] = $this->ask('Parent theme name');
            $this->theme['parent'] = mb_strtolower($this->theme['parent']);
        }
    }

    /**
     * Ask for theme vendor.
     * Notice: if value is set in themes-manager.composer.vendor config value
     * then this value will be used.
     *
     * @return void
     */
    protected function askVendor()
    {
        $this->theme['vendor'] = mb_strtolower($this->config->get('themes-manager.composer.vendor') ?? $this->ask('Vendor name'));
    }

    /**
     * Ask for theme version.
     */
    protected function askVersion()
    {
        $this->theme['version'] = $this->ask('Version number');
    }
}
