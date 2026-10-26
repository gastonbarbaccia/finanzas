<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\FinanceMetrics;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $spent = (new FinanceMetrics($request->user()))->byCategory(now())->keyBy('name');

        return view('categories.index', [
            'categories' => $request->user()->categories()->withCount('expenses')->get(),
            'spent'      => $spent,
        ]);
    }

    public function store(Request $request)
    {
        $request->user()->categories()->create($this->validated($request));

        return back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);
        $category->delete(); // los gastos quedan "sin categoría"

        return back()->with('success', 'Categoría eliminada. Sus gastos quedaron sin categoría.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('categories')->where('user_id', $request->user()->id)->ignore($category?->id),
            ],
            'color'          => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ]);
    }

    private function authorizeOwner(Request $request, Category $category): void
    {
        abort_unless($category->user_id === $request->user()->id, 404);
    }
}
