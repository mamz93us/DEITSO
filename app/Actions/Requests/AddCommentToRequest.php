<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\EmployeeRequestComment;
use App\Models\User;

class AddCommentToRequest
{
    public function __invoke(
        EmployeeRequest $request,
        User $author,
        string $body,
        string $authorRole = EmployeeRequestComment::AUTHOR_REQUESTER,
        bool $isInternal = false,
    ): EmployeeRequestComment {
        return EmployeeRequestComment::create([
            'employee_request_id' => $request->id,
            'user_id' => $author->id,
            'author_role' => $authorRole,
            'body' => $body,
            'is_internal' => $isInternal,
        ]);
    }
}
