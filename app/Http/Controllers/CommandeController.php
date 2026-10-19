<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    /**
     * Ancienne page de saisie de commande (inchangée).
     */
    public function new(int $commandeId)
    {
        $id = $commandeId;
        return view('pages.commandes', compact('id'));
    }

    /**
     * Liste des commandes : une ligne par précommande, avec recherche et filtre de statut.
     */
    public function admin_commande(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $recherche = trim((string) $request->query('q', ''));
        $statut    = $request->query('statut');

        if (! in_array($statut, ['confirme', 'en_cours'], true)) {
            $statut = null;
        }

        $filtrees = Commande::query()
            ->where('company_id', $companyId)
            ->when($recherche !== '', function ($query) use ($recherche) {
                $query->whereHas('precommande', function ($p) use ($recherche) {
                    $p->where('code', 'like', '%' . $recherche . '%');
                });
            })
            ->when($statut !== null, function ($query) use ($statut) {
                $query->whereHas('precommande', function ($p) use ($statut) {
                    $p->where('status', $statut === 'confirme');
                });
            });

        // Une entrée par précommande, la plus récente en premier
        $groupes = (clone $filtrees)
            ->selectRaw('precommande_id, MAX(created_at) as derniere_date')
            ->groupBy('precommande_id')
            ->orderByDesc('derniere_date')
            ->paginate(15)
            ->withQueryString();

        // Toutes les lignes des précommandes de la page, chargées en une seule requête
        $lignes = Commande::with(['precommande', 'produit', 'reduction'])
            ->where('company_id', $companyId)
            ->whereIn('precommande_id', $groupes->pluck('precommande_id'))
            ->get()
            ->groupBy('precommande_id');

        $groupes->through(function ($groupe) use ($lignes) {
            return $this->resumer($lignes->get($groupe->precommande_id, collect()));
        });

        return view('pages.adminCommandes', [
            'commandes' => $groupes,
            'recherche' => $recherche,
            'statut'    => $statut,
        ]);
    }

    /**
     * Calcule les totaux d'une précommande à partir de ses lignes.
     */
    private function resumer(Collection $lignes): array
    {
        $premiere = $lignes->first();

        $sousTotal = $lignes->sum(function (Commande $ligne) {
            return (float) $ligne->quantity_commande * (float) ($ligne->produit?->price ?? 0);
        });

        $ligneAvecRemise = $lignes->first(fn (Commande $ligne) => $ligne->reduction !== null);
        $pourcentage     = (float) ($ligneAvecRemise?->reduction?->pourcentage ?? 0);
        $remise          = $sousTotal * $pourcentage / 100;

        return [
            'code'        => $premiere?->precommande?->code ?? '—',
            'confirme'    => (bool) ($premiere?->precommande?->status),
            'date'        => $lignes->max('created_at'),
            'quantite'    => (int) $lignes->sum('quantity_commande'),
            'sous_total'  => $sousTotal,
            'pourcentage' => $pourcentage,
            'remise'      => $remise,
            'total'       => $sousTotal - $remise,
            'lignes'      => $lignes,
        ];
    }
}