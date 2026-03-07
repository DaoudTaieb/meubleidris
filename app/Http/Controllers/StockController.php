<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Site;
use App\Models\Famille;
use App\Models\Sousfamille;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::select('stockid', 'produitid', 'siteid', 'qtestock')
            ->with([
                'produit' => function ($q) {
                    $q->select('produitid', 'produitlibelle', 'produitcode', 'familleid', 'sousfamilleid', 'fournisseurid', 'pmarque');
                },
                'produit.famille:familleid,famillelibelle',
                'produit.sousfamille:sousfamilleid,sousfamillelibelle',
                'produit.fournisseur:fournisseurid,nom',
                'site' => function ($q) {
                    $q->select('siteid', 'libelle as sitelibelle');
                }
            ]);

        // Filtering
        if ($request->filled('siteid')) {
            $query->where('siteid', $request->siteid);
        }

        if ($request->filled('familleid')) {
            $query->whereHas('produit', function ($q) use ($request) {
                $q->where('familleid', $request->familleid);
            });
        }

        if ($request->filled('sousfamilleid')) {
            $query->whereHas('produit', function ($q) use ($request) {
                $q->where('sousfamilleid', $request->sousfamilleid);
            });
        }

        if ($request->filled('fournisseurid')) {
            $query->whereHas('produit', function ($q) use ($request) {
                $q->where('fournisseurid', $request->fournisseurid);
            });
        }

        if ($request->filled('marque')) {
            $query->whereHas('produit', function ($q) use ($request) {
                $q->where('pmarque', 'LIKE', '%' . $request->marque . '%');
            });
        }

        if ($request->filled('search')) {
            $query->whereHas('produit', function ($q) use ($request) {
                $q->where('produitlibelle', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('produitcode', 'LIKE', '%' . $request->search . '%');
            });
        }

        $stocks = $query->get();

        return Inertia::render('Stock', [
            'stocks' => $stocks,
            'sites' => Site::select('siteid', 'libelle as sitelibelle')->get(),
            'familles' => Famille::select('familleid', 'famillelibelle')->get(),
            'sousfamilles' => Sousfamille::select('sousfamilleid', 'sousfamillelibelle', 'familleid')->get(),
            'fournisseurs' => Fournisseur::select('fournisseurid', 'nom')->get(),
            'filters' => $request->only(['siteid', 'familleid', 'sousfamilleid', 'fournisseurid', 'marque', 'search']),
        ]);
    }
}
