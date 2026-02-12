<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StateTransitionException extends Exception
{
    /**
     * 状態遷移エラーを生成
     */
    public function __construct(
        string $message = 'Invalid state transition',
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
            'error' => 'StateTransitionError',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }

    /**
     * ログに記録する情報
     */
    public function context(): array
    {
        return [
            'exception' => 'StateTransitionException',
            'code' => $this->getCode(),
        ];
    }
}
