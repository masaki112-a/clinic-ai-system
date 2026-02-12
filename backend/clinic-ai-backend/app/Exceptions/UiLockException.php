<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UiLockException extends Exception
{
    /**
     * UIロックエラーを生成
     */
    public function __construct(
        string $message = 'UI lock conflict',
        int $code = 409,
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
            'error' => 'UiLockError',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }

    /**
     * ログに記録する情報
     */
    public function context(): array
    {
        return [
            'exception' => 'UiLockException',
            'code' => $this->getCode(),
        ];
    }
}
