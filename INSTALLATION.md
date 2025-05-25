# Installation Guide

This guide will walk you through installing and configuring the **Viettel Cloud Object Storage - Laravel Storage Driver** package.

## Prerequisites

- **PHP**: 8.1 or higher
- **Laravel**: 10.x or 11.x
- **Composer**: Latest version
- **Viettel Cloud Storage Credentials**: Access key, secret key, and endpoint

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require kaibatech/viettel-cloud-s3
```

The package uses Laravel's auto-discovery feature, so the service provider will be registered automatically.

## Step 2: Environment Configuration

Add the following environment variables to your `.env` file:

```env
# Viettel Cloud Object Storage Configuration
VIETTEL_S3_ACCESS_KEY_ID=your-access-key
VIETTEL_S3_SECRET_ACCESS_KEY=your-secret-key
VIETTEL_S3_REGION=us-east-1
VIETTEL_S3_BUCKET=your-bucket-name
VIETTEL_S3_ENDPOINT=https://vcos.cloudstorage.com.vn
VIETTEL_S3_URL=https://your-access-key.vcos.cloudstorage.com.vn/your-bucket-name
```

### Alternative: Reuse AWS Variables

If you're migrating from AWS S3, you can reuse existing variables:

```env
# Existing AWS variables (if you have them)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=https://vcos.cloudstorage.com.vn
AWS_URL=https://your-access-key.vcos.cloudstorage.com.vn/your-bucket-name
```

## Step 3: Filesystem Configuration

Add the Viettel S3 disk to your `config/filesystems.php`:

```php
'disks' => [
    // ... existing disks

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

### Using AWS Variables (Alternative)

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

## Step 4: Publish Configuration (Optional)

If you want to customize the default configuration:

```bash
php artisan vendor:publish --tag=viettel-cloud-s3-config
```

This will create `config/viettel-cloud-s3.php` where you can modify default settings.

## Step 5: Test the Installation

Create a simple test to verify everything works:

```php
<?php
// Create a test file: test_viettel_s3.php

use Illuminate\Support\Facades\Storage;

try {
    $disk = Storage::disk('viettel-s3');
    
    // Test upload
    $testFile = 'test-installation-' . time() . '.txt';
    $testContent = 'Hello from Viettel Cloud S3!';
    
    echo "Testing file upload...\n";
    $result = $disk->put($testFile, $testContent, ['visibility' => 'public']);
    
    if ($result) {
        echo "✅ Upload successful!\n";
        
        // Test existence
        if ($disk->exists($testFile)) {
            echo "✅ File exists check passed!\n";
            
            // Test download
            $downloadedContent = $disk->get($testFile);
            if ($downloadedContent === $testContent) {
                echo "✅ Download successful!\n";
            }
            
            // Test URL generation
            $url = $disk->url($testFile);
            echo "📁 File URL: {$url}\n";
            
            // Cleanup
            $disk->delete($testFile);
            echo "🗑️ File deleted successfully!\n";
        }
    }
    
    echo "🎉 Installation test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Please check your configuration and credentials.\n";
}
```

Run the test:

```bash
php test_viettel_s3.php
```

## Step 6: Integration with Your Application

Now you can use the Viettel S3 storage in your Laravel application:

```php
// In your controllers
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        
        $path = Storage::disk('viettel-s3')->putFileAs(
            'uploads',
            $file,
            $file->getClientOriginalName(),
            ['visibility' => 'public']
        );
        
        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('viettel-s3')->url($path)
        ]);
    }
}
```

## Step 7: Set as Default (Optional)

If you want to use Viettel S3 as your default filesystem:

```php
// In config/filesystems.php
'default' => 'viettel-s3',
```

Then you can use Storage without specifying the disk:

```php
Storage::put($path, $content);  // Uses viettel-s3 by default
```

## Troubleshooting Installation

### Common Issues

**1. "Driver [viettel-s3] not supported"**
- Run `composer dump-autoload`
- Clear Laravel cache: `php artisan cache:clear`
- Ensure the service provider is registered

**2. "Class not found" errors**
- Run `composer install` again
- Check if the package was installed properly: `composer show kaibatech/viettel-cloud-s3`

**3. Configuration issues**
- Verify all environment variables are set correctly
- Check that the `.env` file is being loaded
- Ensure no trailing spaces in environment values

**4. Permission errors**
- Verify your Viettel Cloud credentials
- Check that the bucket exists and is accessible
- Ensure network connectivity to the endpoint

### Debug Mode

Enable detailed logging by setting in your `.env`:

```env
LOG_LEVEL=debug
```

Then check `storage/logs/laravel.log` for detailed error information.

## Next Steps

- Review the [main README](README.md) for usage examples
- Check the [API documentation](src/ViettelCloudS3Adapter.php) for advanced features
- Consider setting up automated testing with your credentials

## Support

If you encounter any issues during installation:

1. Check the [troubleshooting section](#troubleshooting-installation)
2. Review the error logs in `storage/logs/laravel.log`
3. Open an issue on GitHub with detailed error information

---

**🎉 Congratulations! Your Viettel Cloud Object Storage is now ready to use.** 