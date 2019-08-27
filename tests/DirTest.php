<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use PHPUnit\Framework\TestCase;
use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\SiHDD\Dir;
use Przeslijmi\SiHDD\Path;

/**
 * Methods for testing Dir class.
 */
final class DirTest extends TestCase
{

    /**
     * Data provider for URIs for testing.
     *
     * @return array
     */
    public function uriProvider() : array
    {

        // Calulate dir.
        $moduleDir = rtrim(__DIR__, '\\');
        $moduleDir = substr($moduleDir, 0, strrpos($moduleDir, '\\')) . '\\';

        // Compose result [ dirUri, hasPhpFiles, hasAnyFiles, hasDirs ].
        $uris = [
            [ $moduleDir, false, true, true ],
            [ $moduleDir . 'src\\', true, true, false ],
            [ $moduleDir . 'tests\\emptyDir\\', false, false, false ],
        ];

        return $uris;
    }

    /**
     * Data provider for URIs for recursive testing.
     *
     * @return array
     */
    public function uriRecursiveProvider() : array
    {

        // Calulate dir.
        $moduleDir = rtrim(__DIR__, '\\');
        $moduleDir = substr($moduleDir, 0, strrpos($moduleDir, '\\')) . '\\';

        // Compose result [ dirUri, hasPhpFiles, hasAnyFiles, hasDirs ].
        $uris = [
            [ $moduleDir, true, true, true ],
        ];

        return $uris;
    }

    /**
     * Test if creting DIr on uri taken by file (not dir) throws.
     *
     * @return void
     */
    public function testIfCreatingDirOnNondirThrows() : void
    {

        $this->expectException(ClassFopException::class);

        // Create Dir.
        $dir = new Dir('config/.config.php');
    }

    /**
     * Test if creating dir with wrong name throws.
     *
     * @return void
     */
    public function testIfCreatingDirOnWrongNameThrows() : void
    {

        $this->expectException(ClassFopException::class);

        // Create Dir.
        $dir = new Dir('c   onfig/.config.php');
    }

    /**
     * Test if reading dir properly works.
     *
     * @param string  $dirUri      Dir URI to be tested.
     * @param boolean $hasPhpFiles If this dir has any PHP files.
     * @param boolean $hasAnyFiles If this dir has any files.
     * @param boolean $hasDirs     If this dir has any dirs.
     *
     * @return void
     *
     * @dataProvider uriProvider
     */
    public function testIfProperDirWorks(
        string $dirUri,
        bool $hasPhpFiles,
        bool $hasAnyFiles,
        bool $hasDirs
    ) : void {

        // Lvd.
        $hasAnything = ( $hasPhpFiles || $hasAnyFiles || $hasDirs );

        // Create Dir.
        $dir = new Dir($dirUri);

        $this->assertEquals($hasPhpFiles, (bool) $dir->count('*.php'), 'thereAreFilesWithExtensionPhp');
        $this->assertFalse((bool) $dir->count('*.wrongExtension'), 'thereAreNoFilesWithStrangeExtension');
        $this->assertEquals($hasAnyFiles, (bool) $dir->count('', true), 'thereAreFiles');
        $this->assertEquals($hasDirs, (bool) $dir->count('', false, true), 'thereAreDirs');
        $this->assertEquals($hasPhpFiles, (bool) $dir->countFiles('*.php'), 'countingFiles');
        $this->assertFalse((bool) $dir->countDirs('*.php'), 'countingDirsWithPhpExtension');
        $this->assertEquals($hasAnything, (bool) count($dir->read()), 'thereIsAnything');
        $this->assertEquals($hasPhpFiles, (bool) count($dir->read('*.php')), 'readPhpFiles');
        $this->assertFalse((bool) count($dir->read('*.wrongExtension')), 'readNoneFiles');
    }

    /**
     * Test if reading dir properly works.
     *
     * @param string  $dirUri      Dir URI to be tested.
     * @param boolean $hasPhpFiles If this dir has any PHP files.
     * @param boolean $hasAnyFiles If this dir has any files.
     * @param boolean $hasDirs     If this dir has any dirs.
     *
     * @return void
     *
     * @dataProvider uriRecursiveProvider
     */
    public function testIfAddingFilesMtimesWorks(
        string $dirUri,
        bool $hasPhpFiles,
        bool $hasAnyFiles,
        bool $hasDirs
    ) : void {

        // Lvd.
        $hasAnything = ( $hasPhpFiles || $hasAnyFiles || $hasDirs );

        // Create Dir.
        $dir = new Dir($dirUri, Path::DIR_READ_RECURSIVELY);
        $dir->addFilesMtimes();
        $this->assertEquals($hasPhpFiles, (bool) count($dir->read('*.php')), 'readPhpFiles');
    }
}
