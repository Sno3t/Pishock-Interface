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
        ]);

        OperatorToken::create(['name' => $validated['name']]);

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
}
