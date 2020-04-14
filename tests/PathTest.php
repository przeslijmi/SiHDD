<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use PHPUnit\Framework\TestCase;
use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\MethodFopException;
use Przeslijmi\SiHDD\Path;

/**
 * Methods for testing Path class.
 */
final class PathTest extends TestCase
{

    /**
     * Test if Path can be properly created and read.
     *
     * @return void
     */
    public function testProperCreation() : void
    {

        $path = new Path('config/');

        $this->assertTrue($path->isExisting());
        $this->assertFalse($path->isNotExisting());
        $this->assertTrue($path->isDir());
        $this->assertFalse($path->isNotDir());
        $this->assertFalse($path->isFile());
        $this->assertTrue($path->isNotFile());
        $this->assertEquals('config\\', $path->getPath());
    }

    /**
     * Test if getting dir path in both variants works.
     *
     * @return void
     */
    public function testIfGettingDirPathWorks() : void
    {

        $path = new Path('config');

        $this->assertEquals('config', $path->getPath());
        $this->assertEquals('config\\', $path->getPath(true));
    }

    /**
     * Test if creating Path with wrong name will throw.
     *
     * @return void
     */
    public function testIfWrongNameThrows() : void
    {

        $this->expectException(ClassFopException::class);

        new Path('wro name');
    }

    /**
     * Test if creating and deleting Path works.
     *
     * @return void
     *
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.ContainsNumbers
     */
    public function testIfCreationAndDeletionWorks() : void
    {

        // Lvd.
        $pathString1 = 'config/creationTest/deep/deeper/target/' . rand(1000, 9999);
        $pathString2 = 'config/creationTest';
        $pathString3 = 'config';

        // Create Path.
        $path = new Path($pathString1);
        $path->createDirs(true);

        // Check if exists.
        $this->assertTrue(file_exists($pathString1));

        // Call to delete everything below config/ and leave config intact.
        $path->deleteEmptyDirs(1);

        // Check if deletion was proper.
        $this->assertFalse(file_exists($pathString1));
        $this->assertFalse(file_exists($pathString2));
        $this->assertTrue(file_exists($pathString3));

        // Call again to delete - nothing should be changed but it should work.
        $path->deleteEmptyDirs(1);

        // Check if worked.
        $this->assertFalse(file_exists($pathString1));
        $this->assertFalse(file_exists($pathString2));
        $this->assertTrue(file_exists($pathString3));
    }

    /**
     * Test if deleteting nonempty Path will be ignored.
     *
     * @return void
     */
    public function testIfDeletionOfNonemptyWillBeIgnored() : void
    {

        // Lvd.
        $pathString = 'src';

        // Create Path.
        $path = new Path($pathString);
        $path->deleteEmptyDirs(0);

        // Check if worked.
        $this->assertTrue(file_exists($pathString));
    }

    /**
     * Test if creating direct Path (`/`) will work.
     *
     * @return void
     */
    public function testIfCreationOfDirectPathWorks() : void
    {

        // Lvd.
        $pathString = '\\config';

        // Create Path.
        $path = new Path($pathString);

        // Check if deletion was ignored.
        $this->assertEquals($pathString, $path->getPath());
    }

    /**
     * Test if creating dir below file will throw.
     *
     * @return void
     */
    public function testIfCreationOfDirBelowFileThrows() : void
    {

        $this->expectException(ClassFopException::class);

        // Create Path.
        $path = new Path('src/Dir.php/hello');
    }
}
