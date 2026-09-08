<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Console\Command;

class SeedMenu extends Command
{
    protected $signature = 'dune:seed-menu';

    protected $description = 'Seeds the real Dune Rooftop menu (categories + products, prices TTC with the tax backed out) and the extra ingredients it needs that are not in the starter list. Safe to run more than once — existing rows are left untouched.';

    private const TAX_RATE = 10.0;

    /**
     * [name, unit, minimum_stock] — ingredients the menu below needs that
     * dune:seed-starter-ingredients does not already cover.
     */
    private const EXTRA_INGREDIENTS = [
        ['Camembert', 'kg', 2],
        ['Mozzarella', 'kg', 3],
        ['Parmesan', 'kg', 2],
        ['Cheddar', 'kg', 3],
        ['Anchois', 'kg', 1],
        ['Avocat', 'kg', 5],
        ['Quinoa', 'kg', 2],
        ['Graines de millet', 'kg', 1],
        ['Graines de tournesol', 'kg', 1],
        ['Noix', 'kg', 2],
        ['Tomates cerise', 'kg', 3],
        ['Pruneaux', 'kg', 2],
        ['Figues séchées', 'kg', 2],
        ['Patate douce', 'kg', 5],
        ['Graines de nigelle', 'kg', 1],
        ['Olives rouges', 'kg', 2],
        ['Khlii', 'kg', 2],
        ['Pain à burger', 'unité', 30],
        ['Pâtes fraîches (tagliatelles)', 'kg', 5],
        ['Saumon fumé', 'kg', 2],
        ['Épinards', 'kg', 3],
        ['Chocolat noir', 'kg', 2],
        ['Amlou', 'kg', 1],
        ['Betterave', 'kg', 3],
        ['Fleurs d\'hibiscus', 'kg', 1],
        ['Dattes', 'kg', 3],
        ['Pamplemousse', 'kg', 3],
        ['Romarin', 'botte', 5],
        ['Ananas', 'kg', 5],
        ['Mangue', 'kg', 5],
        ['Jus de grenade', 'L', 3],
        ['Pommes', 'kg', 5],
        ['San Miguel 0.0% (bouteille)', 'bouteille', 24],
        ['Lipton Ice Tea (bouteille)', 'bouteille', 24],
        ['Chocolat en poudre', 'kg', 2],
        ['Verveine (feuilles)', 'kg', 1],
        ['Thé Lipton (sachets)', 'boîte', 5],
        ['Semoule d\'orge', 'kg', 3],
    ];

