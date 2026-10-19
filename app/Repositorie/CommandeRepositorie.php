<?php

namespace App\Repositorie;

use App\Models\Commande;
use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Reduction;
use App\Models\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommandeRepositorie
{
    /* ==========================================================
     |  Outils internes (toujours filtrés par entreprise)
     ========================================================== */

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    /** Valide une quantité : entier strictement positif. */
    private function assertQuantite(mixed $quantite): int
    {
        if (filter_var($quantite, FILTER_VALIDATE_INT) === false || (int) $quantite < 1) {
            throw new \InvalidArgumentException('La quantité doit être un entier supérieur à 0.');
        }

        return (int) $quantite;
    }

    private function produitPourEntreprise(int $produitId): Produit
    {
        return Produit::findOrFail($produitId);
    }

    private function precommandePourEntreprise(int $precommandeId): Precommande
    {
        return Precommande::findOrFail($precommandeId);
    }

    /** Ligne de commande de l'entreprise. Verrou optionnel pour les écritures. */
    private function ligne(int $precommandeId, int $produitId, bool $verrou = false): ?Commande
    {
        $requete = Commande::where('company_id', $this->companyId())
            ->where('precommande_id', $precommandeId)
            ->where('produit_id', $produitId);

        return ($verrou ? $requete->lockForUpdate() : $requete)->first();
    }

    /**
     * Retire du stock de façon atomique.
     * La condition et la mise à jour sont dans une seule requête SQL,
     * donc deux ventes simultanées ne peuvent pas dépasser le stock.
     */
    private function retirerStock(int $produitId, int $quantite): bool
    {
        return Produit::where('id', $produitId)
            ->where('quantity', '>=', $quantite)
            ->decrement('quantity', $quantite) > 0;
    }

    private function remettreStock(int $produitId, int $quantite): void
    {
        Produit::where('id', $produitId)
            ->increment('quantity', $quantite);
    }

    /* ==========================================================
     |  Produits
     ========================================================== */

    public function produit_by_id(int $produitId): ?Produit
    {
        return Produit::where('id', $produitId)
            ->first();
    }

    public function product_qty(int $produitId): int
    {
        return (int) $this->produitPourEntreprise($produitId)->quantity;
    }

    /** Décrémente le stock. Retourne false si le stock est insuffisant. */
    public function substract_quantity(int $produitId, $quantity): bool
    {
        return $this->retirerStock($produitId, $this->assertQuantite($quantity));
    }

    /** Remet du stock (annulation ou réduction de commande). */
    public function restore_product(int $produitId, $quantity): void
    {
        $this->remettreStock($produitId, $this->assertQuantite($quantity));
    }

    /* ==========================================================
     |  Précommandes
     ========================================================== */

    public function all_precommandes()
    {
        return Precommande::where('company_id', $this->companyId())
            ->where('status', false)
            ->latest()
            ->get();
    }

    public function last_commande(int $precommandeId)
    {
        return Precommande::where('status', false)
            ->whereKey($precommandeId)
            ->with('reductions')
            ->first();
    }

    /** Confirme la commande et libère la table, dans une seule transaction. */
    public function confirm(int $precommandeId): void
    {
        DB::transaction(function () use ($precommandeId) {
            $precommande = Precommande::lockForUpdate()
                ->findOrFail($precommandeId);

            $precommande->update(['status' => true]);

            // Une vente à emporter n'a pas de table
            if ($precommande->table_id) {
                $table = Table::find($precommande->table_id);

                if ($table) {
                    $table->update(['status' => false]);
                    session()->forget($table->name);
                }
            }
        });
    }

    /* ==========================================================
     |  Lignes de commande
     ========================================================== */

    public function all_commandes(int $precommandeId)
    {
        return Commande::where('precommande_id', $precommandeId)
            ->whereHas('precommande', fn ($q) => $q->where('status', false))
            ->with('produit')
            ->get();
    }

    public function commande_by_id(int $precommandeId, int $produitId): ?Commande
    {
        return $this->ligne($precommandeId, $produitId);
    }

    public function commande_by_product(int $precommandeId)
    {
        return Commande::where('commandes.precommande_id', $precommandeId)
            ->join('produits', 'commandes.produit_id', '=', 'produits.id')
            ->get(['commandes.*', 'produits.name', 'produits.id as pId']);
    }

    /**
     * Crée une ligne de commande et décrémente le stock, dans une transaction.
     * Retourne false si le stock est insuffisant (rien n'est enregistré).
     */
    public function store_command(int $precommandeId, int $produitId, $quantity): bool
    {
        $quantite = $this->assertQuantite($quantity);
        $this->precommandePourEntreprise($precommandeId);

        return DB::transaction(function () use ($precommandeId, $produitId, $quantite) {
            if (! $this->retirerStock($produitId, $quantite)) {
                return false;
            }

            Commande::create([
                'precommande_id'    => $precommandeId,
                'produit_id'        => $produitId,
                'quantity_commande' => $quantite
            ]);

            return true;
        });
    }

    /** Ajoute une quantité à une ligne existante. Retourne false si le stock est insuffisant. */
    public function update_quantity(int $precommandeId, int $produitId, $quantity): bool
    {
        $quantite = $this->assertQuantite($quantity);

        return DB::transaction(function () use ($precommandeId, $produitId, $quantite) {
            $ligne = $this->ligne($precommandeId, $produitId, true);

            if (! $ligne || ! $this->retirerStock($produitId, $quantite)) {
                return false;
            }

            $ligne->increment('quantity_commande', $quantite);

            return true;
        });
    }

    /** Retire une unité. La ligne est supprimée si la quantité atteint 0. */
    public function reduire_quantity(int $precommandeId, int $produitId): bool
    {
        return DB::transaction(function () use ($precommandeId, $produitId) {
            $ligne = $this->ligne($precommandeId, $produitId, true);

            if (! $ligne) {
                return false;
            }

            $this->remettreStock($produitId, 1);

            if ((int) $ligne->quantity_commande <= 1) {
                $ligne->delete();
            } else {
                $ligne->decrement('quantity_commande');
            }

            return true;
        });
    }

    /** Annule une ligne et restitue son stock. La quantité vient de la base. */
    public function delete_commande(int $precommandeId, int $produitId): bool
    {
        return DB::transaction(function () use ($precommandeId, $produitId) {
            $ligne = $this->ligne($precommandeId, $produitId, true);

            if (! $ligne) {
                return false;
            }

            $this->remettreStock($produitId, (int) $ligne->quantity_commande);
            $ligne->delete();

            return true;
        });
    }

    /* ==========================================================
     |  Factures et rapports
     ========================================================== */

    /**
     * Lignes de facture regroupées par produit.
     * La réduction est agrégée dans une sous-requête pour ne pas multiplier les quantités.
     */
    public function facture(int $precommandeId)
    {
        $reductions = Reduction::query()
            ->selectRaw('precommande_id, MAX(pourcentage) AS pourcentage')
            ->groupBy('precommande_id');

        return DB::table('commandes as c')
            ->join('precommandes as p', 'p.id', '=', 'c.precommande_id')
            ->join('produits as pr', 'pr.id', '=', 'c.produit_id')
            ->leftJoinSub($reductions, 'r', 'r.precommande_id', '=', 'p.id')
            ->where('p.id', $precommandeId)
            ->groupBy('pr.id', 'pr.name', 'pr.price', 'r.pourcentage', 'p.id', 'p.created_at', 'p.invoiced', 'p.code')
            ->get([
                DB::raw('SUM(c.quantity_commande) AS qty'),
                'r.pourcentage',
                'pr.name',
                'pr.price',
                'pr.id AS produit_id',
                'p.created_at',
                'p.id AS pId',
                'p.invoiced',
                'p.code',
            ]);
    }

    /** Quantités vendues aujourd'hui, par produit, pour l'entreprise. */
    public function todays()
    {
        return DB::table('commandes as c')
            ->join('produits as pr', 'pr.id', '=', 'c.produit_id')
            ->whereDate('c.created_at', today())
            ->groupBy('c.produit_id', 'pr.name', 'pr.price')
            ->get([
                'c.produit_id',
                'pr.name',
                'pr.price',
                DB::raw('SUM(c.quantity_commande) AS quantity_commande'),
            ]);
    }
}