# Changelog

All notable changes to `kaibatech/viettel-cloud-s3` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-30

### Added
- Support for Laravel 12.x compatibility
- Support for PHPUnit ^11.0 compatibility  
- Support for Orchestra Testbench ^10.0 for Laravel 12 testing

### Updated
- Updated GitHub Actions workflow to test Laravel 12 compatibility
- Extended compatibility matrix to include Laravel 10.x, 11.x, and 12.x
- Updated requirements to support Laravel ^10.0 || ^11.0 || ^12.0

### Requirements
- PHP ^8.2
- Laravel ^10.0 || ^11.0 || ^12.0
- League/Flysystem ^3.0

## [1.0.0] - 2025-05-26

### Added
- Initial release of Viettel Cloud Object Storage Laravel Storage Driver
- Custom Flysystem adapter for VIPCore/EMC ViPR S3-compatible endpoints
- AWS v4 signature calculation compatible with VIPCore requirements
- Support for UNSIGNED-PAYLOAD content hash (required by VIPCore)
- File upload, download, delete, and existence check operations
- Public/private file visibility support with ACL headers
- MIME type detection for uploaded files
- URL generation for file access
- Comprehensive error handling with Flysystem exceptions
- Laravel 10.x and 11.x compatibility
- Auto-discovery service provider registration
- Configurable via environment variables
- Full documentation and usage examples

### Features
- ✅ Upload files with `Storage::disk('viettel-s3')->put()`
- ✅ Download files with `Storage::disk('viettel-s3')->get()`  
- ✅ Check file existence with `Storage::disk('viettel-s3')->exists()`
- ✅ Generate URLs with `Storage::disk('viettel-s3')->url()`
- ✅ Delete files with `Storage::disk('viettel-s3')->delete()`
- ✅ File metadata retrieval (size, MIME type, last modified)
- ✅ Stream support for large files
- ✅ Batch operations for multiple files
- ✅ Public/private file visibility
- ✅ Custom signature calculation bypassing AWS SDK issues

### Technical Details
- Direct cURL implementation for HTTP requests
- Manual AWS v4 signature calculation
- Proper canonical request formatting
- Alphabetical header ordering for signatures
- Content integrity checks with SHA256
- Comprehensive logging for debugging

### Requirements
- PHP ^8.2
- Laravel ^10.0 || ^11.0
- League/Flysystem ^3.0

### Installation
```bash
composer require kaibatech/viettel-cloud-s3
``` 