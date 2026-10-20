<?php

namespace App\Http\Controllers;

use App\Enums\Period;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private TransactionService $transactions) {}

    /**
     * List the user's transactions, newest first, optionally narrowed by period and category.
     *
     * A period or category that does not exist is ignored.
     */
    public function index(Request $request): Response
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

        return Inertia::render('transactions/index', [
            'transactions' => TransactionResource::collection($transactions),
            'period' => $period?->value,
            'periods' => array_map(fn (Period $option): array => ['value' => $option->value, 'label' => $option->label()], Period::cases()),
            'category' => $category?->id,
            'categoryGroups' => Category::query()
                ->visibleTo($user)
                ->inDisplayOrder()
                ->get()
                ->groupBy('type')
                ->map(fn ($categoriesOfType, string $type): array => [
                    'label' => TransactionType::from($type)->label(),
                    'categories' => CategoryResource::collection($categoriesOfType),
                ])
                ->values(),
        ]);
    }

    /**
     * Show the form for a new expense, income or debt. Defaults to an expense.
     */
    public function create(Request $request): Response
    {
        $type = TransactionType::tryFrom((string) $request->query('type')) ?? TransactionType::Expense;

        return Inertia::render('transactions/create', [
            'type' => $type->value,
            'types' => array_map(fn (TransactionType $option): array => ['value' => $option->value, 'label' => $option->label()], TransactionType::cases()),
            'categories' => CategoryResource::collection($this->selectableCategories($request->user(), $type)),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $this->transactions->record($request->user(), $request->validated());

        Inertia::flash('status', 'تم تسجيل الحركة.');

        return redirect()->route('transactions.index');
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('update', $transaction);

        $transaction->load('category:id,name');

        return Inertia::render('transactions/edit', [
            'transaction' => new TransactionResource($transaction),
            'categories' => CategoryResource::collection(
                $transaction->isGroupedByCategory() ? $this->selectableCategories($request->user(), $transaction->type) : new Collection,
            ),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->transactions->update($transaction, $request->validated());

        Inertia::flash('status', 'تم تعديل الحركة.');

        return redirect()->route('transactions.index');
    }

    /**
     * Delete the transaction. Deleting a debt deletes its payments too.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $this->transactions->delete($transaction);

        Inertia::flash('status', 'تم حذف الحركة.');

        return redirect()->route('transactions.index');
    }

    /**
     * @return Collection<int, Category>
     */
    private function selectableCategories(User $user, TransactionType $type): Collection
    {
        if (! $type->hasCategory()) {
            return new Collection;
        }

        return Category::query()->visibleTo($user)->ofType($type)->inDisplayOrder()->get();
    }
}
