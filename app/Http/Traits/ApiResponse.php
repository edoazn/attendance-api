<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    /**
     * Return a success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error response
     *
     * @param string $message
     * @param array $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a resource response
     *
     * @param JsonResource $resource
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function resource(JsonResource $resource, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $resource,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a collection response
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $collection
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function collection(\Illuminate\Http\Resources\Json\AnonymousResourceCollection $collection, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $collection,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a paginated response
     *
     * @param ResourceCollection $collection (must be paginated)
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function paginated(ResourceCollection $collection, ?string $message = null, int $status = 200): JsonResponse
    {
        $resourceData = $collection->response()->getData(true);

        $response = [
            'success' => true,
            'data' => $resourceData['data'],
        ];

        if (isset($resourceData['meta'])) {
            $response['meta'] = $resourceData['meta'];
        }

        if (isset($resourceData['links'])) {
            $response['links'] = $resourceData['links'];
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }
}
