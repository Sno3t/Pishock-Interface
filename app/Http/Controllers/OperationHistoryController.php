<?php

namespace App\Http\Controllers;

use App\Models\OperationHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OperationHistoryController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $entries = OperationHistory::with(['user', 'operatorToken'])
            ->latest()
            ->get();

        $commands = $this->groupIntoCommands($entries);

        $page = $request->integer('page', 1);

        $history = new LengthAwarePaginator(
            $commands->forPage($page, self::PER_PAGE),
            $commands->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('history.index', ['history' => $history]);
    }

    /**
     * Manually run the same archiving that otherwise happens on a schedule,
     * instead of waiting for it.
     *
     * @return RedirectResponse
     */
    public function prune(): RedirectResponse
    {
        $days = (int) config('pishock.history_retention_days');

        if ($days <= 0) {
            return redirect()->route('history.index')
                ->with('status', 'Pruning is disabled (set PISHOCK_HISTORY_RETENTION_DAYS to enable it).');
        }

        $pruned = OperationHistory::pruneOld($days);

        $message = $pruned === 0
            ? "No history older than {$days} day(s) to archive."
            : "Archived {$pruned} history " . str('entry')->plural($pruned) . " older than {$days} day(s).";

        return redirect()->route('history.index')->with('status', $message);
    }

    /**
     * A single "send command" click can produce two OperationHistory rows
     * (duration and intensity), sent by the same actor in the same request.
     * Merge those back into one entry for display, instead of showing the
     * duration and intensity as two unrelated-looking rows.
     *
     * @param Collection<int, OperationHistory> $entries
     * @return Collection<int, array{operation: string, who: string, created_at: \Illuminate\Support\Carbon, values: array<string, int>}>
     */
    private function groupIntoCommands(Collection $entries): Collection
    {
        $commands = [];

        foreach ($entries as $entry) {
            $last = $commands ? $commands[array_key_last($commands)] : null;

            $sameCommand = $last
                && $last['operation'] === $entry->operation
                && $last['user_id'] === $entry->user_id
                && $last['operator_token_id'] === $entry->operator_token_id
                && $last['created_at']->equalTo($entry->created_at)
                && ! isset($last['values'][$entry->type]);

            if ($sameCommand) {
                $commands[array_key_last($commands)]['values'][$entry->type] = $entry->value;

                continue;
            }

            $commands[] = [
                'operation' => $entry->operation,
                'created_at' => $entry->created_at,
                'who' => $entry->user?->name ?? $entry->operatorToken?->name ?? 'Unknown',
                'succeeded' => $entry->succeeded,
                'devices' => $entry->devices ?? [],
                'user_id' => $entry->user_id,
                'operator_token_id' => $entry->operator_token_id,
                'values' => [$entry->type => $entry->value],
            ];
        }

        return collect($commands);
    }
}
