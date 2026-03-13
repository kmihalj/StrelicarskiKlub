# Instalacija i prvi koraci

Ovaj vodič pokriva kompletan put od prazne instalacije do prvog funkcionalnog administratora.

## 1. Instalacija

Pokreni u rootu projekta:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

## 2. Podesi `.env`

Minimalno provjeri:

- `APP_ENV=local`
- `APP_DEBUG=false`
- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

## 3. Prvi ulaz na aplikaciju

Nakon seed-a aplikacija je inicijalno prazna (osnovni menu i kontakt blok).

![Početna nakon instalacije](screenshots/setup/01-home-after-install.png)

## 4. Registriraj stvarnog korisnika kluba

Prvo se registrira realni korisnik (budući admin).

![Registracija](screenshots/setup/02-register-form.png)

Nakon registracije korisnik je kreiran i prijavljen kao obični korisnik.

![Nakon registracije](screenshots/setup/03-after-registration.png)

## 5. Prijava privremenog bootstrap admina

Bootstrap admin iz seeda:

- email: `administrator@archery.local`
- lozinka: `poklonOdSKDubrava`

Nakon prijave otvara se `Admin > Korisnici`.

![Bootstrap admin - korisnici](screenshots/setup/04-bootstrap-admin-users.png)

## 6. Predaja admin ovlasti

Otvori registriranog korisnika i postavi rolu `1 - Admin`, pa spremi.

![Promocija korisnika u admina](screenshots/setup/05-edit-user-promote-admin.png)

Sustav automatski:
- briše bootstrap korisnika,
- odjavljuje trenutnu sesiju,
- traži prijavu novog administratora.

![Bootstrap korisnik uklonjen](screenshots/setup/06-bootstrap-removed-login.png)

## 7. Prijava novog admina

Novi admin se prijavljuje svojim računom i nastavlja rad.

![Novi admin prijavljen](screenshots/setup/07-new-admin-logged-in.png)
