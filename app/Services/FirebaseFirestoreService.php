<?php

declare(strict_types=1);

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use RuntimeException;

class FirebaseFirestoreService
{
    private FirestoreClient $firestore;

    public function __construct()
    {
        $credentialsPath = base_path(
            config('firebase.projects.app.credentials')
        );

        if (! is_file($credentialsPath)) {
            throw new RuntimeException(
                "Firebase credentials file not found: {$credentialsPath}"
            );
        }

        $credentials = json_decode(
            file_get_contents($credentialsPath),
            true
        );

        if (! is_array($credentials)) {
            throw new RuntimeException(
                'Invalid Firebase credentials JSON.'
            );
        }

        $authCredentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/cloud-platform'],
            $credentials
        );

        $this->firestore = new FirestoreClient([
            'projectId' => config('firebase.projects.app.project_id'),
            'credentials' => $authCredentials,

            // REST transport is used because gRPC is
            // causing a native PHP crash on this Windows setup.
            'transport' => 'rest',
        ]);
    }

    public function collection(string $collection): CollectionReference
    {
        return $this->firestore->collection($collection);
    }

    public function create(string $collection, array $data): array
    {
        $document = $this->collection($collection)->newDocument();

        $document->set($data);

        return [
            'id' => $document->id(),
            ...$data,
        ];
    }

    public function getAll(string $collection): array
    {
        $documents = $this->collection($collection)->documents();
        $items = [];

        foreach ($documents as $document) {
            if (! $document->exists()) {
                continue;
            }

            $items[] = [
                'id' => $document->id(),
                ...$document->data(),
            ];
        }

        return $items;
    }

    public function find(string $collection, string $id): ?array
    {
        $document = $this->document($collection, $id)->snapshot();

        if (! $document->exists()) {
            return null;
        }

        return [
            'id' => $document->id(),
            ...$document->data(),
        ];
    }

    public function update(string $collection, string $id, array $data): ?array
    {
        if ($this->find($collection, $id) === null) {
            return null;
        }

        $this->document($collection, $id)->set($data, [
            'merge' => true,
        ]);

        return $this->find($collection, $id);
    }

    public function delete(string $collection, string $id): bool
    {
        if ($this->find($collection, $id) === null) {
            return false;
        }

        $this->document($collection, $id)->delete();

        return true;
    }

    private function document(string $collection, string $id): DocumentReference
    {
        return $this->collection($collection)->document($id);
    }
}
