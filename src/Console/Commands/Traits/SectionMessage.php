<?php

namespace Hexadog\ThemesManager\Console\Commands\Traits;

trait SectionMessage
{
	public function sectionMessage($title, $message, $style = 'info')
	{
		$formatter = $this->getHelperSet()->get('formatter');
		$formattedLine = $formatter->formatSection(
			$title,
			$message,
			$style
		);
		$this->line($formattedLine);
	}
}
