<?php

namespace Hexadog\ThemesManager\Console\Generators;

use Exception;
use Hexadog\ThemesManager\Console\Commands\Traits\BlockMessage;
use Hexadog\ThemesManager\Console\Commands\Traits\SectionMessage;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

	protected function replacePlaceholders($file)
	{
		$this->sectionMessage('File generation', "{$file->getPathName()}");

		$find = [
			'DummyAuthor',
			'DummyDescription',
			'DummyName',
			'DummyParent',
			'DummyVendor',
			'DummyVersion'
		];

		$replace = [
			Str::title(Arr::get($this->theme, 'author', '')),
			Str::title(Arr::get($this->theme, 'description', '')),
			Arr::get($this->theme, 'name', ''),
			Arr::get($this->theme, 'parent', ''),
			Arr::get($this->theme, 'vendor', ''),
			Arr::get($this->theme, 'version', '1.0'),
		];

		return str_replace($find, $replace, $file->getContents());
	}

	protected function askAuthor()
	{
		$this->config['author'] = $this->ask('Author name');
	}

	protected function askDescription()
	{
		$this->config['description'] = $this->ask('Description');
	}

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

	protected function askParent()
	{
		if ($this->confirm('Is it a child theme?')) {
			$this->theme['parent'] = $this->ask('Parent theme name');
			$this->theme['parent'] = strtolower($this->theme['parent']);
		}
	}

	protected function askVendor()
	{
		$this->theme['vendor'] = mb_strtolower($this->ask('Vendor name'));
	}

	protected function askVersion()
	{
		$this->theme['version'] = $this->ask('Version number');
	}
}
