<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $month = $request->get('month', now()->format('Y-m'));
        $journalId = $request->get('journal_id');

        // État de journée : sessions caisse du jour
        $journeeSessions = DB::table('journalcaisses')
            ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
            ->leftJoin('sites', 'journalcaisses.siteid', '=', 'sites.siteid')
            ->whereDate('journalcaisses.dateouverture', $date)
            ->select(
                'journalcaisses.journalcaisseid',
                'journalcaisses.dateouverture',
                'journalcaisses.datecloture',
                'journalcaisses.fondcaisse',
                'journalcaisses.montantcloture',
                'journalcaisses.montanttheorique',
                'journalcaisses.montantdepense',
                'journalcaisses.isclosed',
                'caisses.libelle as caisse_libelle',
                'sites.libelle as site_libelle'
            )
            ->orderBy('journalcaisses.dateouverture')
            ->get();

        $journeeTotaux = (object) [
            'nombre_sessions' => $journeeSessions->count(),
            'total_fondcaisse' => $journeeSessions->sum('fondcaisse'),
            'total_cloture' => $journeeSessions->sum('montantcloture'),
            'total_theorique' => $journeeSessions->sum('montanttheorique'),
            'total_depense' => $journeeSessions->sum('montantdepense'),
        ];

        // Journal caisse : détail d'une session ou lignes du jour
        $journalLignes = collect();
        $journalEntete = null;
        if ($journalId) {
            $journalEntete = DB::table('journalcaisses')
                ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
                ->leftJoin('sites', 'journalcaisses.siteid', '=', 'sites.siteid')
                ->where('journalcaisses.journalcaisseid', $journalId)
                ->select(
                    'journalcaisses.*',
                    'caisses.libelle as caisse_libelle',
                    'sites.libelle as site_libelle'
                )
                ->first();
            $journalLignes = DB::table('journalcaissedets')
                ->leftJoin('sectionclotures', 'journalcaissedets.sectionclotureid', '=', 'sectionclotures.sectionclotureid')
                ->where('journalcaissedets.journalcaisseid', $journalId)
                ->select(
                    'journalcaissedets.*',
                    'sectionclotures.libelle as section_libelle'
                )
                ->orderBy('journalcaissedets.priorite')
                ->orderBy('journalcaissedets.journalcaissedetid')
                ->get();
        } else {
            $idsJour = $journeeSessions->pluck('journalcaisseid')->toArray();
            if (!empty($idsJour)) {
                $journalLignes = DB::table('journalcaissedets')
                    ->leftJoin('sectionclotures', 'journalcaissedets.sectionclotureid', '=', 'sectionclotures.sectionclotureid')
                    ->leftJoin('journalcaisses', 'journalcaissedets.journalcaisseid', '=', 'journalcaisses.journalcaisseid')
                    ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
                    ->whereIn('journalcaissedets.journalcaisseid', $idsJour)
                    ->select(
                        'journalcaissedets.*',
                        'sectionclotures.libelle as section_libelle',
                        'caisses.libelle as caisse_libelle',
                        'journalcaisses.dateouverture'
                    )
                    ->orderBy('journalcaisses.dateouverture')
                    ->orderBy('journalcaissedets.priorite')
                    ->get();
            }
        }

        // Rapport mensuel : agrégat par mois
        $debutMois = $month . '-01';
        $finMois = date('Y-m-t', strtotime($debutMois));
        $rapportMensuel = DB::table('journalcaisses')
            ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
            ->whereDate('journalcaisses.dateouverture', '>=', $debutMois)
            ->whereDate('journalcaisses.dateouverture', '<=', $finMois)
            ->select(
                DB::raw('COUNT(*) as nb_sessions'),
                DB::raw('COALESCE(SUM(fondcaisse), 0) as total_fond'),
                DB::raw('COALESCE(SUM(montantcloture), 0) as total_cloture'),
                DB::raw('COALESCE(SUM(montanttheorique), 0) as total_theorique'),
                DB::raw('COALESCE(SUM(montantdepense), 0) as total_depense')
            )
            ->first();

        $rapportParCaisse = DB::table('journalcaisses')
            ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
            ->whereDate('journalcaisses.dateouverture', '>=', $debutMois)
            ->whereDate('journalcaisses.dateouverture', '<=', $finMois)
            ->select(
                'caisses.libelle as caisse_libelle',
                DB::raw('COUNT(*) as nb_sessions'),
                DB::raw('COALESCE(SUM(montantcloture), 0) as total_cloture')
            )
            ->groupBy('journalcaisses.caisseid', 'caisses.libelle')
            ->get();

        return Inertia::render('Statistics', [
            'date' => $date,
            'month' => $month,
            'journal_id' => $journalId,
            'journeeSessions' => $journeeSessions,
            'journeeTotaux' => $journeeTotaux,
            'journalLignes' => $journalLignes,
            'journalEntete' => $journalEntete,
            'rapportMensuel' => $rapportMensuel,
            'rapportParCaisse' => $rapportParCaisse,
            'caisses' => DB::table('caisses')->select('caisseid', 'libelle')->orderBy('libelle')->get(),
        ]);
    }
}
