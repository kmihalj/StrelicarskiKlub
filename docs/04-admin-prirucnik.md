# Administrator - detaljni priručnik

Administratorska uloga je operativno središte cijelog sustava. U praksi to znači da administrator vodi korisnike i ovlasti, postavlja i nadzire turnire, potvrđuje uplate, uređuje sadržaj stranice i održava osnovne klupske postavke.

Važna napomena za role: administrator može ujedno biti i član (ako je povezan na profil člana), pa za svoj profil može koristiti sve što koristi i član. Također, administrator može biti označen i kao roditelj, pa uz adminsko sučelje može imati i roditeljski pregled povezane djece.

## 1. Administratorski izbornik i početna orijentacija

Nakon prijave administrator u glavnoj navigaciji vidi dodatni izbornik `Admin`. Kroz taj izbornik ulazi se u sve ključne module: podešenja, nadolazeće turnire, plaćanja, korisnike, teme, članke i podatke o klubu.

Na slici ispod prikazano je: Admin menu.
![01. Admin menu](<screenshots/admin-role/01. admin menu.webp>)

## 2. Nadolazeći turniri i prijave

Modul `Nadolazeći turniri` služi za unos i održavanje kalendara turnira. Tu se može ručno dodati turnir, dopuniti tip turnira, definirati kotizacija, zaključati prijave i pripremiti sve prije nego članovi krenu s prijavama.

Na vrhu modula nalazi se i gumb za uvoz turnira s `archery.hr`, pa se popis nadolazećih turnira može brzo osvježiti za tekuću i sljedeću godinu bez ručnog unosa svakog pojedinog zapisa.

Na slici ispod prikazano je: Nadolazeći turniri.
![02. Nadolazeći turniri](<screenshots/admin-role/02. nadolazeći turniri.webp>)
Na slici ispod prikazano je: Dodavanje turnira.
![16. Dodavanje turnira](<screenshots/admin-role/16. dodavanje turnira.webp>)

Na detalju pojedinog turnira administrator vidi sve prijave, može izvesti CSV za organizatora i po potrebi ukloniti člana s turnira uz obaveznu napomenu. Uklanjanje prijave ne briše trag, nego prijavu prebacuje u povijest uklonjenih.

Za turnire koji su završili administrator može iz modula prošlih turnira pokrenuti akciju `Kreiraj rezultate`. Time sustav iz prijava pripremi početni unos rezultata, a nakon toga administrator treba upisati stvarno ostvarene rezultate članova.

Na slici ispod prikazano je: Prijave i izvoz podataka.
![03. Prijave i izvoz podataka](<screenshots/admin-role/03. prijave i izvoz podataka.webp>)
Na slici ispod prikazano je: Prijave i izvoz podataka - detalj.
![03a. Prijave i izvoz podataka - detalj](<screenshots/admin-role/03a. Slika zaslona 2026-04-02 u 01.20.11.webp>)
Na slici ispod prikazano je: Prijave i izvoz podataka - detalj.
![03b. Prijave i izvoz podataka - detalj](<screenshots/admin-role/03b. Slika zaslona 2026-04-02 u 01.21.01.webp>)

## 3. Plaćanja članova i škole

Modul `Plaćanja` je kontrolna ploča za članarine, kotizacije i školarine. U njemu administrator podešava modele naplate, prati otvorene stavke i vidi sažetak po osobi i po statusu.

Na slici ispod prikazano je: Plaćanja.
![04. Plaćanja](<screenshots/admin-role/04. plaćanja.webp>)

Na popisu članova vidi se i stanje plaćanja, a na profilu člana administrator može potvrditi uplatu, mijenjati status i dodavati ručne stavke kada je to potrebno.

**Važno: potvrde plaćanja treba označavati nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa u tom razdoblju može postojati uplata koja je stvarno izvršena, ali još nije administrativno označena kao podmirena.**

Na slici ispod prikazano je: Popis članova - sa plaćanjima.
![05. Popis članova - sa plaćanjima](<screenshots/admin-role/05. popis članova - sa plaćanjima.webp>)
Na slici ispod prikazano je: Admin radnje na članu.
![07. Admin radnje na članu](<screenshots/admin-role/07. admin radnje na članu.webp>)
Na slici ispod prikazano je: Potvrda plaćanja.
![08. Potvrda plaćanja](<screenshots/admin-role/08. potvrda plaćanja.webp>)

Za stavke koje se vode kao gotovina (npr. naplata treneru), status se također vodi kroz sustav kako bi izvještaji ostali točni.

Na slici ispod prikazano je: Plaćanja treneru.
![06. Plaćanja treneru](<screenshots/admin-role/06. plaćanja treneru.webp>)

