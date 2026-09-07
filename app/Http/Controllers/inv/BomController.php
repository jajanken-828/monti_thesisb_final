<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\man\BomRecord;
use App\Models\crm\Client;
use App\Models\inv\Material;
use App\Models\inv\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BomController extends Controller
{
    /**
     * Display a listing of recipe records (formerly BOM).
     */
    public function index()
    {
        $recipes = BomRecord::with('client', 'product')->get();
        $clients = Client::all();
        $products = Product::all();
        $materials = Material::all();

        return Inertia::render('Dashboard/Inventory/Bom', [
            'boms'      => $recipes,       // Keep prop name 'boms' for compatibility with the Vue file
            'clients'   => $clients,
            'products'  => $products,
            'materials' => $materials,
        ]);
    }

    /**
     * Store a newly created recipe.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'product_id'   => 'required|exists:products,id',
            'yarn_type'    => 'required|string',
            'dye_color'    => 'required|string',
            'weave_design' => 'required|string',
            'materials'    => 'required|array',
        ]);

        BomRecord::updateOrCreate(
            ['client_id' => $data['client_id'], 'product_id' => $data['product_id']],
            $data
        );

        return redirect()->back()->with('success', 'Recipe saved successfully.');
    }

    /**
     * Update the specified recipe.
     */
    public function update(Request $request, $id)
    {
        $recipe = BomRecord::findOrFail($id);

        $data = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'product_id'   => 'required|exists:products,id',
            'yarn_type'    => 'required|string',
            'dye_color'    => 'required|string',
            'weave_design' => 'required|string',
            'materials'    => 'required|array',
        ]);

        $recipe->update($data);

        return redirect()->back()->with('success', 'Recipe updated successfully.');
    }

    /**
     * Remove the specified recipe.
     */
    public function destroy($id)
    {
        $recipe = BomRecord::findOrFail($id);
        $recipe->delete();

        return redirect()->back()->with('success', 'Recipe deleted successfully.');
    }
}