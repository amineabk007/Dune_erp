<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use Illuminate\Console\Command;

class SeedStarterIngredients extends Command
{
    protected $signature = 'dune:seed-starter-ingredients';

    protected $description = 'Creates a starter list of common restaurant ingredients (name + unit + minimum stock), with cost left at 0 to be filled in from real supplier prices. Safe to run more than once — existing ingredients are left untouched.';

    /**
     * [name, unit, minimum_stock]
     */
    private const INGREDIENTS = [
        // Viandes & volailles
        ['Poulet entier', 'kg', 5],
        ['Filet de poulet', 'kg', 5],
        ['Escalope de dinde', 'kg', 3],
        ['Viande hachée bœuf', 'kg', 5],
        ['Entrecôte bœuf', 'kg', 3],
        ['Filet de bœuf', 'kg', 3],
        ['Côtelettes d\'agneau', 'kg', 3],
        ['Épaule d\'agneau', 'kg', 3],
        ['Merguez', 'kg', 3],
        ['Foie de veau', 'kg', 2],

        // Poissons & fruits de mer
        ['Crevettes', 'kg', 2],
        ['Calamars', 'kg', 2],
        ['Saumon', 'kg', 2],
        ['Espadon', 'kg', 2],
        ['Sardines', 'kg', 2],

        // Légumes & herbes
        ['Tomates', 'kg', 10],
        ['Oignons', 'kg', 10],
        ['Ail', 'kg', 2],
        ['Poivrons', 'kg', 5],
        ['Courgettes', 'kg', 5],
        ['Carottes', 'kg', 5],
        ['Pommes de terre', 'kg', 10],
        ['Aubergines', 'kg', 5],
        ['Concombre', 'kg', 5],
        ['Laitue', 'kg', 3],
        ['Roquette', 'kg', 2],
        ['Coriandre', 'botte', 10],
        ['Persil', 'botte', 10],
        ['Menthe', 'botte', 10],
        ['Citron', 'kg', 5],
        ['Olives vertes', 'kg', 3],
        ['Olives noires', 'kg', 3],

        // Épicerie & épices
        ['Huile d\'olive', 'L', 10],
        ['Huile de tournesol', 'L', 10],
        ['Sel', 'kg', 5],
        ['Poivre noir', 'kg', 1],
        ['Cumin', 'kg', 1],
        ['Paprika', 'kg', 1],
        ['Gingembre en poudre', 'kg', 1],
        ['Curcuma', 'kg', 1],
        ['Ras el hanout', 'kg', 1],
        ['Cannelle', 'kg', 1],
        ['Safran', 'g', 50],
        ['Farine', 'kg', 15],
        ['Semoule', 'kg', 10],
        ['Riz', 'kg', 10],
        ['Sucre', 'kg', 10],
        ['Miel', 'kg', 3],
        ['Amandes', 'kg', 3],

        // Produits laitiers & œufs
        ['Lait', 'L', 10],
        ['Crème fraîche', 'L', 5],
        ['Fromage râpé', 'kg', 3],
        ['Fromage frais', 'kg', 3],
        ['Yaourt', 'unité', 20],
        ['Œufs', 'unité', 60],
        ['Beurre', 'kg', 5],

        // Boulangerie / pâtisserie
        ['Pain', 'unité', 30],
        ['Feuilles de brick', 'unité', 50],
        ['Levure', 'kg', 1],
        ['Amandes effilées', 'kg', 2],
        ['Pâte d\'amande', 'kg', 2],
        ['Fleur d\'oranger', 'L', 2],

        // Boissons & bar
        ['Eau plate', 'bouteille', 50],
        ['Eau gazeuse', 'bouteille', 30],
        ['Sodas', 'canette', 50],
        ['Oranges (jus)', 'kg', 10],
        ['Thé vert', 'kg', 3],
        ['Sucre à thé', 'kg', 5],
        ['Café en grains', 'kg', 5],
        ['Sirops cocktail', 'bouteille', 10],
        ['Vin', 'bouteille', 10],
        ['Bière', 'bouteille', 30],
        ['Spiritueux', 'bouteille', 10],

        // Consommables
        ['Serviettes en papier', 'paquet', 20],
        ['Papier essuie-tout', 'rouleau', 20],
        ['Sachets à emporter', 'unité', 100],
        ['Pailles', 'paquet', 20],
    ];

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;

        foreach (self::INGREDIENTS as [$name, $unit, $minimumStock]) {
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $name],
                [
                    'unit' => $unit,
                    'current_stock' => 0,
                    'minimum_stock' => $minimumStock,
                    'unit_cost' => 0,
                    'is_active' => true,
                ]
            );

            if ($ingredient->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->info("{$created} ingrédient(s) créé(s), {$skipped} déjà existant(s) laissé(s) tel quel.");
        $this->comment('Le coût unitaire est à 0 DH pour tous — à compléter avec les vrais prix fournisseurs depuis l\'écran Ingrédients.');

        return self::SUCCESS;
    }
}
