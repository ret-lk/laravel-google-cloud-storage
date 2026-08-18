<?php

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\GoogleCloudStorage\GoogleCloudStorageAdapter;
use Spatie\GoogleCloudStorage\GoogleCloudStorageServiceProvider;

it('can create a gcs disk', function () {
    $disk = Storage::build([
        'driver' => 'gcs',
        'root' => fake()->uuid(),
        'bucket' => fake()->word(),
    ]);

    Storage::set('gcs', $disk);

    expect(Storage::disk('gcs'))->toBeInstanceOf(GoogleCloudStorageAdapter::class);
});

it('applies scoped prefixes to file operations and urls', function () {
    $object = Mockery::mock(StorageObject::class);
    $object->shouldReceive('exists')
        ->once()
        ->andReturnTrue();

    $bucket = Mockery::mock(Bucket::class);
    $bucket->shouldReceive('object')
        ->once()
        ->with('storage/app/public/campaigns/images/uuid/banner.jpg')
        ->andReturn($object);

    $client = Mockery::mock(StorageClient::class);
    $client->shouldReceive('bucket')
        ->once()
        ->with('example-bucket')
        ->andReturn($bucket);

    $provider = new GoogleCloudStorageServiceProvider(app());
    $config = $provider->prepareConfig([
        'bucket' => 'example-bucket',
        'root' => 'storage/app/',
        'prefix' => '/public/campaigns/images/',
        'storage_api_uri' => 'https://images.example.com',
    ]);
    $adapter = $provider->createAdapter($client, $config);
    $disk = new GoogleCloudStorageAdapter(
        new Filesystem($adapter, $config),
        $adapter,
        $config,
        $client,
    );

    expect($disk->exists('uuid/banner.jpg'))->toBeTrue()
        ->and($disk->url('uuid/banner.jpg'))
        ->toBe('https://images.example.com/storage/app/public/campaigns/images/uuid/banner.jpg');
});
