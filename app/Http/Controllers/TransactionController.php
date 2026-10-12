<?php

namespace App\Http\Controllers;

use App\Enums\Period;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private TransactionService $transactions) {}

    /**
     * List the user's transactions, newest first, optionally narrowed by period and category.
     *
     * A period or category that does not exist is ignored.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $period = Period::tryFrom((string) $request->query('period'));
        $category = Category::query()->visibleTo($user)->find($request->integer('category'));

        $transactions = $user->transactions()
            ->with('category:id,name')
            ->when($period, function ($query) use ($period): void {
                [$from, $to] = $period->range(today());

                $query->occurredBetween($from, $to);
            })
            ->when($category, fn ($query) => $query->whereBelongsTo($category))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'period' => $period,
            'category' => $category,
            'categories' => Category::query()->visibleTo($user)->orderBy('name')->get()->groupBy('type'),
        ]);
    }

    /**
     * Show the form for a new expense, income or debt. Defaults to an expense.
     */
    public function create(Request $request): View
    {
        $type = TransactionType::tryFrom((string) $request->query('type')) ?? TransactionType::Expense;

        return view('transactions.create', [
            'type' => $type,
            'categories' => $this->selectableCategories($request->user(), $type),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $this->transactions->record($request->user(), $request->validated());

        return redirect()->route('transactions.index')->with('status', 'تم تسجيل الحركة.');
    }

    public function edit(Request $request, Transaction $transaction): View
    {
        Gate::authorize('update', $transaction);

        return view('transactions.edit', [
            'transaction' => $transaction,
            'categories' => $this->selectableCategories($request->user(), $transaction->type),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->transactions->update($transaction, $request->validated());

        return redirect()->route('transactions.index')->with('status', 'تم تعديل الحركة.');
    }

    /**
     * Delete the transaction. Deleting a debt deletes its payments too.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $this->transactions->delete($transaction);

        return redirect()->route('transactions.index')->with('status', 'تم حذف الحركة.');
    }

    /**
     * @return Collection<int, Category>
     */
    private function selectableCategories(User $user, TransactionType $type): Collection
    {
        if (! $type->hasCategory()) {
            return new Collection;
        }

        return Category::query()->visibleTo($user)->ofType($type)->orderBy('name')->get();
    }
}
