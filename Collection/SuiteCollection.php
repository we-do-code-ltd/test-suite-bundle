<?php

/*
 * This file is part of the WeDoCode TestSuite bundle.
 *
 * (c) Tamas Dobo <tom@wedocode.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Collection;

use ArrayIterator;

use Countable;
use IteratorAggregate;
use SplFileInfo;

use function count;

/**
 * SuitCollection contains the identified Suits and the files associated with a suite.
 *
 * @author Tamas Dobo <tom@wedocode.co.uk>
 */
class SuiteCollection implements IteratorAggregate, Countable
{
    private array $suites = [];

    public function getIterator()
    {
        return new ArrayIterator($this->all());
    }

    public function count()
    {
        return count($this->suites);
    }

    public function add(string $suite, SplFileInfo $file): self
    {
       $this->suites[$suite][] = $file;
       return $this;
    }

    public function all(): array
    {
        return $this->suites;
    }

    public function get(string $suite): ?array
    {
        return $this->suites[$suite] ?? null;
    }
}
