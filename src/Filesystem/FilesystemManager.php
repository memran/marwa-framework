<?php

declare(strict_types=1);

namespace Marwa\App\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Filesystem as FlyFS;
use League\Flysystem\Visibility;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;

// S3
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

// FTP/SFTP (optional; require the respective packages)
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

/**
 * FilesystemManager
 *
 * Responsibilities:
 * - Hold configuration for multiple disks
 * - Lazily instantiate Flysystem adapters
 * - Return a Laravel-like Filesystem wrapper for a given disk
 */
final class FilesystemManager
{
    /** @var array<string,mixed> */
    private array $config;

    /** @var array<string,Filesystem> */
    private array $disks = [];

    public function __construct(array $config)
    {
        // expected shape:
        // [
        //   'default' => 'local',
        //   'disks' => [
        //     'local' => ['driver' => 'local', 'root' => '/path', 'visibility' => 'public'],
        //     's3' => [
        //        'driver' => 's3',
        //        'key' => '...',
        //        'secret' => '...',
        //        'region' => 'ap-south-1',
        //        'bucket' => 'my-bucket',
        //        'endpoint' => null, // optional
        //        'url' => 'https://cdn.example.com', // optional base URL
        //        'visibility' => 'private'
        //     ],
        //     ...
        //   ]
        // ]
        if (!isset($config['default'], $config['disks']) || !is_array($config['disks'])) {
            throw new InvalidArgumentException('Invalid filesystem configuration: missing "default" or "disks".');
        }
        $this->config = $config;
    }

    /**
     * Get a Filesystem for a disk. Defaults to the configured "default" disk.
     */
    public function disk(?string $name = null): Filesystem
    {
        $name = $name ?: (string)$this->config['default'];
        if (isset($this->disks[$name])) {
            return $this->disks[$name];
        }

        $this->disks[$name] = $this->resolve($name);
        return $this->disks[$name];
    }

    /**
     * Register/override a disk at runtime (useful for tests).
     */
    public function setDisk(string $name, Filesystem $filesystem): void
    {
        $this->disks[$name] = $filesystem;
    }

    /**
     * Build the Filesystem for a named disk using config.
     */
    private function resolve(string $name): Filesystem
    {
        $disks = $this->config['disks'];
        if (!isset($disks[$name]) || !is_array($disks[$name])) {
            throw new InvalidArgumentException("Filesystem disk [{$name}] is not configured.");
        }

        $cfg = $disks[$name];
        $driver = strtolower((string)($cfg['driver'] ?? ''));

        return match ($driver) {
            'local' => $this->createLocalFilesystem($cfg),
            's3'    => $this->createS3Filesystem($cfg),
            'ftp'   => $this->createFtpFilesystem($cfg),
            'sftp'  => $this->createSftpFilesystem($cfg),
            default => throw new InvalidArgumentException("Unsupported filesystem driver [{$driver}] for disk [{$name}]."),
        };
    }

    private function createLocalFilesystem(array $cfg): Filesystem
    {
        $root = (string)($cfg['root'] ?? '');
        if ($root === '') {
            throw new InvalidArgumentException('Local driver requires "root" path.');
        }

        $visibilityConverter = $this->visibilityConverter($cfg['visibility'] ?? 'public');

        $adapter = new LocalFilesystemAdapter($root, $visibilityConverter);
        $fly = new FlyFS($adapter);
        return new Filesystem($fly, $cfg);
    }

    private function createS3Filesystem(array $cfg): Filesystem
    {
        foreach (['key', 'secret', 'region', 'bucket'] as $required) {
            if (empty($cfg[$required])) {
                throw new InvalidArgumentException("S3 driver requires [{$required}].");
            }
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => (string)$cfg['region'],
            'credentials' => [
                'key' => (string)$cfg['key'],
                'secret' => (string)$cfg['secret'],
            ],
            // Optional endpoint (e.g., MinIO)
            'endpoint' => $cfg['endpoint'] ?? null,
            'use_path_style_endpoint' => (bool)($cfg['use_path_style_endpoint'] ?? false),
        ]);

        $options = [
            'visibility' => $cfg['visibility'] ?? Visibility::PRIVATE,
        ];

        $adapter = new AwsS3V3Adapter($client, (string)$cfg['bucket'], $cfg['prefix'] ?? '', $options);
        $fly = new FlyFS($adapter);
        return new Filesystem($fly, $cfg);
    }

    private function createFtpFilesystem(array $cfg): Filesystem
    {
        foreach (['host', 'root', 'username', 'password'] as $required) {
            if (empty($cfg[$required])) {
                throw new InvalidArgumentException("FTP driver requires [{$required}].");
            }
        }

        $connection = FtpConnectionOptions::fromArray([
            'host' => (string)$cfg['host'],
            'root' => (string)$cfg['root'],
            'username' => (string)$cfg['username'],
            'password' => (string)$cfg['password'],
            'port' => (int)($cfg['port'] ?? 21),
            'ssl' => (bool)($cfg['ssl'] ?? false),
            'timeout' => (int)($cfg['timeout'] ?? 30),
            'passive' => (bool)($cfg['passive'] ?? true),
        ]);

        $adapter = new FtpAdapter($connection);
        $fly = new FlyFS($adapter);
        return new Filesystem($fly, $cfg);
    }

    private function createSftpFilesystem(array $cfg): Filesystem
    {
        foreach (['host', 'root', 'username'] as $required) {
            if (empty($cfg[$required])) {
                throw new InvalidArgumentException("SFTP driver requires [{$required}].");
            }
        }

        $provider = new SftpConnectionProvider(
            (string)$cfg['host'],
            (int)($cfg['port'] ?? 22),
            (string)$cfg['username'],
            $cfg['password'] ?? null,
            $cfg['privateKey'] ?? null,
            (string)($cfg['passphrase'] ?? ''),
            (int)($cfg['timeout'] ?? 30),
            (bool)($cfg['useAgent'] ?? false)
        );

        $adapter = new SftpAdapter($provider, (string)$cfg['root']);
        $fly = new FlyFS($adapter);
        return new Filesystem($fly, $cfg);
    }

    private function visibilityConverter(string $visibility): mixed
    {
        $isPublic = strtolower($visibility) === 'public';
        $visibilityConverter = new PortableVisibilityConverter();
        if ($isPublic) {
            $visibilityConverter->forFile(Visibility::PUBLIC);
            $visibilityConverter->forDirectory(Visibility::PUBLIC);
        } else {
            $visibilityConverter->forFile(Visibility::PRIVATE);
            $visibilityConverter->forDirectory(Visibility::PRIVATE);
        }

        return $visibilityConverter;
    }
}
