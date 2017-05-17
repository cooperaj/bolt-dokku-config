<?php declare(strict_types=1);

namespace Tests\BoltEnv;

use BoltEnv\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationTest extends TestCase
{
    public function setUp()
    {
        // ensure the envrionment variable is not set.
        putenv('DATABASE_URL');
    }

    /**
     * @test
     * @dataProvider urnProvider
     *
     * @param string $urn
     * @param array $expectedFields
     */
    public function it_loads_config_from_the_environment(string $urn, string $file)
    {
        putenv('DATABASE_URL=' . $urn);

        /** @var Filesystem|\PHPUnit_Framework_MockObject_MockObject $mockFileSystem */
        $mockFileSystem = $this->createMock(Filesystem::class);
        $test = $this;

        $mockFileSystem->expects($this->once())
            ->method('dumpFile')
            ->with(
                $this->stringContains('config_local.yml'),
                $this->callback(function($subject) use ($test, $file) {
                    $test->assertStringEqualsFile(__DIR__ . '/' . $file, $subject);

                    return true;
                })

            );

        $configuration = new Configuration($mockFileSystem);

        $configuration->writeFile();
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_fails_without_available_database_url()
    {
        /** @var Filesystem|\PHPUnit_Framework_MockObject_MockObject $mockFileSystem */
        $mockFileSystem = $this->createMock(Filesystem::class);

        $configuration = new Configuration($mockFileSystem);

        $configuration->writeFile();
    }

    public function urnProvider()
    {
        return [
            [
                'mysql://mysql:SOME_PASSWORD@dokku-mysql-lolipop:3306/lolipop',
                'fixtures/mysql-config.yml'
            ],
            [
                'postgres://postgres:SOME_PASSWORD@dokku-postgres-lolipop:5432/lolipop',
                'fixtures/postgres-config.yml'
            ]
        ];
    }
}
