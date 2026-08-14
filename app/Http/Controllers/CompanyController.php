<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    private function authorizeManage(): void
    {
        if (auth()->user()->hasRole('admin_view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $companies = Company::withCount('costCenters')->orderBy('name')->get();

        if ($request->filled('search')) {
            $search = Str::lower(trim((string) $request->search));
            $companies = $companies->filter(fn (Company $company) => Str::contains(
                Str::lower(implode(' ', [$company->name, $company->rfc, $company->account])),
                $search
            ));
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $companies = new LengthAwarePaginator(
            $companies->forPage($page, 10)->values(),
            $companies->count(),
            10,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $this->authorizeManage();

        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'rfc' => ['required', 'string', 'min:12', 'max:13'],
            'account' => ['required', 'string', 'max:255'],
        ]);

        Company::create([
            'name' => trim($data['name']),
            'rfc' => strtoupper(trim($data['rfc'])),
            'account' => trim($data['account']),
        ]);

        return redirect()->route('companies.index')->with('success', 'Empresa creada correctamente.');
    }

    public function edit(Company $company)
    {
        $this->authorizeManage();

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('companies')->ignore($company->id)],
            'rfc' => ['required', 'string', 'min:12', 'max:13'],
            'account' => ['required', 'string', 'max:255'],
        ]);

        $company->update([
            'name' => trim($data['name']),
            'rfc' => strtoupper(trim($data['rfc'])),
            'account' => trim($data['account']),
        ]);

        return redirect()->route('companies.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Company $company)
    {
        $this->authorizeManage();

        if ($company->costCenters()->exists()) {
            return redirect()->route('companies.index')->with('error', 'No se puede eliminar una empresa con centros de costos asociados.');
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Empresa eliminada correctamente.');
    }
}
