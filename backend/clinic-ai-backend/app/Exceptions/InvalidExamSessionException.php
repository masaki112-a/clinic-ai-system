<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvalidExamSessionException extends Exception
{
    /**
     * 診察セッションエラーを生成
     */
    public function __construct(
        string $message = 'Invalid exam session state',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * HTTPレスポンスにレンダリング
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'InvalidExamSessionError',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }

    /**
     * ログに記録する情報
     */
    public function context(): array
    {
        return [
            'exception' => 'InvalidExamSessionException',
            'code' => $this->getCode(),
        ];
    }
}
