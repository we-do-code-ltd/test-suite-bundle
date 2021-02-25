## WeDoCode TestSuite Bundle

### Project goal: 

Is to provide a single command to run common test tools on the whole code base, on specified files that contains a given
suite attribute (bonus goal: or on a built in git diff suite `git-diff`).

Code samle in project:

```php
<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use WeDoCode\Bundle\WeDoCodeTestSuiteBundle\Attribute\Suite;

#[Suite(['controller', 'lucky'])]
class LuckyController
{
    #[Route('/lucky/number', name: 'luck_number', methods: ['GET', 'HEAD'])]
    public function number(): JsonResponse
    {
        $number = random_int(0, 100);
        return new JsonResponse(
            ['number' => $number]
        );
    }
}
```

### Commands: 

`console test:all --suite [suite]` command, where you can specify a suite to filter down or just run all tests for everything

`console test:feature --suite [suite]` command where you can run feature tests (behat)

`console test:unit --suite [suite]` command to run unit tests, this will include infection testing

`console test:coding-standards --suite [suite]` command to run coding standards (phpcs)

`console test:code-quality --suite [suite]`  command to run phpmd and phpstan

### Configuration

Will expose certain customizations as symfony yml files, 
but all commands will use / manipulate the the original tool config configurations. 

Example: 
- for PHP unit we will add a testsuite node to the project phpunit.xml with a list of files in the specified testsuite.

#### Configuration for individual tools: 
- You will need to configure the individual tools to your project needs.
- We are providing some basic example configuration for each tool, these are `.dist` files in bundle root directory you can copy these
to your project root and loose the `.dist` extension. This way you can start testing with our default rule sets.   
