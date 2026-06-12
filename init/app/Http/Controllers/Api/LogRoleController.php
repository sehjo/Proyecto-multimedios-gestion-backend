<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogRoleResource;
use App\Http\Responses\GlobalResponseConst;
use App\Http\Responses\LogResponseConst;
use App\Models\LogRole;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogRoleController extends Controller
{
    /**
     * List roles audit log.
     *
     * Returns the paginated roles audit log (most recent first).
     * Requires the `logs_roles.read` permission.
     */
    #[Response(
        status: LogResponseConst::ROLE_LOGS['status'],
        description: LogResponseConst::ROLE_LOGS['description'],
        examples: [LogResponseConst::ROLE_LOGS['examples']],
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
        $logs = LogRole::with('performedBy')
            ->orderByDesc('timestamp')
            ->paginate();

        return LogRoleResource::collection($logs);
    }
}
