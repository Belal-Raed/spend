<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List the shared default categories and the user's own, grouped by transaction type.
     */
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->visibleTo($request->user())
            ->orderByRaw('user_id is not null')
            ->inDisplayOrder()
            ->get()
            ->groupBy('type')
            ->map(fn ($categoriesOfType) => CategoryResource::collection($categoriesOfType));

        return Inertia::render('categories/index', ['categories' => $categories]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->safe()->only(['name', 'type']));

        Inertia::flash('status', 'تمت إضافة التصنيف.');

        return redirect()->route('categories.index');
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('categories/edit', [
            'category' => new CategoryResource($category),
            'typeLabel' => $category->type->label(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->safe()->only(['name']));

        Inertia::flash('status', 'تم تعديل التصنيف.');

        return redirect()->route('categories.index');
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

        Inertia::flash('status', 'تم حذف التصنيف.');

        return redirect()->route('categories.index');
    }
}
