<?php

namespace App\Http\Controllers;

use App\Models\OperatorToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OperatorTokenController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('operators.index', [
            'operatorTokens' => OperatorToken::latest()->get(),
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        OperatorToken::create([
            'name' => $validated['name'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('operators.index')->with('status', 'Operator link created.');
    }

    /**
     * @param OperatorToken $operatorToken
     * @return RedirectResponse
     */
    public function destroy(OperatorToken $operatorToken): RedirectResponse
    {
        $operatorToken->revoke();

        return redirect()->route('operators.index')->with('status', 'Operator link revoked.');
    }

    /**
     * Unlike the scheduled job (which waits out a grace period before
     * archiving, in case a link was revoked/expired by mistake), a manual
     * click here archives every currently revoked/expired link right away.
     *
     * @return RedirectResponse
     */
    public function prune(): RedirectResponse
    {
        $pruned = OperatorToken::pruneInactive(0);

        $message = $pruned === 0
            ? 'No revoked or expired links to archive.'
            : "Archived {$pruned} revoked/expired operator link" . ($pruned === 1 ? '' : 's') . '.';

        return redirect()->route('operators.index')->with('status', $message);
    }
}
