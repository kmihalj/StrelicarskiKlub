# Administrator - priručnik

Ovaj vodič pokazuje glavne ekrane i tipične adminsitratorske zadatke.

## 1. Početna administratora

Admin vidi standardnu naslovnicu, ali i admin izbornike.

![Admin početna](screenshots/admin/01-admin-home.png)

## 2. Korisnici i uloge

`Admin > Korisnici` služi za:
- promjenu role (`Admin`, `Član`, `Korisnik`, `Polaznik škole`)
- povezivanje korisnika s članom ili polaznikom škole
- uključivanje roditeljske uloge i povezivanje djece.

![Popis korisnika](screenshots/admin/02-admin-users.png)
![Uređivanje korisnika i roditeljskih veza](screenshots/admin/03-admin-edit-parent-user.png)

## 3. Članovi i status plaćanja

Na popisu članova admin vidi dodatnu kolonu sa stanjem plaćanja (iznos duga ili uredno stanje).

![Popis članova s plaćanjima](screenshots/admin/04-admin-clanovi-list.png)

Na profilu člana (`admin/clanovi/{id}`) admin upravlja:
- modelom plaćanja
- ručnim/dodatnim stavkama
- potvrdom uplata
- pregledom povijesti stavki.

![Praćenje plaćanja člana](screenshots/admin/05-admin-member-payments-section.png)

## 4. Polaznici škole i školarina

Na profilu polaznika admin vodi:
- model školarine (`u cijelosti`, `u dvije rate`, `oslobođen`)
- potvrde uplata
- praćenje druge rate nakon 8 treninga
- evidenciju dolazaka i dokumente.

![Školarina polaznika](screenshots/admin/06-admin-school-payments-section.png)

## 5. Administracija plaćanja i izvještaji

`Admin > Plaćanja` uključuje:
- setup modela plaćanja i iznosa
- filtere perioda/statusa/naplate
- sažetke dugovanja
- tablice dužnika i svih stavki
- CSV export.

![Dashboard plaćanja](screenshots/admin/07-admin-payments-dashboard.png)

## 6. Teme, logo i favicon

`Admin > Teme`:
- odabir aktivne teme
- uređivanje boja
- light/dark varijante
- upload globalnog loga i favicona.

![Administracija tema](screenshots/admin/08-admin-themes.png)

## 7. Članci i sadržaj

`Admin > Članci` prikazuje sve sadržaje.

![Popis članaka](screenshots/admin/09-admin-articles-list.png)

Kreiranje članka koristi CKEditor (sadržaj, menu, Facebook link, mediji).

![Unos članka](screenshots/admin/10-admin-article-create.png)

## 8. Turniri i rezultati

`Admin > Turniri` za kreiranje i pregled turnira:

![Popis turnira](screenshots/admin/11-admin-tournaments.png)

Unos rezultata po turniru:
- član
- stil
- kategorija
- polja po tipu turnira
- plasman i eliminacije
- mediji i opisi.

![Unos rezultata](screenshots/admin/12-admin-results-entry.png)
