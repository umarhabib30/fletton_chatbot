<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class OpenAIAssistantController extends Controller
{
    private Client $http;
    private string $base;
    private string $key;

    public function __construct()
    {
        $this->base = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $this->key  = config('services.openai.key');

        $this->http = new Client([
            'base_uri' => $this->base . '/',
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->key,
                'OpenAI-Beta'   => 'assistants=v2',   // <-- REQUIRED
            ],
            'http_errors' => false,
            'timeout' => 90,
        ]);
    }

    /**
     * POST /openai/assistants
     * Body JSON:
     * {
     *   "name": "Docs Helper",
     *   "model": "gpt-4o-mini",
     *   "instructions": "Answer strictly from attached docs.",
     *   "enable_file_search": true,               // default true
     *   "vector_store_ids": ["vs_123","vs_456"]   // optional
     * }
     */
    public function createAssistant(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string',
            'model' => 'required|string',
            'instructions' => 'nullable|string',
            'enable_file_search' => 'nullable|boolean',
            'vector_store_ids' => 'nullable|array',
            'vector_store_ids.*' => 'string',
        ]);

        $enableFileSearch = $data['enable_file_search'] ?? true;

        $payload = [
            'name' => $data['name'],
            'model' => $data['model'],
        ];

        if (!empty($data['instructions'])) {
            $payload['instructions'] = $data['instructions'];
        }

        if ($enableFileSearch) {
            $payload['tools'] = [['type' => 'file_search']];
            // Attach vector stores if provided
            if (!empty($data['vector_store_ids'])) {
                $payload['tool_resources'] = [
                    'file_search' => [
                        'vector_store_ids' => $data['vector_store_ids'],
                    ],
                ];
            }
        }

        $res = $this->http->post('assistants', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
        ]);

        $status = $res->getStatusCode();
        $body = json_decode((string) $res->getBody(), true);

        return response()->json($body, $status);
    }

    /**
     * POST /openai/vector-stores
     * Body JSON:
     * {
     *   "name": "Company KB"
     * }
     */
    public function createVectorStore(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string',
        ]);

        $res = $this->http->post('vector_stores', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['name' => $data['name']],
        ]);

        $status = $res->getStatusCode();
        $body = json_decode((string) $res->getBody(), true);

        return response()->json($body, $status);
    }

    /**
     * POST /openai/vector-stores/{vectorStoreId}/files
     * Multipart form-data:
     *   files[]: (one or many uploaded files)
     *
     * Steps:
     *   1) Upload each file to /v1/files (purpose=assistants)
     *   2) Add each file_id to the vector store
     */
    public function addFilesToVectorStore(Request $r, string $vectorStoreId)
    {
        $r->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:51200', // 50MB per file (adjust as needed)
        ]);

        $uploaded = [];
        $attached = [];

        foreach ($r->file('files') as $upload) {
            // 1) Upload file to OpenAI Files API
            $resUpload = $this->http->post('files', [
                'multipart' => [
                    [
                        'name' => 'purpose',
                        'contents' => 'assistants',
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($upload->getRealPath(), 'r'),
                        'filename' => $upload->getClientOriginalName(),
                    ],
                ]
            ]);

            $statusUpload = $resUpload->getStatusCode();
            $bodyUpload = json_decode((string) $resUpload->getBody(), true);

            if ($statusUpload >= 200 && $statusUpload < 300 && !empty($bodyUpload['id'])) {
                $uploaded[] = $bodyUpload;

                // 2) Attach file to Vector Store
                $resAttach = $this->http->post("vector_stores/{$vectorStoreId}/files", [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => ['file_id' => $bodyUpload['id']],
                ]);

                $statusAttach = $resAttach->getStatusCode();
                $bodyAttach = json_decode((string) $resAttach->getBody(), true);

                if ($statusAttach >= 200 && $statusAttach < 300) {
                    $attached[] = $bodyAttach;
                } else {
                    // Optional: Attempt cleanup (delete uploaded file) or just report error
                    return response()->json([
                        'error' => 'Failed to attach file to vector store',
                        'details' => $bodyAttach,
                        'uploaded_file' => $bodyUpload,
                    ], $statusAttach);
                }
            } else {
                return response()->json([
                    'error' => 'Failed to upload file to OpenAI',
                    'details' => $bodyUpload,
                ], $statusUpload);
            }
        }

        return response()->json([
            'vector_store_id' => $vectorStoreId,
            'files_uploaded' => $uploaded,
            'files_attached' => $attached,
            'message' => 'Files uploaded and attached to the vector store successfully.',
        ], 200);
    }
}
