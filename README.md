# Streličarski klub - web sustav

Laravel aplikacija za vođenje rada kluba:

1. članovi i dokumenti
2. škola streličarstva
3. treninzi
4. turniri i rezultati
5. članci i obavijesti
6. praćenje plaćanja

## Dokumentacija

Aktualna dokumentacija je u `docs`:

1. [Pregled dokumentacije](docs/README.md)
2. [Korisnik član - detaljne upute](docs/01-clan-prirucnik.md)
3. [Polaznik škole streličarstva - detaljne upute](docs/02-polaznik-skole-prirucnik.md)
4. [Roditelj - detaljni priručnik](docs/03-roditelj-prirucnik.md)
5. [Administrator - detaljni priručnik](docs/04-admin-prirucnik.md)
6. [Instalacija i prvi koraci](docs/05-instalacija-i-prvi-koraci.md)

## Preduvjeti

1. PHP 8.2+
2. Composer 2+
3. MySQL 8+
4. Node.js 18+ i npm

## Brzi start (lokalno)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

Za razvoj:

```bash
npm run dev
php artisan serve
```

## Što seed postavlja

Seed postavlja osnovne šifrarnike i početne podatke:

1. stilove luka
2. kategorije
3. tipove turnira i polja rezultata
4. početne teme
5. bootstrap admin račun

Bootstrap admin:

1. e-mail: `administrator@archery.local`
2. lozinka: `poklonOdSKDubrava`

## Obavezni prvi korak nakon instalacije

Bootstrap admin je samo privremeni račun za inicijalni handover.

1. registriraj stvarnog korisnika kluba,
2. prijavi se bootstrap admin računom,
3. u `Admin -> Korisnici` postavi tom korisniku rolu `1 - Admin`,
4. sustav automatski odjavljuje i uklanja bootstrap admin račun.

## Uvoz nadolazećih turnira (archery.hr)

```bash
php artisan turniri:import-archery
```

Primjeri:

```bash
php artisan turniri:import-archery --year=2026 --dry-run
php artisan turniri:import-archery --year=2026
php artisan turniri:import-archery --year=2026 --skip-existing
php artisan turniri:import-archery --year=2026 --include-past
```
