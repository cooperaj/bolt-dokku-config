<?php declare(strict_types=1);

namespace BoltEnv;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Takes configuration information made available in the environment by dokku and writes out a config_local.yml configuration file so Bolt can make use of it.
 */
class Configuration
{
    /** @var string $configFilePath The path to the configuration file to be written. Relative to the root of the bolt application. */
    protected $configFilePath = 'app/config/config_local.yml';

    /** @var array $dbFields An array containing the expected database configuration fields */
    protected $dbFields = [
        'driver' => 'mysql',
        'username' => 'bolt',
        'password' => 'bolt',
        'database' => 'bolt',
        'host' => 'localhost',
        'port' => '3306'
    ];

    /** @var Filesystem $filesystem */
    protected $filesystem;

    public static function write(Event $event)
    {
        $instance = new self(new Filesystem());

        if ( ! getenv('DATABASE_URL')) {
            $event->getIO()->writeError('DATABASE_URL not set, not writing file');
            return;
        }

        $event->getIO()->writeError(sprintf('Writing DB configuration to %s', $instance->getConfigPath()));
        $instance->writeFile();
    }

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getConfigPath(): string
    {
        return $this->configFilePath;
    }

    public function writeFile()
    {
        $fields = $this->loadDBFromEnv();
        $content = $this->renderFile($fields);

        $this->filesystem->dumpFile($this->getConfigPath(), $content);
    }

    protected function loadDBFromEnv(): array
    {
        $fields = $this->dbFields;

        if ( ! $urn = getenv('DATABASE_URL')) {
            throw new \Exception("DATABASE_URL not defined in environment");
        }

        $components = parse_url($urn);

        $fields['driver'] = $components['scheme'];
        $fields['username'] = $components['user'];
        $fields['password'] = $components['pass'];
        $fields['database'] = ltrim($components['path'], '/');
        $fields['host'] = $components['host'];
        $fields['port'] = $components['port'];

        return $fields;
    }

    protected function renderFile(array $fields): string
    {
        ob_start();
        include(__DIR__ . '/../templates/config_local.php');
        return ob_get_clean();
    }

}
