# Administrator - detaljni priručnik

Administratorska uloga je operativno središte cijelog sustava. U praksi to znači da administrator vodi korisnike i ovlasti, postavlja i nadzire turnire, potvrđuje uplate, uređuje sadržaj stranice i održava osnovne klupske postavke.

Važna napomena za role: administrator može ujedno biti i član (ako je povezan na profil člana), pa za svoj profil može koristiti sve što koristi i član. Također, administrator može biti označen i kao roditelj, pa uz adminsko sučelje može imati i roditeljski pregled povezane djece.

## 1. Administratorski izbornik i početna orijentacija

Nakon prijave administrator u glavnoj navigaciji vidi dodatni izbornik `Admin`. Kroz taj izbornik ulazi se u sve ključne module: podešenja, nadolazeće turnire, plaćanja, korisnike, teme, članke i podatke o klubu.

![01. Admin menu](<screenshots/admin-role/01. admin menu.png>)

## 2. Nadolazeći turniri i prijave

Modul `Nadolazeći turniri` služi za unos i održavanje kalendara turnira. Tu se može ručno dodati turnir, dopuniti tip turnira, definirati kotizacija, zaključati prijave i pripremiti sve prije nego članovi krenu s prijavama.

![02. Nadolazeći turniri](<screenshots/admin-role/02. nadolazeći turniri.png>)
![16. Dodavanje turnira](<screenshots/admin-role/16. dodavanje turnira.png>)

Na detalju pojedinog turnira administrator vidi sve prijave, može izvesti CSV za organizatora i po potrebi ukloniti člana s turnira uz obaveznu napomenu. Uklanjanje prijave ne briše trag, nego prijavu prebacuje u povijest uklonjenih.

![03. Prijave i izvoz podataka](<screenshots/admin-role/03. prijave i izvoz podataka.png>)
![03a. Prijave i izvoz podataka - detalj](<screenshots/admin-role/03a. Slika zaslona 2026-04-02 u 01.20.11.png>)
![03b. Prijave i izvoz podataka - detalj](<screenshots/admin-role/03b. Slika zaslona 2026-04-02 u 01.21.01.png>)

## 3. Plaćanja članova i škole

Modul `Plaćanja` je kontrolna ploča za članarine, kotizacije i školarine. U njemu administrator podešava modele naplate, prati otvorene stavke i vidi sažetak po osobi i po statusu.

![04. Plaćanja](<screenshots/admin-role/04. plaćanja.png>)

Na popisu članova vidi se i stanje plaćanja, a na profilu člana administrator može potvrditi uplatu, mijenjati status i dodavati ručne stavke kada je to potrebno.

![05. Popis članova - sa plaćanjima](<screenshots/admin-role/05. popis članova - sa plaćanjima.png>)
![07. Admin radnje na članu](<screenshots/admin-role/07. admin radnje na članu.png>)
![08. Potvrda plaćanja](<screenshots/admin-role/08. potvrda plaćanja.png>)

Za stavke koje se vode kao gotovina (npr. naplata treneru), status se također vodi kroz sustav kako bi izvještaji ostali točni.

![06. Plaćanja treneru](<screenshots/admin-role/06. plaćanja treneru.png>)

## 4. Podaci o klubu i osnovna sportska podešenja

`Admin -> Klub` služi za održavanje službenih podataka kluba koji se koriste kroz cijelu aplikaciju, uključujući prikaz i generiranje podataka za uplatu.

![09. Podaci o klubu](<screenshots/admin-role/09. Podaci o klubu.png>)

U modulu podešenja administrator održava osnovne sportske šifrarnike (tipovi turnira, stilovi, kategorije i polja rezultata). To je važno jer o tim postavkama ovise forme za unos rezultata i prijave.

![10. Osnovne stavke za unos rezultata](<screenshots/admin-role/10. osnovne stavke za unos rezultata.png>)

## 5. Korisnici, role i povezivanja

U modulu `Korisnici` administrator upravlja identitetima i pravima pristupa. Tu se korisniku postavlja rola (`Admin`, `Član`, `Korisnik`, `Polaznik škole`), povezani profil i roditeljske veze.

![11. Popis korisnika](<screenshots/admin-role/11. popis korisnika.png>)
![12. Uređivanje korisnika](<screenshots/admin-role/12. uređivanje korisnika.png>)

Praktično pravilo rada:

1. rola određuje osnovna prava,
2. povezani profil određuje čiji se podaci i moduli otvaraju,
3. roditeljske veze određuju koja djeca su vidljiva roditeljskom računu.

Za roditeljske veze sustav primjenjuje ograničenja (maloljetna djeca, maksimalan broj povezanih djece i roditelja po djetetu), pa administracija ostaje konzistentna i kontrolirana.

## 6. Oglasnik i članci

Administrator može moderirati i održavati sadržaj stranice kroz `Oglasnik` i `Članke`. To uključuje pregled objava, unos novih sadržaja, izmjene postojećih i objavu informacija važnih za rad kluba.

![13. Oglasnik](<screenshots/admin-role/13. oglasnik.png>)
![14. Unos oglasa](<screenshots/admin-role/14. unos oglasa.png>)
![15. Unos članka](<screenshots/admin-role/15. unos članka.png>)
![15a. Uređivanje članka](<screenshots/admin-role/15a. uređivanje članka.png>)

## 7. Škola streličarstva

U modulu škole administrator vodi aktivne i neaktivne polaznike, status školarine i evidenciju dolazaka.

![17. Polaznici škole](<screenshots/admin-role/17. polaznici škole.png>)
![18. Evidencija dolazaka](<screenshots/admin-role/18.evidencija dolazaka.png>)

Na profilu polaznika administrator može:

1. uređivati osobne podatke,
2. voditi dokumente,
3. postaviti model školarine i potvrđivati uplate,
4. po potrebi prebaciti polaznika u članove.

## 8. Teme i vizualni identitet

Kroz modul tema administrator bira i uređuje aktivni vizualni stil sučelja.

![19. Uređivanje teme](<screenshots/admin-role/19. uređivanje teme.png>)

Ovdje se centralno upravlja bojama i izgledom, pa se promjena odmah vidi na cijelom web sučelju.

## Završna operativna napomena

Najstabilniji način rada je da se svaka promjena radi redom: prvo korisnici i povezivanja, zatim sadržaj i postavke, pa tek onda operativni moduli poput prijava i plaćanja. Tako se izbjegnu situacije u kojima je funkcionalnost tehnički dostupna, ali korisnik zbog pogrešne role ili nepovezanog profila ne vidi očekivane opcije.
