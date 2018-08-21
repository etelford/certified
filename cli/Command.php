<?php

namespace SSLGenerator;

use Symfony\Component\Process\Process;

class Command
{
	/**
	 * Make a new process and run the command
	 * 
	 * @param  string $command
	 * @return int
	 */
	public function run($command)
	{
		$process = new Process($command);
		$process->run();

		return $process->getOutput();
	}
}