# Viettel Cloud Object Storage - Laravel Storage Driver

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaibatech/viettel-cloud-s3.svg?style=flat-square)](https://packagist.org/packages/kaibatech/viettel-cloud-s3)
[![Total Downloads](https://img.shields.io/packagist/dt/kaibatech/viettel-cloud-s3.svg?style=flat-square)](https://packagist.org/packages/kaibatech/viettel-cloud-s3)
[![License](https://img.shields.io/packagist/l/kaibatech/viettel-cloud-s3.svg?style=flat-square)](https://packagist.org/packages/kaibatech/viettel-cloud-s3)

A Laravel Storage driver for **Viettel Cloud Object Storage** and other **VIPCore/EMC ViPR** S3-compatible endpoints that have signature compatibility issues with the standard AWS SDK for PHP.

## ✨ Features

- ✅ **Full Laravel Storage integration** - Use familiar `Storage::disk()` methods
- ✅ **Upload, download, delete files** with proper error handling
- ✅ **File existence checks** and metadata retrieval
- ✅ **Public/private file visibility** support (with ACL headers)
- ✅ **MIME type detection** for uploaded files
- ✅ **URL generation** for public file access
- ✅ **Custom AWS v4 signature calculation** compatible with VIPCore/EMC ViPR
- ✅ **UNSIGNED-PAYLOAD support** required by some S3-compatible services
- ✅ **Laravel 10.x & 11.x support**

## 🚀 Installation

Install the package via Composer:

```bash
composer require kaibatech/viettel-cloud-s3
```

### Laravel Auto-Discovery

The package uses Laravel's auto-discovery feature, so the service provider will be registered automatically.

For Laravel versions that don't support auto-discovery, add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    Kaibatech\ViettelCloudS3\ViettelCloudS3ServiceProvider::class,
],
```

### Publish Configuration (Optional)

If you want to customize the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=viettel-cloud-s3-config
```

## ⚙️ Configuration

Add a new disk to your `config/filesystems.php`:

```php
'disks' => [
    // ... other disks

    'viettel-s3' => [
        'driver' => 'viettel-s3',
        'key' => env('VIETTEL_S3_ACCESS_KEY_ID'),
        'secret' => env('VIETTEL_S3_SECRET_ACCESS_KEY'),
        'region' => env('VIETTEL_S3_REGION', 'us-east-1'),
        'bucket' => env('VIETTEL_S3_BUCKET'),
        'url' => env('VIETTEL_S3_URL'),
        'endpoint' => env('VIETTEL_S3_ENDPOINT'),
        'throw' => false,
    ],
],
```

### Environment Variables

Add these variables to your `.env` file:

```env
# Viettel Cloud Object Storage Configuration
VIETTEL_S3_ACCESS_KEY_ID=your-access-key
VIETTEL_S3_SECRET_ACCESS_KEY=your-secret-key
VIETTEL_S3_REGION=us-east-1
VIETTEL_S3_BUCKET=your-bucket-name
VIETTEL_S3_ENDPOINT=https://vcos.cloudstorage.com.vn
VIETTEL_S3_URL=https://your-access-key.vcos.cloudstorage.com.vn/your-bucket-name
```

### Alternative: Use Existing AWS Environment Variables

If you're migrating from AWS S3, you can reuse your existing environment variables:

```php
'viettel-s3' => [
    'driver' => 'viettel-s3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'throw' => false,
],
```

## 📖 Usage

### Basic File Operations

```php
use Illuminate\Support\Facades\Storage;

// Upload a file
$content = 'Hello, Viettel Cloud!';
$path = 'documents/hello.txt';

Storage::disk('viettel-s3')->put($path, $content, [
    'visibility' => 'public',
    'mimetype' => 'text/plain'
]);

// Check if file exists
if (Storage::disk('viettel-s3')->exists($path)) {
    echo "File exists!";
}

// Download file content
$content = Storage::disk('viettel-s3')->get($path);

// Get file size
$size = Storage::disk('viettel-s3')->size($path);

// Get file URL
$url = Storage::disk('viettel-s3')->url($path);

// Delete file
Storage::disk('viettel-s3')->delete($path);
```

### File Upload with Form Validation

```php
public function uploadFile(Request $request)
{
    $request->validate([
        'file' => 'required|file|max:10240', // 10MB max
    ]);

    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    
    // Upload using Viettel S3 driver
    $path = Storage::disk('viettel-s3')->putFileAs(
        'uploads', 
        $file, 
        $filename,
        ['visibility' => 'public']
    );

    return response()->json([
        'success' => true,
        'path' => $path,
        'url' => Storage::disk('viettel-s3')->url($path),
        'size' => $file->getSize(),
    ]);
}
```

### Batch Operations

```php
// Upload multiple files
$files = [
    'file1.txt' => 'Content 1',
    'file2.txt' => 'Content 2', 
    'file3.txt' => 'Content 3',
];

foreach ($files as $filename => $content) {
    Storage::disk('viettel-s3')->put("batch/{$filename}", $content, [
        'visibility' => 'public'
    ]);
}

// Delete multiple files
$filesToDelete = ['batch/file1.txt', 'batch/file2.txt', 'batch/file3.txt'];
Storage::disk('viettel-s3')->delete($filesToDelete);
```

### Working with Streams

```php
// Upload from stream
$stream = fopen('/path/to/large-file.zip', 'r');
Storage::disk('viettel-s3')->putStream('large-files/archive.zip', $stream);
fclose($stream);

// Read as stream
$stream = Storage::disk('viettel-s3')->readStream('large-files/archive.zip');
// Process stream...
```

### File Metadata

```php
$path = 'documents/example.pdf';

// Get file information
$exists = Storage::disk('viettel-s3')->exists($path);
$size = Storage::disk('viettel-s3')->size($path);
$lastModified = Storage::disk('viettel-s3')->lastModified($path);
$mimeType = Storage::disk('viettel-s3')->mimeType($path);
$url = Storage::disk('viettel-s3')->url($path);

echo "File: {$path}\n";
echo "Exists: " . ($exists ? 'Yes' : 'No') . "\n";
echo "Size: {$size} bytes\n";
echo "Last Modified: " . date('Y-m-d H:i:s', $lastModified) . "\n";
echo "MIME Type: {$mimeType}\n";
echo "URL: {$url}\n";
```

## 🔧 Advanced Configuration

### Custom User Agent

The driver uses a default user agent `viettel-cloud-s3/1.0 callback`. This is configured in the adapter and matches the working signature requirements.

### File Visibility and ACL

```php
// Upload with public visibility (adds x-amz-acl: public-read header)
Storage::disk('viettel-s3')->put($path, $content, [
    'visibility' => 'public'
]);

// Upload as private (default)
Storage::disk('viettel-s3')->put($path, $content);
// or explicitly
Storage::disk('viettel-s3')->put($path, $content, [
    'visibility' => 'private'
]);
```

**⚠️ VIPCore Limitation**: While the driver correctly sends ACL headers, VIPCore/EMC ViPR may not support anonymous public access like AWS S3. Files may still require authentication regardless of the ACL setting.

### Error Handling

```php
try {
    Storage::disk('viettel-s3')->put($path, $content);
    echo "Upload successful!";
} catch (\League\Flysystem\UnableToWriteFile $e) {
    echo "Upload failed: " . $e->getMessage();
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage();
}
```

## 🏗️ How It Works

### The Problem

Standard AWS SDK for PHP calculates signatures differently than what VIPCore/EMC ViPR S3-compatible services expect, causing `SignatureDoesNotMatch` errors.

### The Solution

This package provides a custom Flysystem adapter that:

1. **Manually calculates AWS v4 signatures** using the exact format expected by VIPCore
2. **Forces `UNSIGNED-PAYLOAD`** content hash (required by VIPCore)
3. **Uses direct cURL requests** bypassing AWS SDK signature issues
4. **Implements proper header formatting** based on working examples

### Key Components

- **Custom signature calculation** compatible with VIPCore/EMC ViPR
- **Proper canonical request formatting** with alphabetical header ordering
- **UNSIGNED-PAYLOAD handling** for all requests
- **cURL-based HTTP client** for direct control over requests

## 🔒 Security

- Uses AWS v4 signature validation
- Proper credential handling through Laravel configuration
- Request timestamp validation prevents replay attacks
- Content integrity checks with SHA256 hashing
- Supports both public and private file access controls

## 🧪 Testing

After installation, you can test the integration with a simple script:

```php
// Test basic functionality
$disk = Storage::disk('viettel-s3');

// Upload test
$testFile = 'test-' . time() . '.txt';
$testContent = 'Hello from Viettel Cloud S3!';

$disk->put($testFile, $testContent, ['visibility' => 'public']);

// Verify upload
if ($disk->exists($testFile)) {
    echo "✅ Upload successful\n";
    
    // Test download
    $downloadedContent = $disk->get($testFile);
    if ($downloadedContent === $testContent) {
        echo "✅ Download successful\n";
    }
    
    // Test URL generation
    $url = $disk->url($testFile);
    echo "📁 File URL: {$url}\n";
    
    // Cleanup
    $disk->delete($testFile);
    echo "🗑️ Cleanup completed\n";
}
```

## 🐛 Troubleshooting

### Common Issues

**1. SignatureDoesNotMatch Error**
- ✅ **Solved by this package!** The custom signature calculation handles VIPCore compatibility.

**2. File Upload Fails**
- Check your credentials in `.env`
- Verify bucket name and endpoint URL
- Ensure network connectivity to the endpoint

**3. File URLs Don't Work**
- Verify the `VIETTEL_S3_URL` environment variable
- Check if the bucket and file permissions are correct
- Remember that VIPCore may require authentication even for "public" files

**4. Large File Uploads**
- Use `putStream()` for large files instead of `put()`
- Consider implementing chunked uploads for files > 100MB

### Debug Mode

Enable debug logging in your Laravel application to see detailed request/response information:

```php
// In config/logging.php, set the default log level to 'debug'
'level' => 'debug',
```

Check `storage/logs/laravel.log` for detailed error information.

## 📋 Requirements

- **PHP**: ^8.2
- **Laravel**: ^10.0 || ^11.0  
- **League/Flysystem**: ^3.0

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🏢 Support

- **Issues**: [GitHub Issues](https://github.com/kaibatech/viettel-cloud-s3/issues)
- **Documentation**: This README and inline code documentation
- **Community**: Feel free to open discussions for questions and feature requests

## 🎯 Roadmap

- [ ] Add support for multipart uploads
- [ ] Implement proper directory listing (ListObjects API)
- [ ] Add comprehensive test suite
- [ ] Support for more VIPCore-specific features
- [ ] Performance optimizations and caching

---

**🎉 Happy coding with Viettel Cloud Object Storage!** 