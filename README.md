#ASIS 0.1.0
__Do you want to cover your legacy code with thousands of automated unit tests in just few lines of code?!..__

##Introduction

Asis means "as is", and the Asis project is inspired by [ApprovalTests](http://approvaltests.sourceforge.net/) project and shares its key concepts.

As metioned in [this article](http://php.dzone.com/articles/approval-tests-alternative) currently ApprovalTests concepts works best when you deal with 2 things: 
- UI design;
- legacy code.

__Asis is about legacy code.__

Often you have a huge legacy code project where you have no tests at all, but you have to change code to implement a new feature, or refactor. The interesting thing about legacy code is  - It works! It works for years, no matter how it is written. And this is a very great advantage of that code. With approvals, with only one test you can get all possible outputs (HTML, XML, JSON, SQL or whatever output it could be) and approve, because you know - it works! After you have complete such a test and approved the result, you are really much safer with a refactoring, since now you "locked down" all existing behavior.

Asis tool is exactly about mantaining the legacy code through creating and running characterization tests automatically. 

ApprovalTests allows you to record and approve every possible output of any module, meanwhile the main goal of Asis is to maximally automate this process. 

##Main concept

The main idea is the following: while user or tester is using your product (for example, Web site) the Asis tool records the function calls which are performed, the sets of arguments which are passed to the function and the received output. Output can be any, starting from strings, integers, HTML, JSON and finishing with serialized objects with complex internal structure. We don't care what we receive - we just record it and approve as correct result, because we know that we are working with the stable release version.

So just using your product or surfing the Web-site , user or tester records hundreds or thousands different characterization tests!

Then when we are going to start the refactoring, we switch off the tests recording and make changes to the codebase.

Now periodically running recorded tests we can ensure that the functionality of stable version is not ruined at least in the part covered by tests we had generated eariler.

_(Thus we injected tests recording with primitive stack backtracing in Zend_Db_Table class of our Zend Framework application and receive tests suit with hundreds of variuos tests for our object-relational mapping layer in several minutes of just web-site surfing!)_

This concept is far away from being the "silver bullet" of unit testing. One of the reasons is the following: it does not generate any "dirty" tests with broken input which are probably even more important than simplest tests with just regulary input data.

Currently we chose PHP language and implemented very simple set of functionality, but I am pretty sure that this concept may be usefull in other programming languages and environments.

##Usage

The recording of test is performed in one line of code, so for simple class
 
```php
// Simple class that we want to test
class Foo {   
    public function bar($x) {       
       return $x * $x;
    }
}
```

just create `Asis_Logger` object (for the simplicity we bind project directory root linked to the current directory)

```php
$logger = new Asis_Logger(
    array("applicationPath" => dirname(__FILE__))
);
```

and pass class name, public method name and input argument to its log method:

```php
$logger->log('Foo', 'bar', 2);
```

Now the sample input data is in the `tests/inputs/sample.xml`.

Then create a tester class

```php
$tester = new Asis_Tester();
// Note: all classes which are tested should be available here
// either with require_once-s or with autoloading
```

Run tests (first pass - recording outputs for recently added tests) 

```php
$tester->run();
// serialized output for just added test (value 4 for our case) is now
// in tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.received.txt
```

Approve output

```php
$tester->approve();
// tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.received.txt is renamed in
// tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.approved.txt
```

Run tests again to check the results (second pass - unit testing mode)

```php
$tester->run();
// 1 tests executed
// 0 assertions
```

P.S.: We consciously do not use PHP 5.3+ namespaces in library (we use Zend Framefork 1.x class naming convention instead) cause we target on legacy environments were even older versions of PHP interpreter can be installed.

##Logger injection strategies

You can simply add `Asis_Logger` code to some common points of your application, and call its `log` method with parameters received by simple stack backtracing.

PHP pseudocode:
```php
foreach (debug_backtrace() as $call) {
    if(areWeInterestedInThisMethod($call))
        $logger->log($call['class'], $call['function'], $call['args']);
}
```

For example, in our Zend Framework project we injected tests recording in Zend_Db_Table class which implements Table Gateway pattern.
Thus we instantly received hundreds of tests for object-relational mapping (ORM) layer.

Other and probably more promising approach is to integrate with some AOP (Aspect-oriented programming) framework.
Using of AOP will allow you to call the `log` function on every public method call of every class in your project,
resulting in much more higher code coverage!


##Advanced usage

You can provide additional parameters to `Asis_Logger` and `Asis_Tester` to set directory pathes and extensions.

```php
$logger = new Asis_Logger(
    'applicationPath' => "/path/to/your/project" 
    'inputDataPath' => "/path/to/directory_with_testdata/inputs",
    'inputExtension' => "xml"
);
```

```php
$tester = new Asis_Logger(   
    'inputDataPath' => '/path/to/directory_with_testdata/inputs',
    'inputExtension' => 'xml',
    'outputDataPath' => '/path/to/directory_with_testdata/outputs',
    'outputExtension' => 'txt'
);
```

`Asis_Commander` class which you may find in library directory is just a sample code with configuration file parsing and command-line interface implementation (which not works from the box because it needs some additional dependencies: [ZF's Zend_Config](https://github.com/zendframework/zf1) and [Commando](https://github.com/nategood/commando)). 

```php
/*
 * Sample command-line interface using Zend_Config and https://github.com/nategood/commando
 */

require_once 'Zend/Config.php';
require_once 'vendor/autoload.php';
require_once 'Asis/Common/InputDataProvider.php';
require_once 'Asis/Tester.php';

class Asis_Commander
{
    private $_asisTester;

    public function __construct($argv)
    {
        $config = new Zend_Config_Ini('asisConfig.ini');
        $cmd = new Commando\Command($argv);

        $this->_asisTester = new Asis_Tester_Tester(array(
            'inputDataPath' => $config->inputPath,
            'outputDataPath' => $config->outputPath,
            'inputExtension' => $config->inputExtension,
            'outputExtension' => $config->outputExtension)
        );

        $cmd->option('r')->boolean()->aka('run')->describedAs('Run testing');
        $cmd->option('a')->boolean()->aka('approve')->describedAs('Approve received tests');

        if ($cmd['run'])        $this->run();
        if ($cmd['approve'])    $this->approve($config->inputPath, $config->outputPath);
    }
 
    private function run()
    {
        return $this->_asisTester->run();
    }

    private function approve()
    {
        return $this->_asisTester->approve();
    }
}
```


##Implementation details

Currently Asis uses 2 internal serializers by default:
- [PEAR `XML_Serializer`](http://pear.php.net/package/XML_Serializer/) to create human readable XML files with tests;
- native PHP serialization functions ([serialize](http://php.net/manual/ru/function.serialize.php) and [unserialize](http://php.net/manual/ru/function.unserialize.php)) for outputs saving (in general case we don't require them to be human readable, because we don't really care about results if we previously assume that system is stable).
 
Each XML-file internally consists of one global node which contains class nodes. Class nodes in its turn contain method's nodes which contain nodes with input datasets to perform different unit tests.

The `sample.xml` automatically created in the sample above is the following 

```xml
<array _type="array">
   <Foo _type="array">
       <bar _type="array">
          <unnamedItem _originalKey="0" _type="integer">2</unnamedItem>
        </bar>
   </Foo>
</array>
```

XML files are saved in input directory (which is set in `inputDataPath` parameter of `Asis_Logger` constructor) in directory structure which refelects the structure of your project.
It means that if you have recorded the test for some class in `/path/to/your/project/models/mappers/UserMapper.php` file, then the recorded data will be in `/path/to/directory_with_testdata/inputs/models/mappers/UserMapper.xml`.

It seems to be very convenient to surf tests data when it is organized in the same way as the code to test.
The output files are saved in output directory (which is set in `outputDataPath` parameter of `Asis_Tester`'s constructor) structured in directories with names equal to the names of classes.

The output files naming convention is currently the following:

`<method_name>-<md5(serialize(<method_args>))>.<status>.<extension>`

for example, `bar-a5f5d7a5fc80600513c623db108873af.received.txt` 

Currently there are the following `<status>` values:

- __"received"__ - when the output result is evaluated, serialized and saved;
- __"approved"__  - when the output result is approved;
- __"failed"__ - when the unit test failed (the unmatching output is saved in separate file for debug purposes).

While "received" and "approved" statuses are well-known from ApprovalTests, the "failed" status was added in Asis for debugging purposes.

##Contributions

__The further contributions are vary warmly welcomed!__

(Suggestions too, even those which imply whole architecture redesign).

Of course, current implementation is very raw and it may be enhanced and refactored in many different ways. 

Consider it just as a working prototype for concept proving and starting point for further development.

##Ideas for TO-DO LIST

###New functionality

- Add clean-up operation (removing files with failed tests data)
- Create command-line interface
- Code coverage calculation
- Add possibility to create tests for non-method functions (which do not belong to certain class)
- Add possibility to exclude some functions or datasets from test suite (may be neseccary for functions with random output)
- Add limit on recording of tests which belong to the same class of equality
- Now tool can create tests only for public methods of classes, it will be great to add possibility to record and approve outputs of private methods 
- Add wider functionality to test database interactions (especially its DML-part)
- Use some better implementation of XML serialization instead of old and poorly mantained PEAR `XML_Serializer` package (there are lots of "strict standards" and "deprecated" errors during its execution)
- Add some other serializers, as, for example, fast unserialization using simple but effective var\_export and require_once technique, as it was done [here](https://github.com/sebastianbergmann/phpunit/pull/989)
- Add some other input data storages (SQLite is one of the options)
- Integrate with some AOP (Aspect-oriented programming) frameworks 

###Other

- Create composer package
- Add code comments and proper documentation 

## License

Licensed by GPL
