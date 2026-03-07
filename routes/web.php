<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\LoginController;
use Inertia\Inertia;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        $fournisseurs = DB::table('fournisseurs')
            ->leftJoin('fournisseurfamilles', 'fournisseurs.fournisseurfamilleid', '=', 'fournisseurfamilles.fournisseurfamilleid')
            ->select('fournisseurs.*', 'fournisseurfamilles.fournisseurfamillelibelle as famille')
            ->addSelect(DB::raw('
            (COALESCE(fournisseurs.soldeinitial, 0) + 
             COALESCE((SELECT SUM(netapayer) FROM ffactures WHERE fournisseurid = fournisseurs.fournisseurid), 0) +
             COALESCE((SELECT SUM(netapayer) FROM fbls WHERE fournisseurid = fournisseurs.fournisseurid AND transfere = false), 0) -
             COALESCE((SELECT SUM(montant) FROM freglements WHERE fournisseurid = fournisseurs.fournisseurid), 0) -
             COALESCE((SELECT SUM(netapayer) FROM favoirs WHERE fournisseurid = fournisseurs.fournisseurid), 0) -
             COALESCE((SELECT SUM(netapayer) FROM fbrs WHERE fournisseurid = fournisseurs.fournisseurid AND transfere = false), 0)
            ) as calculated_solde
        '))
            ->orderBy('calculated_solde', 'desc')
            ->get();

        foreach ($fournisseurs as $f) {
            $f->solde = $f->calculated_solde;
        }

        return \Inertia\Inertia::render('Dashboard', [
            'fournisseurs' => $fournisseurs
        ]);
    });

    Route::get('/fournisseur/{id}/releve', function ($id) {
        $fournisseur = DB::table('fournisseurs')
            ->leftJoin('fournisseurfamilles', 'fournisseurs.fournisseurfamilleid', '=', 'fournisseurfamilles.fournisseurfamilleid')
            ->select('fournisseurs.*', 'fournisseurfamilles.fournisseurfamillelibelle as famille')
            ->where('fournisseurid', $id)
            ->first();

        if (!$fournisseur)
            abort(404);

        $factures = DB::table('ffactures')
            ->where('fournisseurid', $id)
            ->select('ffacturedate as date', DB::raw("'Facture' as libelle"), DB::raw("CAST(ffacturenumero AS TEXT) as numero"), DB::raw("0 as debit"), 'netapayer as credit', DB::raw("ffactureid as doc_id"), DB::raw("'facture' as type_slug"));

        $reglements = DB::table('freglements')
            ->where('fournisseurid', $id)
            ->select('date', DB::raw("'Règlement' as libelle"), DB::raw("CAST(numero AS TEXT) as numero"), 'montant as debit', DB::raw("0 as credit"), DB::raw("freglementid as doc_id"), DB::raw("'reglement' as type_slug"));

        $avoirs = DB::table('favoirs')
            ->where('fournisseurid', $id)
            ->select('favoirdate as date', DB::raw("'Avoir' as libelle"), DB::raw("CAST(favoirnumero AS TEXT) as numero"), 'netapayer as debit', DB::raw("0 as credit"), DB::raw("favoirid as doc_id"), DB::raw("'avoir' as type_slug"));

        $bls = DB::table('fbls')
            ->where('fournisseurid', $id)
            ->where('transfere', false)
            ->select('fbldate as date', DB::raw("'Bon Entrée' as libelle"), DB::raw("CAST(fblnumero AS TEXT) as numero"), DB::raw("0 as debit"), 'netapayer as credit', DB::raw("fblid as doc_id"), DB::raw("'bon-entree' as type_slug"));

        $brs = DB::table('fbrs')
            ->where('fournisseurid', $id)
            ->where('transfere', false)
            ->select('fbrdate as date', DB::raw("'Bon Sortie' as libelle"), DB::raw("CAST(fbrnumero AS TEXT) as numero"), 'netapayer as debit', DB::raw("0 as credit"), DB::raw("fbrid as doc_id"), DB::raw("'bon-sortie' as type_slug"));

        $movements = $factures->union($reglements)->union($avoirs)->union($bls)->union($brs)
            ->orderBy('date')
            ->get();

        // Pre-load details for Bons
        $blIds = $movements->where('type_slug', 'bon-entree')->pluck('doc_id')->toArray();
        $brIds = $movements->where('type_slug', 'bon-sortie')->pluck('doc_id')->toArray();

        $blHeaders = DB::table('fbls')
            ->join('fournisseurs', 'fbls.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->whereIn('fblid', $blIds)
            ->select('fbls.*', 'fournisseurs.nom as fournisseur_nom')
            ->get()->keyBy('fblid');

        $brHeaders = DB::table('fbrs')
            ->join('fournisseurs', 'fbrs.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->whereIn('fbrid', $brIds)
            ->select('fbrs.*', 'fournisseurs.nom as fournisseur_nom')
            ->get()->keyBy('fbrid');

        $blDetails = DB::table('detfbls')
            ->join('produits', 'detfbls.produitid', '=', 'produits.produitid')
            ->whereIn('fblid', $blIds)
            ->select('detfbls.*', 'produits.produitlibelle', 'produits.produitcode')
            ->get()->groupBy('fblid');

        $brDetails = DB::table('detfbrs')
            ->join('produits', 'detfbrs.produitid', '=', 'produits.produitid')
            ->whereIn('fbrid', $brIds)
            ->select('detfbrs.*', 'produits.produitlibelle', 'produits.produitcode')
            ->get()->groupBy('fbrid');

        foreach ($movements as $m) {
            if ($m->type_slug == 'bon-entree') {
                $m->header = $blHeaders->get($m->doc_id);
                $m->details = $blDetails->get($m->doc_id);
            } elseif ($m->type_slug == 'bon-sortie') {
                $m->header = $brHeaders->get($m->doc_id);
                $m->details = $brDetails->get($m->doc_id);
            }
        }

        $facturesTotal = DB::table('ffactures')->where('fournisseurid', $id)->sum('netapayer');
        $reglementsTotal = DB::table('freglements')->where('fournisseurid', $id)->sum('montant');
        $avoirsTotal = DB::table('favoirs')->where('fournisseurid', $id)->sum('netapayer');
        $bonsEntreeTotal = DB::table('fbls')->where('fournisseurid', $id)->where('transfere', false)->sum('netapayer');
        $bonsSortieTotal = DB::table('fbrs')->where('fournisseurid', $id)->where('transfere', false)->sum('netapayer');
        $soldeDepart = $fournisseur->soldeinitial ?? 0;

        $soldeFinal = $soldeDepart + $facturesTotal + $bonsEntreeTotal - $reglementsTotal - $avoirsTotal - $bonsSortieTotal;

        $summary = [
            'solde_depart' => $soldeDepart,
            'factures' => $facturesTotal,
            'reglements' => $reglementsTotal,
            'avoirs' => $avoirsTotal,
            'bons_entree' => $bonsEntreeTotal,
            'bons_sortie' => $bonsSortieTotal,
            'solde_final' => $soldeFinal
        ];

        return \Inertia\Inertia::render('Statement', [
            'fournisseur' => $fournisseur,
            'movements' => $movements,
            'summary' => $summary
        ]);
    });

    Route::get('/achats', function () {
        $factures = DB::table('ffactures')
            ->join('fournisseurs', 'ffactures.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->select(
                'ffactureid as id',
                'ffactures.fournisseurid',
                'fournisseurs.nom as fournisseur_nom',
                'ffacturedate as date',
                'ffacturenumero as numero',
                'netapayer',
                DB::raw("'Facture' as type_libelle"),
                DB::raw("'facture' as type_slug")
            );

        $bls = DB::table('fbls')
            ->join('fournisseurs', 'fbls.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->where('transfere', false)
            ->select(
                'fblid as id',
                'fbls.fournisseurid',
                'fournisseurs.nom as fournisseur_nom',
                'fbldate as date',
                'fblnumero as numero',
                'netapayer',
                DB::raw("'Bon Entrée' as type_libelle"),
                DB::raw("'bon-entree' as type_slug")
            );

        $purchases = $factures->union($bls)
            ->orderBy('date', 'desc')
            ->limit(100)
            ->get();

        return \Inertia\Inertia::render('Purchases', [
            'purchases' => $purchases
        ]);
    })->name('achats');

    Route::get('/bon-entree/{id}', function (Request $request, $id) {
        $bon = DB::table('fbls')
            ->join('fournisseurs', 'fbls.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->where('fblid', $id)
            ->select('fbls.*', 'fournisseurs.nom as fournisseur_nom')
            ->first();

        if (!$bon)
            abort(404);

        $details = DB::table('detfbls')
            ->join('produits', 'detfbls.produitid', '=', 'produits.produitid')
            ->where('fblid', $id)
            ->select('detfbls.*', 'produits.produitlibelle', 'produits.produitcode')
            ->get();

        if ($request->ajax() || $request->has('partial')) {
            return view('partials.details_bon_content', ['type' => 'Bon Entrée', 'bon' => $bon, 'details' => $details, 'numero' => $bon->fblnumero]);
        }

        return view('details_bon', ['type' => 'Bon Entrée', 'bon' => $bon, 'details' => $details, 'numero' => $bon->fblnumero]);
    });

    Route::get('/bon-sortie/{id}', function (Request $request, $id) {
        $bon = DB::table('fbrs')
            ->join('fournisseurs', 'fbrs.fournisseurid', '=', 'fournisseurs.fournisseurid')
            ->where('fbrid', $id)
            ->select('fbrs.*', 'fournisseurs.nom as fournisseur_nom')
            ->first();

        if (!$bon)
            abort(404);

        $details = DB::table('detfbrs')
            ->join('produits', 'detfbrs.produitid', '=', 'produits.produitid')
            ->where('fbrid', $id)
            ->select('detfbrs.*', 'produits.produitlibelle', 'produits.produitcode')
            ->get();

        if ($request->ajax() || $request->has('partial')) {
            return view('partials.details_bon_content', ['type' => 'Bon Sortie', 'bon' => $bon, 'details' => $details, 'numero' => $bon->fbrnumero]);
        }

        return view('details_bon', ['type' => 'Bon Sortie', 'bon' => $bon, 'details' => $details, 'numero' => $bon->fbrnumero]);
    });

    Route::get('/stock', [\App\Http\Controllers\StockController::class, 'index'])->name('stock.index');

});
