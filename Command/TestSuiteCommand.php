<?php

namespace WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Loader\AttributeClassLoader;

class TestSuiteCommand extends Command
{

    protected static $defaultName = 'test:all';

    protected function configure(): void
    {
        $this
            ->setDescription('Runs all tests for a suite')
            ->setHelp('This command allows run your tests for a given suite')
            ->addArgument('suite', InputArgument::REQUIRED, 'Suite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new AttributeClassLoader();
        $finder = new Finder();
        $suite = $input->getArgument('suite');

        $files = $loader
            ->load($finder
                ->files()
                ->in('src')
                ->followLinks()
                ->name('*.php')
            )->get($suite);

        if (!$files) {
            return Command::FAILURE;
        }

        foreach ($files as $file) {
            $output->writeln($file->getPathName());
        }

        return Command::SUCCESS;
    }
}