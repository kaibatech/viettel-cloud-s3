<?php

namespace Kaibatech\ViettelCloudS3\Tests;

use PHPUnit\Framework\TestCase;
use Kaibatech\ViettelCloudS3\ViettelCloudS3Adapter;
use League\Flysystem\Config;

class ViettelCloudS3AdapterTest extends TestCase
{
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ViettelCloudS3Adapter([
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
            'endpoint' => 'https://test.example.com',
            'url' => 'https://test.example.com/test-bucket',
        ]);
    }

    public function testAdapterCanBeInstantiated()
    {
        $this->assertInstanceOf(ViettelCloudS3Adapter::class, $this->adapter);
    }

    public function testDirectoryAlwaysExists()
    {
        $this->assertTrue($this->adapter->directoryExists('any/path'));
    }

    public function testGetUrlGeneratesCorrectUrl()
    {
        $path = 'test/file.txt';
        $expectedUrl = 'https://test.example.com/test-bucket/test/file.txt';
        $actualUrl = $this->adapter->getUrl($path);
        
        $this->assertEquals($expectedUrl, $actualUrl);
    }

    public function testGetUrlTrimsLeadingSlash()
    {
        $path = '/test/file.txt';
        $expectedUrl = 'https://test.example.com/test-bucket/test/file.txt';
        $actualUrl = $this->adapter->getUrl($path);
        
        $this->assertEquals($expectedUrl, $actualUrl);
    }

    // Note: Integration tests would require actual credentials and endpoint
    // These basic tests ensure the adapter structure is correct
} 