<?php

namespace Olodoc\Command;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveHtmlCommand extends Command
{
    private $config;
    private $rootPath;
    private $htmlPath;
    private $configArray = array();

    public function __construct(
        array $config
    )
    {
        $this->configArray = $config;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Remove html documentation files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->configArray['olodoc'])) {
            $output->writeln('<error>Olodoc configuration key not found in your config file.</error>');
            return Command::FAILURE;
        }
        $this->config = $this->configArray['olodoc'];
        try {
            $this->validateConfigurations();
            $this->removeHtmlFiles(); // remove all html files
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Html files removed successfully.</info>');

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    protected function validateConfigurations()
    {
        if (empty($this->config['root_path'])) {
            throw new Exception(
                "The configuration key 'root_path' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->rootPath = '/'.ltrim(rtrim($this->config['root_path'], '/'), '/');
        if (empty($this->config['html_path'])) {
            throw new Exception(
                "The configuration key 'html_path' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->htmlPath = $this->rootPath.'/'.ltrim(rtrim($this->config['html_path'], '/'), '/').'/';
    }

    protected function removeHtmlFiles()
    {
        $files = array();
        $it = new RecursiveDirectoryIterator($this->htmlPath);
        foreach (new RecursiveIteratorIterator($it) as $splFileInfo) {
            $file = $splFileInfo->getPathName();
            $parts = pathinfo($file);
            $extension = strtolower($parts['extension']);
            if ($extension == 'html' && file_exists($file)) {
                unlink($file);
            }
        }
    }

}