<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuctionController extends Controller
{
    private const STATUSES = ['draft', 'active', 'finished', 'cancelled'];

    private const CREATABLE_STATUSES = ['draft', 'active'];

    private const SORTABLE_FIELDS = [
        'title',
        'status',
        'starting_price',
        'current_price',
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    private const NEVER_EDITABLE_FIELDS = [
        'user_id',
        'current_price',
        'winner_id',
    ];

    private const UPDATEABLE_FIELDS_BY_STATUS = [
        'draft' => [
            'category_id',
            'title',
            'description',
            'starting_price',
            'starts_at',
            'ends_at',
            'status',
        ],
        'active' => [
            'description',
            'ends_at',
            'status',
        ],
        'finished' => [],
        'cancelled' => [],
    ];

    private const STATUS_TRANSITIONS = [
        'draft' => ['active', 'cancelled'],
        'active' => ['finished', 'cancelled'],
        'finished' => [],
        'cancelled' => [],
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'seller_id' => ['sometimes', 'integer', 'exists:users,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'winner_id' => ['sometimes', 'integer', 'exists:users,id'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'gte:min_price'],
            'starts_from' => ['sometimes', 'date'],
            'starts_until' => ['sometimes', 'date'],
            'ends_from' => ['sometimes', 'date'],
            'ends_until' => ['sometimes', 'date'],
            'sort_by' => ['sometimes', Rule::in(self::SORTABLE_FIELDS)],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = Auction::query()
            ->with(['user', 'category', 'winner']);

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        $sellerId = $validated['seller_id'] ?? $validated['user_id'] ?? null;

        if ($sellerId !== null) {
            $query->where('user_id', $sellerId);
        }

        if (isset($validated['winner_id'])) {
            $query->where('winner_id', $validated['winner_id']);
        }

        if (isset($validated['min_price'])) {
            $query->where('starting_price', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('starting_price', '<=', $validated['max_price']);
        }

        if (isset($validated['starts_from'])) {
            $query->where('starts_at', '>=', $validated['starts_from']);
        }

        if (isset($validated['starts_until'])) {
            $query->where('starts_at', '<=', $validated['starts_until']);
        }

        if (isset($validated['ends_from'])) {
            $query->where('ends_at', '>=', $validated['ends_from']);
        }

        if (isset($validated['ends_until'])) {
            $query->where('ends_at', '<=', $validated['ends_until']);
        }

        $auctions = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'count' => $auctions->count(),
            'total' => $auctions->total(),
            'per_page' => $auctions->perPage(),
            'current_page' => $auctions->currentPage(),
            'last_page' => $auctions->lastPage(),
            'sort' => [
                'by' => $sortBy,
                'direction' => $sortDirection,
            ],
            'filters' => $request->only([
                'search',
                'status',
                'category_id',
                'seller_id',
                'user_id',
                'winner_id',
                'min_price',
                'max_price',
                'starts_from',
                'starts_until',
                'ends_from',
                'ends_until',
            ]),
            'auctions' => AuctionResource::collection($auctions->getCollection()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'user_id' => ['prohibited'],
            'winner_id' => ['prohibited'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'starting_price' => ['required', 'numeric', 'min:0'],
            'current_price' => ['prohibited'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', Rule::in(self::CREATABLE_STATUSES)],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['current_price'] = null;
        $validated['status'] ??= 'draft';

        $auction = Auction::create($validated)->load(['user', 'category', 'winner']);

        return response()->json([
            'message' => 'Auction created successfully.',
            'auction' => new AuctionResource($auction),
        ], 201);
    }

    public function show(Auction $auction): JsonResponse
    {
        $auction->load(['user', 'category', 'winner']);

        return response()->json([
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function update(Request $request, Auction $auction): JsonResponse
    {
        if (! $this->canManage($request, $auction)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->ensureAuctionCanBeUpdated($request, $auction);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'user_id' => ['prohibited'],
            'winner_id' => ['prohibited'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'starting_price' => ['sometimes', 'numeric', 'min:0'],
            'current_price' => ['prohibited'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
        ]);

        if ($validated === []) {
            $auction->load(['user', 'category', 'winner']);

            return response()->json([
                'message' => 'Nothing to update.',
                'auction' => new AuctionResource($auction),
            ]);
        }

        $this->ensureEndsAfterStarts($validated, $auction);
        $this->ensureStatusTransitionIsAllowed($validated, $auction);

        $auction->update($validated);
        $auction->load(['user', 'category', 'winner']);

        return response()->json([
            'message' => 'Auction updated successfully.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function destroy(Request $request, Auction $auction): JsonResponse
    {
        if (! $this->canManage($request, $auction)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! in_array($auction->status, ['draft', 'cancelled'], true)) {
            return response()->json([
                'message' => 'Only draft or cancelled auctions can be deleted.',
            ], 409);
        }

        $auction->delete();

        return response()->json([
            'message' => 'Auction deleted successfully.',
        ]);
    }

    private function canManage(Request $request, Auction $auction): bool
    {
        $user = $request->user();

        return $user?->role === 'admin' || $auction->user_id === $user?->id;
    }

    private function ensureAuctionCanBeUpdated(Request $request, Auction $auction): void
    {
        $submittedFields = array_keys($request->all());

        $neverEditableFields = array_intersect($submittedFields, self::NEVER_EDITABLE_FIELDS);

        if ($neverEditableFields !== []) {
            throw ValidationException::withMessages(
                collect($neverEditableFields)
                    ->mapWithKeys(fn(string $field): array => [
                        $field => ['This field cannot be updated manually.'],
                    ])
                    ->all()
            );
        }

        $allowedFields = self::UPDATEABLE_FIELDS_BY_STATUS[$auction->status] ?? [];

        if ($allowedFields === []) {
            throw ValidationException::withMessages([
                'status' => ["{$auction->status} auctions cannot be updated."],
            ]);
        }

        $knownEditableFields = collect(self::UPDATEABLE_FIELDS_BY_STATUS)
            ->flatten()
            ->unique()
            ->all();

        $blockedFields = array_diff(
            array_intersect($submittedFields, $knownEditableFields),
            $allowedFields,
        );

        if ($blockedFields !== []) {
            throw ValidationException::withMessages(
                collect($blockedFields)
                    ->mapWithKeys(fn(string $field): array => [
                        $field => ["This field cannot be updated while the auction is {$auction->status}."],
                    ])
                    ->all()
            );
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureEndsAfterStarts(array $validated, Auction $auction): void
    {
        $startsAt = Carbon::parse($validated['starts_at'] ?? $auction->starts_at);
        $endsAt = Carbon::parse($validated['ends_at'] ?? $auction->ends_at);

        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['The ends at must be after starts at.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureStatusTransitionIsAllowed(array $validated, Auction $auction): void
    {
        if (! array_key_exists('status', $validated) || $validated['status'] === $auction->status) {
            return;
        }

        $allowedStatuses = self::STATUS_TRANSITIONS[$auction->status] ?? [];

        if (! in_array($validated['status'], $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Status cannot be changed from {$auction->status} to {$validated['status']}."],
            ]);
        }
    }
}
