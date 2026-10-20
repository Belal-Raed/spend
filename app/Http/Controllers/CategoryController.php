<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * List the shared default categories and the user's own, grouped by transaction type.
     */
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->visibleTo($request->user())
            ->orderByRaw('user_id is not null')
            ->inDisplayOrder()
            ->get()
            ->groupBy('type');

        return view('categories.index', ['categories' => $categories]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->safe()->only(['name', 'type']));

        return redirect()->route('categories.index')->with('status', 'تمت إضافة التصنيف.');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->safe()->only(['name']));

        return redirect()->route('categories.index')->with('status', 'تم تعديل التصنيف.');
    }

    /**
     * Delete one of the user's categories, unless transactions still use it.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $transactionsCount = $category->transactions()->count();

        if ($transactionsCount > 0) {
            return redirect()->route('categories.index')->withErrors([
                'category' => "لا يمكن حذف «{$category->name}» لأنه مستخدم في {$transactionsCount} حركة. غيّر تصنيف هذه الحركات أولًا.",
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'تم حذف التصنيف.');
    }
}
