<?php

namespace WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Command;

use PHPUnit\TextUI\XmlConfiguration\PHPUnit;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Loader\AttributeClassLoader;

use function array_map;
use function file_put_contents;
use function implode;
use function passthru;
use function sprintf;

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
        //We will extract things out from here and start imroving on them once we have one happy path working.

        // gathering the files we work on
        $loader = new AttributeClassLoader();
        $suite = $input->getArgument('suite');

        $testFiles = $loader
            ->load((new Finder())
                ->files()
                ->in('tests')
                ->followLinks()
                ->name('*.php')
                ->notName('bootstrap.php')
            )->get($suite);

        $sourceFiles = $loader
            ->load((new Finder())
                ->files()
                ->in('src')
                ->followLinks()
                ->name('*.php')
            )->get($suite);

        if (!$testFiles) {
            $output->writeln('No files in the testsuite');
            return Command::FAILURE;
        }

        //writing phpunit xml
        $document = (new \DOMDocument());
        $document->load('phpunit.xml');

        $rootElement = $document->getElementsByTagName('phpunit')->item(0);

        if ($document->getElementsByTagName('phpunit')->count() !== 1) {
            $output->writeln('Corrupt phpunit.xml. Missing phpunit element');
            Command::FAILURE;
        }

        //adding coverage src files to xml
        $suites = $document->getElementsByTagName('testsuites');
        if ($suites->count() !== 1) {
            $output->writeln('Corrupt phpunit.xml. Missing phpunit element');
            Command::FAILURE;
        }

        $testSuite = $document->createElement('testsuite');
        $testSuite->setAttribute('name', $suite);


        /** @var \SplFileInfo $file */
        foreach ($testFiles as $file) {
            $testSuite->appendChild($document->createElement('file', $file->getRealPath()));
        }

        $suites->item(0)->appendChild($testSuite);

        //adding coverage src files to xml
        if ($document->getElementsByTagName('coverage')->count() > 0) {
            foreach($document->getElementsByTagName('coverage') as $element) $rootElement->removeChild($element);
        }

        $coverage = $document->createElement('coverage');
        $coverage->setAttribute('cacheDirectory', 'var/cache/phpunit/code-coverage');
        $coverage->setAttribute('processUncoveredFiles', 'true');
        $include = $document->createElement('include');

        foreach ($sourceFiles as $file) {
            $include->appendChild($document->createElement('file', $file->getRealPath()));
        }

        $coverage->appendChild($include);
        $rootElement->appendChild($coverage);

        $phpunitFile = '/tmp/phpunit.xml';
        file_put_contents($phpunitFile, $document->saveXml());

        passthru(sprintf('vendor/bin/phpunit \
            --coverage-text \
            --coverage-xml=var/coverage/coverage-xml \
            --coverage-html=var/coverage/html \
            --log-junit=var/coverage/junit.xml \
            --testsuite=%s \
            --configuration=%s', $suite, $phpunitFile)
        );

        $fileList = implode(',', array_map(fn($file) => $file->getRealPath(), $sourceFiles));

        passthru(sprintf('vendor/bin/infection \
                  --only-covered \
                  --min-covered-msi=100 \
                  -j$(nproc) \
                  --filter=%s \
                  --ignore-msi-with-no-mutations \
                  --coverage=var/coverage/ \
                  --skip-initial-tests \
                  --test-framework-options="--configuration=%s"
            ', $fileList, $phpunitFile));

        return Command::SUCCESS;
    }
}