<?php

namespace Kaibatech\ViettelCloudS3;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use Exception;
use Illuminate\Support\Facades\Log;

class ViettelCloudS3Adapter implements FilesystemAdapter
{
    private $accessKey;
    private $secretKey;
    private $region;
    private $bucket;
    private $endpoint;
    private $baseUrl;

    public function __construct(array $config)
    {
        $this->accessKey = $config['key'];
        $this->secretKey = $config['secret'];
        $this->region = $config['region'] ?? 'us-east-1';
        $this->bucket = $config['bucket'];
        $this->endpoint = $config['endpoint'];
        $this->baseUrl = $config['url'] ?? $this->endpoint;
    }

    public function fileExists(string $path): bool
    {
        try {
            $service = 's3';
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            
            // Create timestamp in ISO8601 format
            $t = new \DateTime('UTC');
            $amzDate = $t->format('Ymd\THis\Z');
            $dateStamp = $t->format('Ymd');
            
            // Create the URL
            $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($path, '/');
            
            // Create canonical request for HEAD
            $method = 'HEAD';
            $canonicalUri = '/' . $this->bucket . '/' . ltrim($path, '/');
            $canonicalQuerystring = '';
            $payloadHash = 'UNSIGNED-PAYLOAD';
            
            $canonicalHeaders = "host:{$host}\n" .
                               "x-amz-content-sha256:{$payloadHash}\n" .
                               "x-amz-date:{$amzDate}\n" .
                               "x-amz-user-agent:viettel-cloud-s3/1.0 callback\n";
            
            $signedHeaders = 'host;x-amz-content-sha256;x-amz-date;x-amz-user-agent';
            
            $canonicalRequest = $method . "\n" .
                               $canonicalUri . "\n" .
                               $canonicalQuerystring . "\n" .
                               $canonicalHeaders . "\n" .
                               $signedHeaders . "\n" .
                               $payloadHash;
            
            // Create string to sign
            $algorithm = 'AWS4-HMAC-SHA256';
            $credentialScope = $dateStamp . '/' . $this->region . '/' . $service . '/aws4_request';
            $stringToSign = $algorithm . "\n" .
                           $amzDate . "\n" .
                           $credentialScope . "\n" .
                           hash('sha256', $canonicalRequest);
            
            // Calculate signature
            $signingKey = $this->getSignatureKey($this->secretKey, $dateStamp, $this->region, $service);
            $signature = hash_hmac('sha256', $stringToSign, $signingKey);
            
            // Create authorization header
            $authorizationHeader = $algorithm . ' ' .
                                  'Credential=' . $this->accessKey . '/' . $credentialScope . ', ' .
                                  'SignedHeaders=' . $signedHeaders . ', ' .
                                  'Signature=' . $signature;
            
            // Prepare headers
            $headers = [
                'Authorization: ' . $authorizationHeader,
                'Host: ' . $host,
                'X-Amz-Content-Sha256: ' . $payloadHash,
                'X-Amz-Date: ' . $amzDate,
                'X-Amz-User-Agent: viettel-cloud-s3/1.0 callback',
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        // S3 doesn't have real directories, so we'll check if any files exist with this prefix
        return true; // For simplicity, assume directories always exist
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $service = 's3';
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            
            // Create timestamp in ISO8601 format
            $t = new \DateTime('UTC');
            $amzDate = $t->format('Ymd\THis\Z');
            $dateStamp = $t->format('Ymd');
            
            // Create the URL
            $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($path, '/');
            
            // Get config values before signature calculation
            $contentType = $config->get('mimetype') ?? $this->getMimeType($path);
            $visibility = $config->get('visibility');
            
            // Create canonical request
            $method = 'PUT';
            $canonicalUri = '/' . $this->bucket . '/' . ltrim($path, '/');
            $canonicalQuerystring = '';
            $payloadHash = 'UNSIGNED-PAYLOAD';
            
            // Build canonical headers - include x-amz-acl if visibility is public
            $canonicalHeaders = "host:{$host}\n" .
                               "x-amz-content-sha256:{$payloadHash}\n" .
                               "x-amz-date:{$amzDate}\n" .
                               "x-amz-user-agent:viettel-cloud-s3/1.0 callback\n";
            
            $signedHeaders = 'host;x-amz-content-sha256;x-amz-date;x-amz-user-agent';
            
            // Add ACL to canonical headers if visibility is public (must be in alphabetical order)
            if ($visibility === 'public') {
                $canonicalHeaders = "host:{$host}\n" .
                                   "x-amz-acl:public-read\n" .
                                   "x-amz-content-sha256:{$payloadHash}\n" .
                                   "x-amz-date:{$amzDate}\n" .
                                   "x-amz-user-agent:viettel-cloud-s3/1.0 callback\n";
                $signedHeaders = 'host;x-amz-acl;x-amz-content-sha256;x-amz-date;x-amz-user-agent';
            }
            
            $canonicalRequest = $method . "\n" .
                               $canonicalUri . "\n" .
                               $canonicalQuerystring . "\n" .
                               $canonicalHeaders . "\n" .
                               $signedHeaders . "\n" .
                               $payloadHash;
            
            // Create string to sign
            $algorithm = 'AWS4-HMAC-SHA256';
            $credentialScope = $dateStamp . '/' . $this->region . '/' . $service . '/aws4_request';
            $stringToSign = $algorithm . "\n" .
                           $amzDate . "\n" .
                           $credentialScope . "\n" .
                           hash('sha256', $canonicalRequest);
            
            // Calculate signature
            $signingKey = $this->getSignatureKey($this->secretKey, $dateStamp, $this->region, $service);
            $signature = hash_hmac('sha256', $stringToSign, $signingKey);
            
            // Create authorization header
            $authorizationHeader = $algorithm . ' ' .
                                  'Credential=' . $this->accessKey . '/' . $credentialScope . ', ' .
                                  'SignedHeaders=' . $signedHeaders . ', ' .
                                  'Signature=' . $signature;
            
            // Prepare headers
            $headers = [
                'Authorization: ' . $authorizationHeader,
                'Host: ' . $host,
                'X-Amz-Content-Sha256: ' . $payloadHash,
                'X-Amz-Date: ' . $amzDate,
                'X-Amz-User-Agent: viettel-cloud-s3/1.0 callback',
                'Content-Type: ' . $contentType,
                'Content-Length: ' . strlen($contents),
            ];

            // Add ACL header to actual request headers if visibility is public
            if ($visibility === 'public') {
                $headers[] = 'x-amz-acl: public-read';
            }
            
            // Make the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new UnableToWriteFile('cURL error: ' . $error, 0, null);
            }
            
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new UnableToWriteFile('Upload failed with status: ' . $httpCode . '. Response: ' . $response, 0, null);
            }
            
            Log::info('Viettel Cloud S3 file written successfully', [
                'path' => $path,
                'size' => strlen($contents),
                'status' => $httpCode,
                'visibility' => $visibility
            ]);
            
        } catch (Exception $e) {
            if ($e instanceof UnableToWriteFile) {
                throw $e;
            }
            throw new UnableToWriteFile($e->getMessage(), 0, $e, $path);
        }
    }

    public function writeStream(string $path, $resource, Config $config): void
    {
        $contents = stream_get_contents($resource);
        $this->write($path, $contents, $config);
    }

    public function read(string $path): string
    {
        try {
            $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($path, '/');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getAuthHeaders('GET', $path));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new UnableToReadFile('cURL error: ' . $error, 0, null);
            }
            
            if ($httpCode !== 200) {
                throw new UnableToReadFile('File not found or access denied', 0, null);
            }
            
            return $response;
            
        } catch (Exception $e) {
            if ($e instanceof UnableToReadFile) {
                throw $e;
            }
            throw new UnableToReadFile($e->getMessage(), 0, $e, $path);
        }
    }

    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $service = 's3';
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            
            // Create timestamp in ISO8601 format
            $t = new \DateTime('UTC');
            $amzDate = $t->format('Ymd\THis\Z');
            $dateStamp = $t->format('Ymd');
            
            // Create the URL
            $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($path, '/');
            
            // Create canonical request for DELETE
            $method = 'DELETE';
            $canonicalUri = '/' . $this->bucket . '/' . ltrim($path, '/');
            $canonicalQuerystring = '';
            $payloadHash = 'UNSIGNED-PAYLOAD';
            
            // Include content-type in the canonical headers for DELETE to match working JS example
            $canonicalHeaders = "content-type:application/octet-stream\n" .
                               "host:{$host}\n" .
                               "x-amz-content-sha256:{$payloadHash}\n" .
                               "x-amz-date:{$amzDate}\n" .
                               "x-amz-user-agent:viettel-cloud-s3/1.0 callback\n";
            
            $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date;x-amz-user-agent';
            
            $canonicalRequest = $method . "\n" .
                               $canonicalUri . "\n" .
                               $canonicalQuerystring . "\n" .
                               $canonicalHeaders . "\n" .
                               $signedHeaders . "\n" .
                               $payloadHash;
            
            // Create string to sign
            $algorithm = 'AWS4-HMAC-SHA256';
            $credentialScope = $dateStamp . '/' . $this->region . '/' . $service . '/aws4_request';
            $stringToSign = $algorithm . "\n" .
                           $amzDate . "\n" .
                           $credentialScope . "\n" .
                           hash('sha256', $canonicalRequest);
            
            // Calculate signature
            $signingKey = $this->getSignatureKey($this->secretKey, $dateStamp, $this->region, $service);
            $signature = hash_hmac('sha256', $stringToSign, $signingKey);
            
            // Create authorization header
            $authorizationHeader = $algorithm . ' ' .
                                  'Credential=' . $this->accessKey . '/' . $credentialScope . ', ' .
                                  'SignedHeaders=' . $signedHeaders . ', ' .
                                  'Signature=' . $signature;
            
            // Prepare headers - include Content-Type as in working JS example
            $headers = [
                'Authorization: ' . $authorizationHeader,
                'Host: ' . $host,
                'X-Amz-Content-Sha256: ' . $payloadHash,
                'X-Amz-Date: ' . $amzDate,
                'X-Amz-User-Agent: viettel-cloud-s3/1.0 callback',
                'Content-Type: application/octet-stream',
            ];
            
            // Make the DELETE request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new UnableToDeleteFile('cURL error: ' . $error, 0, null, $path);
            }
            
            // Accept 204 (No Content) as primary success, also accept 200 and 404 (already deleted)
            if ($httpCode !== 204 && $httpCode !== 200 && $httpCode !== 404) {
                throw new UnableToDeleteFile('Delete failed with status: ' . $httpCode . ' Response: ' . $response, 0, null, $path);
            }
            
            Log::info('Viettel Cloud S3 file deleted successfully', [
                'path' => $path,
                'status' => $httpCode
            ]);
            
        } catch (Exception $e) {
            if ($e instanceof UnableToDeleteFile) {
                throw $e;
            }
            throw new UnableToDeleteFile($e->getMessage(), 0, $e, $path);
        }
    }

    public function deleteDirectory(string $path): void
    {
        // For simplicity, we'll just return success
        // In a full implementation, you'd list all files in the directory and delete them
    }

    public function createDirectory(string $path, Config $config): void
    {
        // S3 doesn't have real directories, so this is a no-op
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // This would require updating the ACL, which is complex
        // For now, we'll just log it
        Log::info('Visibility change requested', ['path' => $path, 'visibility' => $visibility]);
    }

    public function visibility(string $path): FileAttributes
    {
        // For simplicity, assume all files are private unless specified otherwise
        return new FileAttributes($path, null, 'private');
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = $this->getMimeType($path);
        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        // This would require a HEAD request to get metadata
        // For simplicity, return current time
        return new FileAttributes($path, null, null, time());
    }

    public function fileSize(string $path): FileAttributes
    {
        // This would require a HEAD request to get content-length
        // For simplicity, return 0
        return new FileAttributes($path, 0);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        // This would require implementing the ListObjects S3 API
        // For simplicity, return empty array
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // Copy then delete
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }

    /**
     * Get the public URL for a file
     */
    public function getUrl(string $path): string
    {
        // Remove bucket duplication in URL
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    private function getAuthHeaders(string $method, string $path): array
    {
        $service = 's3';
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        
        // Create timestamp in ISO8601 format
        $t = new \DateTime('UTC');
        $amzDate = $t->format('Ymd\THis\Z');
        $dateStamp = $t->format('Ymd');
        
        // Create canonical request
        $canonicalUri = '/' . $this->bucket . '/' . ltrim($path, '/');
        $canonicalQuerystring = '';
        $payloadHash = 'UNSIGNED-PAYLOAD';
        
        $canonicalHeaders = "host:{$host}\n" .
                           "x-amz-content-sha256:{$payloadHash}\n" .
                           "x-amz-date:{$amzDate}\n" .
                           "x-amz-user-agent:viettel-cloud-s3/1.0 callback\n";
        
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date;x-amz-user-agent';
        
        $canonicalRequest = $method . "\n" .
                           $canonicalUri . "\n" .
                           $canonicalQuerystring . "\n" .
                           $canonicalHeaders . "\n" .
                           $signedHeaders . "\n" .
                           $payloadHash;
        
        // Create string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = $dateStamp . '/' . $this->region . '/' . $service . '/aws4_request';
        $stringToSign = $algorithm . "\n" .
                       $amzDate . "\n" .
                       $credentialScope . "\n" .
                       hash('sha256', $canonicalRequest);
        
        // Calculate signature
        $signingKey = $this->getSignatureKey($this->secretKey, $dateStamp, $this->region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        
        // Create authorization header
        $authorizationHeader = $algorithm . ' ' .
                              'Credential=' . $this->accessKey . '/' . $credentialScope . ', ' .
                              'SignedHeaders=' . $signedHeaders . ', ' .
                              'Signature=' . $signature;
        
        return [
            'Authorization: ' . $authorizationHeader,
            'Host: ' . $host,
            'X-Amz-Content-Sha256: ' . $payloadHash,
            'X-Amz-Date: ' . $amzDate,
            'X-Amz-User-Agent: viettel-cloud-s3/1.0 callback',
        ];
    }

    private function getSignatureKey($key, $dateStamp, $regionName, $serviceName)
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $key, true);
        $kRegion = hash_hmac('sha256', $regionName, $kDate, true);
        $kService = hash_hmac('sha256', $serviceName, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        
        return $kSigning;
    }

    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
} 