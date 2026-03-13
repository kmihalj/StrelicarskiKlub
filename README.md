# SKDubrava (Open Club Edition)

Laravel aplikacija za vođenje streličarskog kluba:
- članovi i dokumenti
- škola streličarstva
- treninzi
- turniri i rezultati
- članci/obavijesti
- teme (svijetla/tamna varijanta)
- praćenje plaćanja

## 1. Preduvjeti

- PHP 8.2+
- Composer 2+
- MySQL 8+
- Node.js 18+ i npm
- web server (Apache/Nginx) ili `php artisan serve`

## 2. Instalacija projekta

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Uredi `.env` (najvažnije DB postavke):

```dotenv
APP_NAME="Archery Club"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Pokreni migracije i početni seed:

```bash
php artisan migrate --seed
```

Kreiraj storage link:

```bash
php artisan storage:link
```

Frontend assets:

```bash
npm install
npm run build
```

Za lokalni razvoj može i:

```bash
npm run dev
php artisan serve
```

## 3. Što seed automatski postavlja

- tablicu stilova (`stilovis`) bez "Standardni luk" (`id = 7`)
- kategorije (`kategorijes`)
- tipove turnira (`tipovi_turniras`) i pripadajuća polja (`polja_za_tipove_turniras`)
- predefinirane teme (bez SKDubrava), default aktivna tema: **Zelena (light)**
- globalni logo/favicon: streličarska meta (svijetla i tamna varijanta)
- početnog bootstrap admin korisnika:
  - email: `administrator@archery.local`
  - lozinka: `poklonOdSKDubrava`

## 4. Obavezni prvi korak nakon instalacije (handover admina)

Bootstrap korisnik `Administrator` je samo za inicijalno postavljanje.

Nakon instalacije:

1. Ulogiraj se kao `Administrator`.
2. Kreiraj/uredi stvarnog korisnika kluba (registrirani član).
3. U Admin > Korisnici postavi tom korisniku rolu **Administrator**.
4. Aplikacija automatski:
   - odjavljuje bootstrap korisnika,
   - briše bootstrap korisnika iz baze,
   - novi korisnik ostaje administrator.

Time je inicijalni setup završen.

## 5. Produkcija (preporuka)

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --seed --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Uloge u sustavu (sažetak)

- **Administrator**: puni pristup administraciji (članovi, korisnici, teme, setup, plaćanja, sadržaj).
- **Član**: vlastiti profil, treninzi, relevantni prikazi i plaćanja.
- **Roditelj**: vlastiti račun + pregled povezane djece.
- **Polaznik škole**: profil škole, dolasci i školarina prema ovlastima.

## 7. Napomena

Za detaljan korisnički priručnik (admin/član/roditelj/polaznik) preporuka je izraditi zaseban dokument s ekranima i primjerima procesa.
