<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuctionResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const AUCTION_STATUSES = ['draft', 'active', 'finished', 'cancelled'];

    private const AUCTION_SORTABLE_FIELDS = [
        'title',
        'status',
        'starting_price',
        'current_price',
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = Category::all();

        return response()->json([
            'count' => $categories->count(),
            'categories' => CategoryResource::collection($categories)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['required', 'string'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'category' => new CategoryResource($category),
        ]);
    }

    public function auctions(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(self::AUCTION_STATUSES)],
            'seller_id' => ['sometimes', 'integer', 'exists:users,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'winner_id' => ['sometimes', 'integer', 'exists:users,id'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'gte:min_price'],
            'starts_from' => ['sometimes', 'date'],
            'starts_until' => ['sometimes', 'date'],
            'ends_from' => ['sometimes', 'date'],
            'ends_until' => ['sometimes', 'date'],
            'sort_by' => ['sometimes', Rule::in(self::AUCTION_SORTABLE_FIELDS)],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = $category->auctions()
            ->with(['user', 'category', 'winner']);

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
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
            'category' => new CategoryResource($category),
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => ['sometimes', 'string'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'Nothing to update.',
                'category' => new CategoryResource($category),
            ]);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }
}
