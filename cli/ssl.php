#!/usr/bin/env php
<?php
/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    require __DIR__.'/../../../autoload.php';
}

use SSLGenerator\Make;
use Symfony\Component\Console\Output\OutputInterface;

$app = new Silly\Application();

$app->command('make domain [-w|--wildcard]', function ($domain, $wildcard, OutputInterface $output) {
	// Make a CSR
	$make = new Make();
	$make->secure($domain);
    // $output->writeln($domain);
    // echo $domain);
});

$app->run();