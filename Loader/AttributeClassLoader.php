<?php

/*
 * This file is part of the WeDoCode TestSuite bundle.
 *
 * (c) Tamas Dobo <tom@wedocode.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Loader;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Attribute\Suite;
use WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Collection\SuiteCollection;

use function dd;
use function file_get_contents;
use function sprintf;
use function token_get_all;

/**
 * AttributeClassLoader loads suite information from a PHP class.
 *
 * @author Tamas Dobo <tom@wedocode.co.uk>
 */
class AttributeClassLoader
{
    public function load(Finder $files): SuiteCollection
    {
        $collection = new SuiteCollection();
        foreach ($files as $file) {
            $class = $this->findClass($file);
            $reflection = new ReflectionClass($class);

            $attributes = $reflection->getAttributes();

            foreach ($attributes as $attribute) {
                if ($attribute->getName() === Suite::class) {
                    foreach ($attribute->newInstance()->getSuites() as $suite) {
                        $collection->add($suite, $file);
                    }
                }
            }
        }
        return $collection;
    }

    /**
     * Returns the full class name for the first class in the file.
     */
    protected function findClass(string $file): ?string
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($file));

        if (1 === \count($tokens) && \T_INLINE_HTML === $tokens[0][0]) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?', $file));
        }

        $nsTokens = [\T_NS_SEPARATOR => true, \T_STRING => true];
        if (\defined('T_NAME_QUALIFIED')) {
            $nsTokens[\T_NAME_QUALIFIED] = true;
        }

        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];

            if (!isset($token[1])) {
                continue;
            }

            if (true === $class && \T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && isset($nsTokens[$token[0]])) {
                $namespace = $token[1];
                while (isset($tokens[++$i][1], $nsTokens[$tokens[$i][0]])) {
                    $namespace .= $tokens[$i][1];
                }
                $token = $tokens[$i];
            }

            if (\T_CLASS === $token[0]) {
                // Skip usage of ::class constant and anonymous classes
                $skipClassToken = false;
                for ($j = $i - 1; $j > 0; --$j) {
                    if (!isset($tokens[$j][1])) {
                        break;
                    }

                    if (\T_DOUBLE_COLON === $tokens[$j][0] || \T_NEW === $tokens[$j][0]) {
                        $skipClassToken = true;
                        break;
                    } elseif (!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT])) {
                        break;
                    }
                }

                if (!$skipClassToken) {
                    $class = true;
                }
            }

            if (\T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return null;
    }
}