## 4. Podaci o klubu i osnovna sportska podešenja

`Admin -> Klub` služi za održavanje službenih podataka kluba koji se koriste kroz cijelu aplikaciju, uključujući prikaz i generiranje podataka za uplatu.

Na slici ispod prikazano je: Podaci o klubu.
![09. Podaci o klubu](<screenshots/admin-role/09. Podaci o klubu.webp>)

U modulu podešenja administrator održava osnovne sportske šifrarnike (tipovi turnira, stilovi, kategorije i polja rezultata). To je važno jer o tim postavkama ovise forme za unos rezultata i prijave.

Na slici ispod prikazano je: Osnovne stavke za unos rezultata.
![10. Osnovne stavke za unos rezultata](<screenshots/admin-role/10. osnovne stavke za unos rezultata.webp>)

## 5. Korisnici, role i povezivanja

U modulu `Korisnici` administrator upravlja identitetima i pravima pristupa. Tu se korisniku postavlja rola (`Admin`, `Član`, `Korisnik`, `Polaznik škole`), povezani profil i roditeljske veze.

Na slici ispod prikazano je: Popis korisnika.
![11. Popis korisnika](<screenshots/admin-role/11. popis korisnika.webp>)
Na slici ispod prikazano je: Uređivanje korisnika.
![12. Uređivanje korisnika](<screenshots/admin-role/12. uređivanje korisnika.webp>)

Praktično pravilo rada:

1. rola određuje osnovna prava,
2. povezani profil određuje čiji se podaci i moduli otvaraju,
3. roditeljske veze određuju koja djeca su vidljiva roditeljskom računu.

Za roditeljske veze sustav primjenjuje ograničenja (djeca mlađa od 23 godine, maksimalan broj povezanih djece i roditelja po djetetu), pa administracija ostaje konzistentna i kontrolirana.

## 6. Oglasnik i članci

Administrator može moderirati i održavati sadržaj stranice kroz `Oglasnik` i `Članke`. To uključuje pregled objava, unos novih sadržaja, izmjene postojećih i objavu informacija važnih za rad kluba.

Na slici ispod prikazano je: Oglasnik.
![13. Oglasnik](<screenshots/admin-role/13. oglasnik.webp>)
Na slici ispod prikazano je: Unos oglasa.
![14. Unos oglasa](<screenshots/admin-role/14. unos oglasa.webp>)
Na slici ispod prikazano je: Unos članka.
![15. Unos članka](<screenshots/admin-role/15. unos članka.webp>)
Na slici ispod prikazano je: Uređivanje članka.
![15a. Uređivanje članka](<screenshots/admin-role/15a. uređivanje članka.webp>)

## 7. Škola streličarstva

U modulu škole administrator vodi aktivne i neaktivne polaznike, status školarine i evidenciju dolazaka.

Na slici ispod prikazano je: Polaznici škole.
![17. Polaznici škole](<screenshots/admin-role/17. polaznici škole.webp>)
Na slici ispod prikazano je: Evidencija dolazaka.
![18. Evidencija dolazaka](<screenshots/admin-role/18.evidencija dolazaka.webp>)

Na profilu polaznika administrator može:

1. uređivati osobne podatke,
2. voditi dokumente,
3. postaviti model školarine i potvrđivati uplate,
4. po potrebi prebaciti polaznika u članove.

**Važno: potvrde plaćanja treba označavati nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa u tom razdoblju može postojati uplata koja je stvarno izvršena, ali još nije administrativno označena kao podmirena.**

## 8. Teme i vizualni identitet

Kroz modul tema administrator bira i uređuje aktivni vizualni stil sučelja.

Na slici ispod prikazano je: Uređivanje teme.
![19. Uređivanje teme](<screenshots/admin-role/19. uređivanje teme.webp>)

Ovdje se centralno upravlja bojama i izgledom, pa se promjena odmah vidi na cijelom web sučelju.

## 9. Klupski zid i moderiranje poruka

Administrator može pisati poruke na `Klupski zid` kao i ostali ovlašteni korisnici. Uz to, administrator ima dodatne moderatorske ovlasti: može istaknuti bilo koju poruku na zidu i može obrisati bilo koju poruku.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid ne treba upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

## Završna operativna napomena

Najstabilniji način rada je da se svaka promjena radi redom: prvo korisnici i povezivanja, zatim sadržaj i postavke, pa tek onda operativni moduli poput prijava i plaćanja. Tako se izbjegnu situacije u kojima je funkcionalnost tehnički dostupna, ali korisnik zbog pogrešne role ili nepovezanog profila ne vidi očekivane opcije.

