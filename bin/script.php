<?php

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

if ($argc < 2) {
    die('No command specified');
}

$command = $argv[1];
$rootVendorDir = dirname(dirname(dirname(__DIR__)));

// Get additional arguments
$args = array_slice($argv, 2);

// Helper function to get platform-specific path
function getPlatformPath($path) {
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

// Helper function to execute git command
function getGitDiffFiles() {
    $output = [];
    $returnVar = 0;
    exec('git diff --name-only origin/pre-release...', $output, $returnVar);
    
    if ($returnVar !== 0) {
        return [];
    }
    
    return array_filter($output, function($file) {
        return preg_match('/\.php$/', $file);
    });
}

// Helper function to execute command
function executeCommand($command) {
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    if ($returnVar !== 0) {
        echo "Command failed with exit code: $returnVar\n";
        return false;
    }
    
    return true;
}

switch ($command) {
    case 'phpcs':
        $phpcsPath = getPlatformPath("$rootVendorDir/bin/phpcs");
        $argsList = implode(' ', array_map(function($arg) {
            return '"' . $arg . '"';
        }, $args));
        executeCommand("php \"$phpcsPath\" -s --standard=Uncanny-Automator --warning-severity=1 $argsList");
        break;
        
    case 'phpcsOnSave':
        $phpcsPath = getPlatformPath("$rootVendorDir/bin/phpcs");
        $argsList = implode(' ', array_map(function($arg) {
            return '"' . $arg . '"';
        }, $args));
        executeCommand("php \"$phpcsPath\" -s -v --standard=Uncanny-Automator --warning-severity=1 --report=full $argsList");
        break;
        
    case 'phpcbf':
        $phpcbfPath = getPlatformPath("$rootVendorDir/bin/phpcbf");
        $argsList = implode(' ', array_map(function($arg) {
            return '"' . $arg . '"';
        }, $args));
        executeCommand("php \"$phpcbfPath\" -s --standard=Uncanny-Automator $argsList");
        break;
        
    case 'phpcs:pr':
        $files = getGitDiffFiles();
        if (!empty($files)) {
            $phpcsPath = getPlatformPath("$rootVendorDir/bin/phpcs");
            $filesList = implode(' ', array_map(function($file) {
                return '"' . $file . '"';
            }, $files));
            executeCommand("php \"$phpcsPath\" -s --standard=Uncanny-Automator --warning-severity=1 $filesList");
        } else {
            echo "No PHP files to lint.\n";
        }
        break;
        
    case 'phpcbf:pr':
        $files = getGitDiffFiles();
        if (!empty($files)) {
            $phpcbfPath = getPlatformPath("$rootVendorDir/bin/phpcbf");
            $filesList = implode(' ', array_map(function($file) {
                return '"' . $file . '"';
            }, $files));
            executeCommand("php \"$phpcbfPath\" -s --standard=Uncanny-Automator $filesList");
        } else {
            echo "No PHP files to fix.\n";
        }
        break;
        
    case 'unit-tests':
        $codeceptPath = getPlatformPath("$rootVendorDir/bin/codecept");
        executeCommand("php \"$codeceptPath\" run wpunit --skip-group Full_Coverage");
        break;
        
    case 'unit-tests-full':
        $codeceptPath = getPlatformPath("$rootVendorDir/bin/codecept");
        executeCommand("php \"$codeceptPath\" run wpunit");
        break;
        
    case 'unit-tests:coverage':
        $codeceptPath = getPlatformPath("$rootVendorDir/bin/codecept");
        executeCommand("php \"$codeceptPath\" run wpunit --coverage --coverage-html --xml");
        break;
        
    default:
        die("Unknown command: $command\n");
} 