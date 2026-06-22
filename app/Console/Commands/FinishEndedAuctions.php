<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinishEndedAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:finish-ended';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finish active auctions whose end time has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $finishedCount = 0;

        Auction::query()
            ->where('status', 'active')
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($auctions) use (&$finishedCount): void {
                foreach ($auctions as $auction) {
                    DB::transaction(function () use ($auction, &$finishedCount): void {
                        $lockedAuction = Auction::query()
                            ->whereKey($auction->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedAuction || $lockedAuction->status !== 'active' || $lockedAuction->ends_at->gt(now())) {
                            return;
                        }

                        $highestBid = $lockedAuction->bids()
                            ->orderByDesc('amount')
                            ->orderBy('updated_at')
                            ->first();

                        $lockedAuction->update([
                            'status' => 'finished',
                            'current_price' => $highestBid?->amount,
                            'winner_id' => $highestBid?->user_id,
                        ]);

                        $finishedCount++;
                    });
                }
            });

        $this->info("Finished {$finishedCount} auctions.");

        return self::SUCCESS;
    }
}
