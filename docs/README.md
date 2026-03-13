# Dokumentacija

Ovaj direktorij sadrži praktične vodiče za rad sa sustavom nakon instalacije.

## Sadržaj

1. [Instalacija i prvi koraci](01-instalacija-i-prvi-koraci.md)
2. [Administrator - priručnik](02-admin-prirucnik.md)
3. [Član - priručnik](03-clan-prirucnik.md)
4. [Roditelj - priručnik](04-roditelj-prirucnik.md)
5. [Polaznik škole - priručnik](05-polaznik-skole-prirucnik.md)

## Napomena

Screenshotovi su snimljeni na svježoj `TEMP` instalaciji nakon:
- `composer install`
- podešavanja `.env`
- `php artisan migrate --seed`
- `php artisan storage:link`
- inicijalnog bootstrap handovera administratora.

Za potrebe snimanja korišteni su demo računi:
- `ivana.admin@test.local` (admin)
- `marko.clan@test.local` (član)
- `maja.roditelj@test.local` (roditelj)
- `luka.korisnik@test.local` (polaznik škole)
