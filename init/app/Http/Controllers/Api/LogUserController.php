<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogUserResource;
use App\Http\Responses\GlobalResponseConst;
use App\Http\Responses\LogResponseConst;
use App\Models\LogUser;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogUserController extends Controller
{
    /**
     * List users audit log.
     *
     * Returns the paginated users audit log (most recent first).
     * Requires the `logs_users.read` permission.
     */
    #[Response(
        status: LogResponseConst::USER_LOGS['status'],
        description: LogResponseConst::USER_LOGS['description'],
        examples: [LogResponseConst::USER_LOGS['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::UNAUTHENTICATED['status'],
        description: GlobalResponseConst::UNAUTHENTICATED['description'],
        examples: [GlobalResponseConst::UNAUTHENTICATED['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::FORBIDDEN['status'],
        description: GlobalResponseConst::FORBIDDEN['description'],
        examples: [GlobalResponseConst::FORBIDDEN['examples']],
    )]
    public function index(): AnonymousResourceCollection
    {
        $logs = LogUser::with(['performedBy', 'targetUser'])
            ->orderByDesc('timestamp')
            ->paginate();

        return LogUserResource::collection($logs);
    }
}