    /**
     * Category name => ['type' => food|drink, 'products' => [[name, description, sku, price_ttc], ...]]
     */
    private function menu(): array
    {
        return [
            'Entrées' => [
                'type' => 'food',
                'products' => [
                    ['Pastilla au coquelet', 'Salé-sucré de poulet effiloché, œuf brouillé au bouillon de cuisson et amande.', 'ENT-01', 70],
                    ['Nems de crevettes', 'Revisité à la chermoula marocaine.', 'ENT-02', 65],
                    ['Salade dune', 'Mesclun, roquette, jeune pousse, graine de millet, quinoa, pépins de tournesol, avocat, tomates, concombres, poulet pané, camembert.', 'ENT-03', 85],
                    ['Salade de mozzarella', 'Mozzarella, huile d\'olive au piment doux, miel, brisure de noix sur lit de roquette marinée, tomates cerise sautées à l\'ail, courgettes grillées.', 'ENT-04', 55],
                    ['Tartare d\'avocat', 'Anchois, fraîcheur de fruit, salade de légumes croquants, vinaigrette d\'agrumes.', 'ENT-05', 60],
                    ['Soupe harira marrakchia', null, 'ENT-06', 40],
                ],
            ],
            'Plats' => [
                'type' => 'food',
                'products' => [
                    ['Filet de poisson', 'Mariné à la marocaine, frit, accompagné de sauce tartare au paprika & ratatouille (selon arrivage).', 'PLA-01', 75],
                    ['Côtelettes d\'agneau', 'Romarin de l\'Atlas, purée de pomme de terre.', 'PLA-02', 95],
                    ['Tajine de poulet revisité', 'Poulet effiloché, citrons confits, olives rouges, purée de pomme de terre aux graines de nigelle.', 'PLA-03', 75],
                    ['Tajine de bœuf revisité', 'Bœuf effiloché, pruneaux, figues, noix, purée de patate douce au curcuma.', 'PLA-04', 80],
                    ['Tangia marrakchia', 'Bœuf effiloché, safran pur, citrons, cumin, semoule d\'orge.', 'PLA-05', 90],
                    ['Couscous 7 légumes (Bœuf/Poulet)', 'Couscous aux sept légumes.', 'PLA-06', 80],
                    ['Couscous 7 légumes (Végétarien)', 'Couscous aux sept légumes.', 'PLA-07', 65],
                    ['Krunchy burger', 'Poulet à la marocaine effiloché pané aux céréales, salade, tomate, cheddar & frites.', 'PLA-08', 75],
                    ['Fassi burger', 'Bœuf effiloché au cumin, œuf au plat, khlii & olives noires, salade, tomate, cheddar & frites.', 'PLA-09', 80],
                    ['Tagliatelles saumon fumé', 'Pâtes fraîches maison, saumon fumé, crème d\'épinard & parmesan.', 'PLA-10', 85],
                    ['Tagliatelles végétariennes', 'Pâtes fraîches maison, tomates & légumes sautés.', 'PLA-11', 70],
                ],
            ],
            'Desserts' => [
                'type' => 'food',
                'products' => [
                    ['Tiramisu', 'Au mélange d\'épices ras el hanout.', 'DES-01', 55],
                    ['Poire pochée', 'Sirop de safran pur & mousse d\'amlou.', 'DES-02', 55],
                    ['Compote d\'avocat acidulé', 'Ganache de chocolat noir et notes de fleur d\'oranger.', 'DES-03', 45],
                    ['Palets bretons', 'Citron & menthe.', 'DES-04', 45],
                    ['Pâtisseries marocaines', null, 'DES-05', 40],
                ],
            ],
            'Menu enfant' => [
                'type' => 'food',
                'products' => [
                    ['Petit wrap bœuf', 'Viande hachée, tomate, salade, frites & jus.', 'ENF-01', 50],
                    ['Petit wrap poulet', 'Poulet, cheddar, tomate, salade, frites & jus.', 'ENF-02', 50],
                    ['Pâtes au poulet', 'Poulet, crème, fromage & jus.', 'ENF-03', 50],
                ],
            ],
            'Mocktails' => [
                'type' => 'drink',
                'products' => [
                    ['Virgin mojito', 'Citron, menthe, eau gazeuse.', 'MOC-01', 35],
                    ['Sahara Storm', 'Orange, mangue, citron.', 'MOC-02', 40],
                    ['Agua Fresca', 'Hibiscus, citron, gingembre.', 'MOC-03', 40],
                    ['Pomme fizz', 'Jus de pomme, gingembre, menthe, citron, cannelle.', 'MOC-04', 40],
                    ['Dune sunset', 'Pamplemousse, romarin, eau gazeuse.', 'MOC-05', 45],
                    ['Exotique', 'Ananas, mangue, citron, orange, jus de grenade.', 'MOC-06', 45],
                ],
            ],
            'Jus' => [
                'type' => 'drink',
                'products' => [
                    ['Jus de betterave', 'Orange.', 'JUS-01', 35],
                    ['Jus de citron', 'Menthe & gingembre.', 'JUS-02', 30],
                    ['Jus palmeraie', 'Orange, avocat & dattes.', 'JUS-03', 45],
                    ['Jus de carotte', 'Gingembre.', 'JUS-04', 35],
                    ['Jus d\'avocat', 'Orange.', 'JUS-05', 40],
                    ['Jus d\'orange', null, 'JUS-06', 25],
                ],
            ],
            'Boissons froides' => [
                'type' => 'drink',
                'products' => [
                    ['San Miguel 0.0%', null, 'BFR-01', 35],
                    ['Sodas 33cl', null, 'BFR-02', 20],
                    ['Lipton Ice Tea 50cl', 'Citron/Pêche.', 'BFR-03', 30],
                    ['Eau gazeuse 0.5L', null, 'BFR-04', 15],
                    ['Eau gazeuse 1L', null, 'BFR-05', 25],
                    ['Eau plate 0.5L', null, 'BFR-06', 10],
                    ['Eau plate 1.5L', null, 'BFR-07', 25],
                ],
            ],
            'Boissons chaudes' => [
                'type' => 'drink',
                'products' => [
                    ['Café expresso', null, 'BCH-01', 20],
                    ['Café Américain', null, 'BCH-02', 20],
                    ['Café noisette', 'Café & lait.', 'BCH-03', 25],
                    ['Thé marocain', null, 'BCH-04', 20],
                    ['Chocolat chaud', null, 'BCH-05', 25],
                    ['Thé Lipton', null, 'BCH-06', 25],
                    ['Verveine', null, 'BCH-07', 20],
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $ingredientsCreated = 0;
        foreach (self::EXTRA_INGREDIENTS as [$name, $unit, $minimumStock]) {
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
                $ingredientsCreated++;
            }
        }
        $this->info("{$ingredientsCreated} ingrédient(s) supplémentaire(s) créé(s) pour le menu.");

        $categoriesCreated = 0;
        $productsCreated = 0;

        foreach ($this->menu() as $categoryName => $section) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['type' => $section['type'], 'is_active' => true]
            );
            if ($category->wasRecentlyCreated) {
                $categoriesCreated++;
            }

            foreach ($section['products'] as [$name, $description, $sku, $priceTtc]) {
                $product = Product::firstOrCreate(
                    ['name' => $name],
                    [
                        'category_id' => $category->id,
                        'sku' => $sku,
                        'description' => $description,
                        'price' => round($priceTtc / (1 + self::TAX_RATE / 100), 2),
                        'tax_rate' => self::TAX_RATE,
                        'is_active' => true,
                    ]
                );
                if ($product->wasRecentlyCreated) {
                    $productsCreated++;
                }
            }
        }

        $this->info("{$categoriesCreated} catégorie(s) créée(s), {$productsCreated} produit(s) créé(s).");
        $this->comment('Prix menu = TTC (TVA 10% déjà déduite pour le prix HT stocké). Les recettes (ingrédients par plat) restent à configurer manuellement depuis l\'écran Recettes.');

        return self::SUCCESS;
    }
}
