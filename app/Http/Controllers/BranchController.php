<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $branches = Branch::when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('branches.index', compact('branches', 'search'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:150|unique:branches,name',
            'address' => 'nullable|string|max:255',
        ]);

        Branch::create([
            'name'      => $request->name,
            'address'   => $request->address,
            'is_active' => true,
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name'    => 'required|string|max:150|unique:branches,name,' . $branch->id,
            'address' => 'nullable|string|max:255',
        ]);

        $branch->update([
            'name'      => $request->name,
            'address'   => $request->address,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function show(Branch $branch)
    {
        $branch->load('inventory.finishedProduct');
        return view('branches.show', compact('branch'));
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')
            ->with('success', 'Branch deleted.');
    }
}