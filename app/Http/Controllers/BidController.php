<?php

namespace App\Http\Controllers;

use App\Http\Resources\BidResource;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BidController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'buyer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'auction_id' => ['required', 'integer', 'exists:auctions,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();

        $result = DB::transaction(function () use ($validated, $user): array {
            $auction = Auction::query()
                ->whereKey($validated['auction_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($message = $this->bidConflictMessage($auction)) {
                return ['conflict' => $message];
            }

            $existingBid = Bid::query()
                ->where('auction_id', $auction->id)
                ->where('user_id', $user->id)
                ->first();

            $currentPrice = $auction->current_price ?? $auction->starting_price;
            $minimumAmount = max(
                (float) $currentPrice,
                (float) ($existingBid?->amount ?? 0),
            );

            if ((float) $validated['amount'] <= $minimumAmount) {
                throw ValidationException::withMessages([
                    'amount' => ["The bid amount must be greater than {$minimumAmount}."],
                ]);
            }

            $bid = Bid::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'auction_id' => $auction->id,
                ],
                [
                    'amount' => $validated['amount'],
                ],
            );

            $auction->update([
                'current_price' => $validated['amount'],
                'winner_id' => $user->id,
            ]);

            return [
                'bid' => $bid->load('user'),
                'created' => $bid->wasRecentlyCreated,
                'auction' => $auction->fresh(['user', 'category', 'winner']),
            ];
        });

        if (isset($result['conflict'])) {
            return response()->json([
                'message' => $result['conflict'],
            ], 409);
        }

        return response()->json([
            'message' => $result['created']
                ? 'Bid created successfully.'
                : 'Bid updated successfully.',
            'bid' => new BidResource($result['bid']),
            'auction' => [
                'id' => $result['auction']->id,
                'current_price' => $result['auction']->current_price,
                'winner_id' => $result['auction']->winner_id,
            ],
        ], $result['created'] ? 201 : 200);
    }

    public function index(Request $request, Auction $auction): JsonResponse
    {
        $user = $request->user();

        if ($user?->role === 'buyer') {
            $bid = Bid::query()
                ->with('user')
                ->where('auction_id', $auction->id)
                ->where('user_id', $user->id)
                ->first();

            return response()->json([
                'auction_id' => $auction->id,
                'count' => $bid ? 1 : 0,
                'bid' => $bid ? new BidResource($bid) : null,
            ]);
        }

        if ($user?->role === 'seller' && $auction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bids = $auction->bids()
            ->with('user')
            ->orderByDesc('amount')
            ->get();

        return response()->json([
            'auction_id' => $auction->id,
            'count' => $bids->count(),
            'bids' => BidResource::collection($bids),
        ]);
    }

    private function bidConflictMessage(Auction $auction): ?string
    {
        if ($auction->status !== 'active') {
            return 'Bids can only be placed on active auctions.';
        }

        if (now()->lt($auction->starts_at)) {
            return 'Auction has not started yet.';
        }

        if (now()->gt($auction->ends_at)) {
            return 'Auction has already ended.';
        }

        return null;
    }
}
