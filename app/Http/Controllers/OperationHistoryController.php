<?php

namespace App\Http\Controllers;

use App\Models\OperationHistory;
use Illuminate\Contracts\View\View;

class OperationHistoryController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('history.index', [
            'history' => OperationHistory::with(['user', 'operatorToken'])
                ->latest()
                ->paginate(25),
        ]);
    }
}
