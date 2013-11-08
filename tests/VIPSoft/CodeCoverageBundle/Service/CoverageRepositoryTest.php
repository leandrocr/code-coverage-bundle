<?php
/**
 * Repository
 *
 * @copyright 2013 Anthon Pang
 * @license BSD-2-Clause
 */

namespace VIPSoft\CodeCoverageBundle\Service;

use VIPSoft\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @group Unit
 */
class CodeCoverageRepositoryTest extends TestCase
{
    private $vfsRoot;
    private $databaseFile;
    private $sqlite;

    public function __construct()
    {
        if ( ! class_exists('VIPSoft\CodeCoverageBundle\Test\SQLite')) {
            eval(<<<END_OF_SQLITE
namespace VIPSoft\CodeCoverageBundle\Test {
    class SQLite
    {
        static public \$proxiedMethods;

        public function __call(\$methodName, \$args)
        {
            if (isset(self::\$proxiedMethods[\$methodName])) {
                return call_user_func_array(self::\$proxiedMethods[\$methodName], \$args);
            }
        }
    }
}
END_OF_SQLITE
            );
        }

        $this->sqlite = 'VIPSoft\CodeCoverageBundle\Test\SQLite';
    }

    protected function setUp()
    {
        parent::setUp();

        $this->vfsRoot = vfsStream::setup('privateDir');

        $this->databaseFile = vfsStream::url('privateDir/coverage.dbf');
    }

    protected function tearDown()
    {
        parent::tearDown();

        \VIPSoft\CodeCoverageBundle\Test\SQLite::$proxiedMethods = array();
    }

    public function testInitialize()
    {
        $this->getMockFunction('file_exists', function () { return false; });

        \VIPSoft\CodeCoverageBundle\Test\SQLite::$proxiedMethods['exec'] = function () { return null; };

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);

        $this->assertTrue($repository->initialize());
    }

    public function testIsEnabledWhenFileExists()
    {
        file_put_contents($this->databaseFile, 'data');

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);

        $this->assertTrue($repository->isEnabled());
    }

    public function testIsEnabledWhenFileMissing()
    {
        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);

        $this->assertFalse($repository->isEnabled());
    }

    public function testAddCoverage()
    {
        $coverage = array('test' => array(1 => 1));

        $proxy = $this->getMock('VIPSoft\Test\FunctionProxy');
        $proxy->expects($this->once())
              ->method('invokeFunction')
              ->with("INSERT INTO coverage (class, counts) VALUES ('test', '{\"1\":1}')");

        \VIPSoft\CodeCoverageBundle\Test\SQLite::$proxiedMethods['exec'] = array($proxy, 'invokeFunction');

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);
        $repository->addCoverage($coverage);
    }

    public function testGetCoverage()
    {
        $coverage = array('test' => array(1 => 1));

        \VIPSoft\CodeCoverageBundle\Test\SQLite::$proxiedMethods['query_array'] = function () {
            return array(
                array('class' => 'test', 'counts' => '{"1":1}'),
            );
        };

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);

        $this->assertEquals($coverage, $repository->getCoverage());
    }

    public function testDropWhenFileExists()
    {
        $proxy = $this->getMock('VIPSoft\Test\FunctionProxy');
        $proxy->expects($this->once())
              ->method('invokeFunction');

        $this->getMockFunction('unlink', $proxy);

        file_put_contents($this->databaseFile, 'data');

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);
        $repository->drop();
    }

    public function testDropWhenFileMissing()
    {
        $proxy = $this->getMock('VIPSoft\Test\FunctionProxy');
        $proxy->expects($this->never())
              ->method('invokeFunction');

        $this->getMockFunction('unlink', $proxy);

        $repository = new CodeCoverageRepository($this->databaseFile, $this->sqlite);
        $repository->drop();
    }
}