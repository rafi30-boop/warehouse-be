<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Upload')]
class FileController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:upload');
    }

    #[OA\Post(
        path: '/api/upload',
        summary: 'Upload file (dokumen, gambar, dll.)',
        tags: ['Upload'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(properties: [
                new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File yang diupload (max 10MB)'),
            ]),
        )),
        responses: [
            new OA\Response(response: 200, description: 'File uploaded', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'File berhasil diupload'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/uploads/xxx.pdf'),
                    new OA\Property(property: 'path', type: 'string', example: 'uploads/xxx.pdf'),
                    new OA\Property(property: 'name', type: 'string', example: 'xxx.pdf'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
        ]);

        $file = $validated['file'];
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $name, 'public');

        return $this->success([
            'url' => url('storage/'.$path),
            'path' => $path,
            'name' => $name,
        ], 'File berhasil diupload');
    }
}
