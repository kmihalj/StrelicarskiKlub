<?php

namespace App\Support;

use App\Models\Kategorije;
use Illuminate\Validation\ValidationException;

class TournamentFeeCategories
{
    /** Dobne skupine kotizacije zajedničke muškim i ženskim kategorijama. */
    public const LABELS = [
        'U11' => 'U11', 'U13' => 'U13', 'U15' => 'U15',
        'U18' => 'U18', 'U21' => 'U21',
        'seniori' => 'Seniori', 'veterani' => 'Veterani',
    ];

    /** Razvrstava natjecateljsku kategoriju bez promjene njezina spola. */
    public static function groupFor(Kategorije|string|null $category): ?string
    {
        $name = mb_strtoupper(trim($category instanceof Kategorije ? $category->naziv : (string) $category), 'UTF-8');
        if (preg_match('/\bU(11|13|15|18|21)(?:[MWŽZ]|\b)/u', $name, $match)) {
            return 'U'.$match[1];
        }
        if (str_contains($name, 'VETERAN') || str_contains($name, '50+')) {
            return 'veterani';
        }
        if (str_contains($name, 'SENIOR') || in_array($name, ['M', 'W', 'Ž', 'Z'], true)) {
            return 'seniori';
        }

        return null;
    }

    /** Validira potpuno pokriće kategorija, bez ponavljanja i nevaljanih iznosa. */
    public static function validateRates(array $rates): ?array
    {
        $seen = [];
        $result = [];
        foreach (array_values($rates) as $i => $rate) {
            $groups = $rate['kategorije'] ?? [];
            if ($groups === [] || count(array_unique($groups)) !== count($groups)) {
                throw ValidationException::withMessages(["kotizacija_razredi.$i.kategorije" => 'Odaberite kategorije bez ponavljanja.']);
            }
            foreach ($groups as $group) {
                if (! array_key_exists($group, self::LABELS) || in_array($group, $seen, true)) {
                    throw ValidationException::withMessages(["kotizacija_razredi.$i.kategorije" => 'Svaka kategorija može pripadati samo jednoj kotizaciji.']);
                }
                $seen[] = $group;
            }
            $amount = trim((string) ($rate['iznos'] ?? ''));
            if ($amount === '' && count($rates) === 1 && count($groups) === count(self::LABELS)) {
                return null;
            }
            $amount = str_replace(',', '.', $amount);
            if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(["kotizacija_razredi.$i.iznos" => 'Unesite iznos kotizacije veći od 0, s najviše dvije decimale.']);
            }
            $result[] = ['kategorije' => array_values($groups), 'iznos' => number_format((float) $amount, 2, '.', '')];
        }
        if (count($seen) !== count(self::LABELS)) {
            throw ValidationException::withMessages(['kotizacija_razredi' => 'Odredite kotizaciju za sve kategorije.']);
        }

        return $result;
    }
}
