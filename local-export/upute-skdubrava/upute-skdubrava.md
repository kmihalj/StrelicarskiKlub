# Korisnik član - detaljne upute

Ovaj vodič je namjerno pisan opširnije, tako da se kroz njega može proći i bez tehničkog predznanja. Cilj nije samo pokazati gdje kliknuti, nego i objasniti zašto se neke opcije prikazuju, a neke ne, te kako prepoznati normalno ponašanje sustava.

## 1. Što se događa nakon registracije

Kada član otvori registraciju i unese svoje podatke, sustav ne radi samo izradu korisničkog računa. U isto vrijeme pokušava automatski povezati novi račun s postojećim profilom člana u bazi kluba.

Automatsko povezivanje se radi samo kada identitet stvarno odgovara postojećem članu. U praksi to znači da se podaci moraju podudarati kroz:

1. OIB
2. e-mail adresu
3. ime i prezime
4. broj telefona

Ako je podudaranje uspješno, račun odmah dobiva vezu na profil člana i članske ovlasti. Ako nije, registracija može biti uspješna, ali račun ostaje bez veze na člana dok administrator ne napravi ručno povezivanje.

Na slici ispod prikazano je: Naslovnica.
![01. Naslovnica](images/member-role/01. naslovnica.png)
Na slici ispod prikazano je: Registracija.
![02. Registracija](images/member-role/02. registracija.png)
Na slici ispod prikazano je: Podaci za registraciju.
![03. Podaci za registraciju](images/member-role/03. podaci za registraciju.png)
Na slici ispod prikazano je: Prijava na site.
![02a. Prijava na site](images/member-role/02a. prijava na site.png)

## 2. Kako odmah prepoznati je li račun pravilno povezan

Nakon prve prijave najvažniji signal je izbornik `Korisnik`.

Kada je račun pravilno povezan s članom, uobičajeno se prikazuju stavke poput `Profil`, `Prijave na turnire` i `Odjava`. Ako je račun registriran, ali još nije povezan na člana, korisnik je prijavljen, no u izborniku najčešće ostaje samo `Odjava`.

To nije kvar, nego znak da je potreban kontakt s administratorom koji u administraciji korisnika postavlja odgovarajuću rolu i povezani profil člana.

Na slici ispod prikazano je: Provjera statusa.
![04. Provjera statusa](images/member-role/04. provjera statusa.png)
Na slici ispod prikazano je: Admin povezuje člana sa korisnikom.
![05. Admin povezuje člana sa korisnikom](images/member-role/05. admin povezuje člana sa korisnikom.png)
Na slici ispod prikazano je: Prijavljeni kao član.
![06. Prijavljeni kao član](images/member-role/06. prijavljeni kao član.png)

## 3. Profil člana i dokumentacija

Profil člana je središnje mjesto za osobne podatke, sportske informacije i dokumentaciju. U sekciji `Pregled dokumenata` nalaze se podaci o dokumentima člana i liječničkim pregledima.

Važna stvar za razumjeti: član vidi svoje dokumente. U profilima gdje korisnik nema pravo na dokumente ta sekcija se u pravilu uopće ne prikazuje. Dakle, korisnik bez prava najčešće ne dobiva poruku o zabrani, nego taj dio sučelja jednostavno ne vidi.

Na slici ispod prikazano je: Profil korisnika.
![07. Profil korisnika](images/member-role/07. profil korisnika.png)
Na slici ispod prikazano je: Dokumenti i podaci.
![08. Dokumenti i podaci](images/member-role/08. dokumenti i podaci.png)

## 4. Treninzi člana

Modul `Moji treninzi` služi za osobnu evidenciju dvoranskih i vanjskih treninga. Član unosi rezultate po serijama, kasnije ih pregledava i po potrebi uređuje ili briše.

Sustav treninge veže na prijavljenog korisnika i njegov profil člana, pa unosi ostaju odvojeni po korisnicima i ne miješaju se između članova.

Na slici ispod prikazano je: Pregled treninga.
![09. Pregled treninga](images/member-role/09. pregled treninga.png)
Na slici ispod prikazano je: Unos treninga.
![10. Unos treninga](images/member-role/10. unos treninga.png)
Na slici ispod prikazano je: Pregled i uređivanje treninga.
![11. Pregled i uređivanje treninga](images/member-role/11. pregled i uređivanje treninga.png)

## 5. Popis članova i profil rezultata

Kroz izbornik `O nama` -> `Članovi` otvara se popis aktivnih članova. Tu se može otvoriti profil pojedinog člana i pregledati rezultate, rekorde i nastupe na turnirima.

Na slici ispod prikazano je: Popis članova.
![12. Popis članova](images/member-role/12. popis članova.png)
Na slici ispod prikazano je: Profil člana.
![13. Profil člana](images/member-role/13. profil člana.png)

## 6. Prijave na turnire

Prijava na turnire je jednostavna forma, ali sustav u pozadini radi nekoliko važnih provjera. Prikazuju se turniri u dostupnom razdoblju (u pravilu sljedećih 60 dana), ne dopušta se dvostruka aktivna prijava istog člana na isti turnir, a prijave na zaključane turnire nisu dopuštene.

Kategorija se može promijeniti. Sustav samo predlaže početnu kategoriju prema dobi i spolu člana, ali korisnik može odabrati drugu dostupnu kategoriju iz ponuđenog popisa.

Na slici ispod prikazano je: Prijava na turnire.
![14. Prijava na turnire](images/member-role/14. prijava na turnire.png)
Na slici ispod prikazano je: Prijava na odabrani turnir.
![15. Prijava na odabrani turnir](images/member-role/15. prijava na odabrani turnir.png)
Na slici ispod prikazano je: Pregled prijave.
![16. Pregled prijave](images/member-role/16. pregled prijave.png)
Na slici ispod prikazano je: Popis prijava na turnire.
![17. Popis prijava na turnire](images/member-role/17. popis prijava na turnire.png)

## 7. Kotizacije i plaćanja

Kod turnira postoje dvije varijante naplate kotizacije:

1. gotovina
2. plaćanje preko računa kluba

Ako je za turnir postavljeno plaćanje preko računa kluba, u prijavi i popisu prijava pojavljuje se poveznica na `Plaćanja člana`, gdje se vidi stanje, odabire stavka duga i prikazuju podaci za uplatu s barkodom.

Kod članarine (sezonske ili godišnje) član može imati više ponuđenih varijanti plaćanja. O odabranoj varijanti ovisi status člana u pojedinoj sezoni. Ako je odabrana podupiruća varijanta za dvoransku i/ili vanjsku sezonu, osoba i dalje ostaje član kluba, ali u toj sezoni nema pravo korištenja dvorane i/ili terena.

**Važno: potvrde plaćanja radi administrator nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa ako je nešto plaćeno, a još nije evidentirano, to najčešće znači da administrator još nije napravio sljedeći uvid i označio da je uplata podmirena.**

Kada uplata bude evidentirana, status prelazi na `Plaćeno`.

Na slici ispod prikazano je: Prijava na turnir koji se plaća preko računa.
![18. Prijava na turnir koji se plaća preko računa](images/member-role/18. prijava na turnir koji se plaća preko računa.png)
Na slici ispod prikazano je: Pregled prijave.
![19. Pregled prijave](images/member-role/19. pregled prijave_.png)
Na slici ispod prikazano je: Popis prijava - link na plaćanje.
![20. Popis prijava - link na plaćanje](images/member-role/20. popis prijava - link na plaćanje.png)
Na slici ispod prikazano je: Plaćanja.
![21. Plaćanja](images/member-role/21. plaćanja.png)

Na naslovnici se kod otvorenog duga prikazuju upozorenja tipa `Potrebna uplata`. To nije greška, nego podsjetnik da treba otvoriti modul plaćanja i podmiriti stavku.

Na slici ispod prikazano je: Naslovnica - obavijesti članu.
![22. Naslovnica - obavijesti članu](images/member-role/22. naslovnica - obavijesti članu.png)
Na slici ispod prikazano je: Naslovnica - članarina.
![23. Naslovnica - članarina](images/member-role/23. naslovnica - članarina.png)
Na slici ispod prikazano je: Pregled plaćanja - članarina.
![24. Pregled plaćanja - članarina](images/member-role/24. pregled plaćanja - članarina.png)
Na slici ispod prikazano je: Upute za plaćanje ako ne želite plaćati barkodom.
![25. Upute za plaćanje ako ne želite plaćati barkodom](images/member-role/25. upute za plaćanje ako ne želite plaćati barkodom.png)
Na slici ispod prikazano je: Sva plaćanja su podmirena.
![26. Sva plaćanja su podmirena](images/member-role/26. sva plaćanja su podmirena.png)

## 8. Oglasnik

`Oglasnik` služi za objavu, razmjenu i prodaju opreme. Član može izraditi oglas, dodati slike, kasnije ga uređivati, privremeno deaktivirati ili trajno obrisati.

Na slici ispod prikazano je: Oglasnik.
![27. Oglasnik](images/member-role/27. oglasnik.png)
Na slici ispod prikazano je: Kreiranje oglasa.
![28. Kreiranje oglasa](images/member-role/28. kreiranje oglasa.png)
Na slici ispod prikazano je: Radnje sa oglasom.
![29. Radnje sa oglasom](images/member-role/29. radnje sa oglasom.png)
Na slici ispod prikazano je: Vaš oglas.
![30. Vaš oglas](images/member-role/30. vaš oglas.png)

## 9. Klupski zid

Svi članovi imaju pravo pisati poruke na `Klupski zid`. To je mjesto za kratke klupske obavijesti, dogovore i informacije koje želite podijeliti sa zajednicom.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid nemojte upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

## Završna napomena za člana

Ako nakon registracije ne vidite `Profil` i `Prijave na turnire`, najčešći uzrok je da račun još nije povezan s profilom člana. U toj situaciji treba kontaktirati administratora i poslati točne podatke korištene pri registraciji (ime i prezime, OIB, e-mail, broj telefona).

Kada je povezivanje dovršeno, članski prikaz i mogućnosti postaju dostupni odmah nakon sljedeće prijave.

# Polaznik škole streličarstva - detaljne upute

Ovaj vodič opisuje kako sustav izgleda kada je korisnički račun povezan s profilom polaznika škole streličarstva. Naglasak je na tome što polaznik vidi i prati kroz svoj račun, a što administrativno održava klub.

## 1. Registracija i povezivanje

Polaznik se registrira svojim podacima. Kao i kod ostalih korisnika, sustav pri registraciji pokušava automatski povezati račun s postojećim profilom u bazi. Ako podaci odgovaraju, veza se napravi odmah; ako ne, administrator ručno dovršava povezivanje.

Na slici ispod prikazano je: Naslovnica i registracija.
![01. Naslovnica i registracija](images/school-role/01. naslovnica i registracija.png)
Na slici ispod prikazano je: Registracija.
![02. Registracija](images/school-role/02. registracija.png)
Na slici ispod prikazano je: Provjera registracije.
![03. Provjera registracije](images/school-role/03. provjera registracije.png)
Na slici ispod prikazano je: Admin Vas povezuje sa profilom.
![04. Admin Vas povezuje sa profilom](images/school-role/04. admin Vas povezuje sa profilom.png)

Nakon uspješnog povezivanja naslovnica počinje prikazivati blok `Moji podaci škole streličarstva`, s osnovnim statusom i brzim ulazom na detaljni profil.

Na slici ispod prikazano je: Naslovnica povezanog polatnika.
![05. Naslovnica povezanog polatnika](images/school-role/05. naslovnica povezanog polatnika.png)

## 2. Profil polaznika

Profil polaznika je glavno mjesto pregleda podataka. U njemu se vidi osobni profil, stanje dolazaka i školarina.

Važno je znati da polaznik u pravilu pregledava vlastite podatke, dok uređivanje ključnih administrativnih stavki (npr. promjene modela školarine i potvrde uplata) radi administrator.

Na slici ispod prikazano je: Pregled profila.
![06. Pregled profila](images/school-role/06. pregled profila.png)

## 3. Školarina i status uplata

U sekciji školarine prikazuju se stavke, status (`plaćeno` / `nije plaćeno`) i informativne poruke. Kada je stavka otvorena, na naslovnici i profilu pojavljuje se odgovarajuća obavijest.

**Važno: potvrde plaćanja radi administrator nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa ako je nešto plaćeno, a još nije evidentirano, to najčešće znači da administrator još nije napravio sljedeći uvid i označio da je uplata podmirena.**

Nakon što administrator potvrdi uplatu, status se ažurira i obavijest prelazi u uredno stanje.

Na slici ispod prikazano je: Pregled školarine.
![07. Pregled školarine](images/school-role/07. pregled školarine.png)
Na slici ispod prikazano je: Sve plaćeno.
![08. Sve plaćeno](images/school-role/08. sve plaćeno.png)
Na slici ispod prikazano je: Sve plaćeno u profilu.
![09. Sve plaćeno u profilu](images/school-role/09. sve plaćeno u profilu.png)

## 4. Dolasci i praktična uporaba

Polaznik kroz svoj profil može redovito pratiti evidenciju dolazaka i promjene statusa školarine bez dodatne komunikacije za svaku pojedinu stavku. To olakšava praćenje obveza i daje jasan pregled što je već podmireno, a što još čeka potvrdu.

Ako se podaci ne prikazuju kako očekujete nakon registracije, najčešći uzrok je da korisnički račun još nije povezan s profilom polaznika. U tom slučaju administrator treba dovršiti povezivanje u modulu korisnika.

## 5. Klupski zid

Polaznici škole mogu pisati poruke na `Klupski zid` kroz svoje korisničke račune. Zid je namijenjen kratkim javnim obavijestima i porukama koje su korisne cijeloj zajednici.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid nemojte upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

# Roditelj - detaljni priručnik

Roditeljski račun je zamišljen kao zaseban, punopravan korisnički račun roditelja, kroz koji se pregledavaju podaci djeteta. To znači da roditelj ne treba i ne smije koristiti djetetov račun.

Najvažnija napomena prije svega:

1. roditelj se registrira i prijavljuje kao roditelj, svojim podacima,
2. ne prijavljuje se kao dijete,
3. prijava kao dijete nije potrebna za roditeljski pregled, a može otvoriti pitanja privatnosti i prava djeteta (GDPR).

## 1. Kako roditeljski pristup nastaje

Nakon registracije roditeljskog računa sustav ne može sam pogoditi koje je dijete povezano s tim računom. Zato administrator u modulu korisnika mora uključiti roditeljsku oznaku i povezati dijete (član i/ili polaznik škole).

Tek nakon tog povezivanja roditeljski račun dobiva smisleni sadržaj na naslovnici i pristup profilima djece.

## 2. Roditelj člana kluba

Kod roditelja koji je povezan s djetetom članom kluba, sustav na naslovnici prikazuje blokove za dijete, stanje liječničkog i obavijesti o plaćanjima.

Na slici ispod prikazano je: Naslovnica.
![00. Naslovnica](images/parent-member-role/00. naslovnica.png)
Na slici ispod prikazano je: Registracija.
![01. Registracija](images/parent-member-role/01. registracija.png)
Na slici ispod prikazano je: Registracija roditelja.
![02. Registracija roditelja](images/parent-member-role/02. registracija roditelja.png)
Na slici ispod prikazano je: Prijava na site.
![03. Prijava na site](images/parent-member-role/03. prijava na site.png)
Na slici ispod prikazano je: Provjera statusa.
![04. Provjera statusa](images/parent-member-role/04. provjera statusa.png)
Na slici ispod prikazano je: Admin vas povezuje sa djecom.
![05. Admin vas povezuje sa djecom](images/parent-member-role/05. admin vas povezuje sa djecom.png)
Na slici ispod prikazano je: Pregled djece.
![06. Pregled djece](images/parent-member-role/06. pregled djece.png)

Kada je dijete član, roditelj preko svog računa može:

1. otvoriti profil djeteta člana,
2. pregledati dokumente i status liječničkog,
3. otvoriti pregled treninga djeteta,
4. prijaviti dijete na turnir,
5. otvoriti i pratiti plaćanja djeteta.

Na slici ispod prikazano je: Pregled djeteta.
![07. Pregled djeteta](images/parent-member-role/07. Pregled djeteta.png)
Na slici ispod prikazano je: Pregled podataka djeteta.
![08. Pregled podataka djeteta](images/parent-member-role/08. pregled podataka djeteta.png)
Na slici ispod prikazano je: Prijava djeteta na turnir.
![09. Prijava djeteta na turnir](images/parent-member-role/09. prijava djeteta na turnir.png)
Na slici ispod prikazano je: Pregled prijave.
![10. Pregled prijave](images/parent-member-role/10. pregled prijave.png)
Na slici ispod prikazano je: Pregled svih prijava.
![11. Pregled svih prijava](images/parent-member-role/11. pregled svih prijava.png)
Na slici ispod prikazano je: Naslovnica sa podacima.
![12. Naslovnica sa podacima](images/parent-member-role/12. naslovnica sa podacima.png)

Kod turnirskih prijava roditelj može odabrati dijete iz padajuće liste i prijavljivati samo onu djecu koja su mu stvarno povezana u administraciji.

Plaćanja se vode kroz isti modul kao i za članove, pa roditelj može vidjeti otvorene stavke, birati dug za uplatu i koristiti podatke za plaćanje.

**Važno: potvrde plaćanja radi administrator nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa ako je nešto plaćeno, a još nije evidentirano, to najčešće znači da administrator još nije napravio sljedeći uvid i označio da je uplata podmirena.**

Na slici ispod prikazano je: Plaćanja.
![13. Plaćanja](images/parent-member-role/13. plaćanja.png)
Na slici ispod prikazano je: Plaćanja ako ne koristite barcode.
![14. Plaćanja ako ne koristite barcode](images/parent-member-role/14. plaćanja ako ne koristite barcode.png)
Na slici ispod prikazano je: Plaćanje članarine.
![15. Plaćanje članarine](images/parent-member-role/15. plaćanje članarine.png)
Na slici ispod prikazano je: Sve plaćeno za dijete.
![16. Sve plaćeno za dijete](images/parent-member-role/16. sve plaćeno za dijete.png)
Na slici ispod prikazano je: Pregled plaćanja.
![17. Pregled plaćanja](images/parent-member-role/17. pregled plaćanja.png)
Na slici ispod prikazano je: Pregled članova.
![18. Pregled članova](images/parent-member-role/18. pregled članova.png)

## 3. Roditelj polaznika škole streličarstva

Roditelj može biti povezan i s djetetom koje je polaznik škole. U toj varijanti fokus je na profilu polaznika, dolascima i školarini.

Na slici ispod prikazano je: Naslovnica i registracija.
![01. Naslovnica i registracija](images/parent-school-role/01. naslovnica i registracija.png)
Na slici ispod prikazano je: Registracija.
![02. Registracija](images/parent-school-role/02. registracija.png)
Na slici ispod prikazano je: Provjera prijave.
![03. Provjera prijave](images/parent-school-role/03. provjera prijave.png)
Na slici ispod prikazano je: Admin vas povezuje s djecom.
![04. Admin vas povezuje s djecom](images/parent-school-role/04. admin vas povezuje s djecom.png)

Kada je povezivanje napravljeno, roditelj otvara profil djeteta polaznika i vidi:

1. osnovne podatke,
2. evidenciju dolazaka,
3. stanje školarine i otvorene stavke.

Na slici ispod prikazano je: Pregled polaznika - djeteta.
![05. Pregled polaznika - djeteta](images/parent-school-role/05. pregled polaznika - djeteta.png)
Na slici ispod prikazano je: Pregled školarine.
![06. Pregled školarine](images/parent-school-role/06. pregled školarine.png)
Na slici ispod prikazano je: Administrator je potvrdio upatu.
![07. Administrator je potvrdio upatu](images/parent-school-role/07. administrator je potvrdio upatu.png)
Na slici ispod prikazano je: Pregled profila i školarine.
![08. Pregled profila i školarine](images/parent-school-role/08. pregled profila i školarine.png)

Kod školarine sustav jasno razlikuje otvorene i podmirene stavke, a roditelj sve prati preko vlastitog računa bez potrebe za prijavom kao dijete.

**Važno: potvrde plaćanja radi administrator nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa ako je nešto plaćeno, a još nije evidentirano, to najčešće znači da administrator još nije napravio sljedeći uvid i označio da je uplata podmirena.**

## 4. Granice roditeljskog pristupa

Roditelj vidi samo djecu koja su mu stvarno povezana u administraciji. Ako dijete nije povezano, njegove sekcije neće biti dostupne roditeljskom računu.

To je namjerno ponašanje sustava i sigurnosna mjera:

1. štiti privatnost djece,
2. zadržava pregled samo na stvarno ovlaštene osobe,
3. smanjuje mogućnost pogrešne obrade osobnih podataka.

## 5. Klupski zid

Roditeljski račun koji je pravilno povezan i ima roditeljske ovlasti može pisati poruke na `Klupski zid`. To je korisno za kratke javne poruke i obavijesti koje su relevantne široj zajednici kluba.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid nemojte upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

## Završna napomena za roditelje

Ako nakon registracije vidite samo osnovni korisnički izbornik bez podataka djece, najčešći uzrok je da administrativno povezivanje još nije dovršeno. U tom slučaju treba se javiti administratoru da potvrdi roditeljsku oznaku i doda odgovarajuće veze prema djetetu.

# Administrator - detaljni priručnik

Administratorska uloga je operativno središte cijelog sustava. U praksi to znači da administrator vodi korisnike i ovlasti, postavlja i nadzire turnire, potvrđuje uplate, uređuje sadržaj stranice i održava osnovne klupske postavke.

Važna napomena za role: administrator može ujedno biti i član (ako je povezan na profil člana), pa za svoj profil može koristiti sve što koristi i član. Također, administrator može biti označen i kao roditelj, pa uz adminsko sučelje može imati i roditeljski pregled povezane djece.

## 1. Administratorski izbornik i početna orijentacija

Nakon prijave administrator u glavnoj navigaciji vidi dodatni izbornik `Admin`. Kroz taj izbornik ulazi se u sve ključne module: podešenja, nadolazeće turnire, plaćanja, korisnike, teme, članke i podatke o klubu.

Na slici ispod prikazano je: Admin menu.
![01. Admin menu](images/admin-role/01. admin menu.png)

## 2. Nadolazeći turniri i prijave

Modul `Nadolazeći turniri` služi za unos i održavanje kalendara turnira. Tu se može ručno dodati turnir, dopuniti tip turnira, definirati kotizacija, zaključati prijave i pripremiti sve prije nego članovi krenu s prijavama.

Na vrhu modula nalazi se i gumb za uvoz turnira s `archery.hr`, pa se popis nadolazećih turnira može brzo osvježiti za tekuću i sljedeću godinu bez ručnog unosa svakog pojedinog zapisa.

Na slici ispod prikazano je: Nadolazeći turniri.
![02. Nadolazeći turniri](images/admin-role/02. nadolazeći turniri.png)
Na slici ispod prikazano je: Dodavanje turnira.
![16. Dodavanje turnira](images/admin-role/16. dodavanje turnira.png)

Na detalju pojedinog turnira administrator vidi sve prijave, može izvesti CSV za organizatora i po potrebi ukloniti člana s turnira uz obaveznu napomenu. Uklanjanje prijave ne briše trag, nego prijavu prebacuje u povijest uklonjenih.

Za turnire koji su završili administrator može iz modula prošlih turnira pokrenuti akciju `Kreiraj rezultate`. Time sustav iz prijava pripremi početni unos rezultata, a nakon toga administrator treba upisati stvarno ostvarene rezultate članova.

Na slici ispod prikazano je: Prijave i izvoz podataka.
![03. Prijave i izvoz podataka](images/admin-role/03. prijave i izvoz podataka.png)
Na slici ispod prikazano je: Prijave i izvoz podataka - detalj.
![03a. Prijave i izvoz podataka - detalj](images/admin-role/03a. Slika zaslona 2026-04-02 u 01.20.11.png)
Na slici ispod prikazano je: Prijave i izvoz podataka - detalj.
![03b. Prijave i izvoz podataka - detalj](images/admin-role/03b. Slika zaslona 2026-04-02 u 01.21.01.png)

## 3. Plaćanja članova i škole

Modul `Plaćanja` je kontrolna ploča za članarine, kotizacije i školarine. U njemu administrator podešava modele naplate, prati otvorene stavke i vidi sažetak po osobi i po statusu.

Na slici ispod prikazano je: Plaćanja.
![04. Plaćanja](images/admin-role/04. plaćanja.png)

Na popisu članova vidi se i stanje plaćanja, a na profilu člana administrator može potvrditi uplatu, mijenjati status i dodavati ručne stavke kada je to potrebno.

**Važno: potvrde plaćanja treba označavati nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa u tom razdoblju može postojati uplata koja je stvarno izvršena, ali još nije administrativno označena kao podmirena.**

Na slici ispod prikazano je: Popis članova - sa plaćanjima.
![05. Popis članova - sa plaćanjima](images/admin-role/05. popis članova - sa plaćanjima.png)
Na slici ispod prikazano je: Admin radnje na članu.
![07. Admin radnje na članu](images/admin-role/07. admin radnje na članu.png)
Na slici ispod prikazano je: Potvrda plaćanja.
![08. Potvrda plaćanja](images/admin-role/08. potvrda plaćanja.png)

Za stavke koje se vode kao gotovina (npr. naplata treneru), status se također vodi kroz sustav kako bi izvještaji ostali točni.

Na slici ispod prikazano je: Plaćanja treneru.
![06. Plaćanja treneru](images/admin-role/06. plaćanja treneru.png)

## 4. Podaci o klubu i osnovna sportska podešenja

`Admin -> Klub` služi za održavanje službenih podataka kluba koji se koriste kroz cijelu aplikaciju, uključujući prikaz i generiranje podataka za uplatu.

Na slici ispod prikazano je: Podaci o klubu.
![09. Podaci o klubu](images/admin-role/09. Podaci o klubu.png)

U modulu podešenja administrator održava osnovne sportske šifrarnike (tipovi turnira, stilovi, kategorije i polja rezultata). To je važno jer o tim postavkama ovise forme za unos rezultata i prijave.

Na slici ispod prikazano je: Osnovne stavke za unos rezultata.
![10. Osnovne stavke za unos rezultata](images/admin-role/10. osnovne stavke za unos rezultata.png)

## 5. Korisnici, role i povezivanja

U modulu `Korisnici` administrator upravlja identitetima i pravima pristupa. Tu se korisniku postavlja rola (`Admin`, `Član`, `Korisnik`, `Polaznik škole`), povezani profil i roditeljske veze.

Na slici ispod prikazano je: Popis korisnika.
![11. Popis korisnika](images/admin-role/11. popis korisnika.png)
Na slici ispod prikazano je: Uređivanje korisnika.
![12. Uređivanje korisnika](images/admin-role/12. uređivanje korisnika.png)

Praktično pravilo rada:

1. rola određuje osnovna prava,
2. povezani profil određuje čiji se podaci i moduli otvaraju,
3. roditeljske veze određuju koja djeca su vidljiva roditeljskom računu.

Za roditeljske veze sustav primjenjuje ograničenja (djeca mlađa od 23 godine, maksimalan broj povezanih djece i roditelja po djetetu), pa administracija ostaje konzistentna i kontrolirana.

## 6. Oglasnik i članci

Administrator može moderirati i održavati sadržaj stranice kroz `Oglasnik` i `Članke`. To uključuje pregled objava, unos novih sadržaja, izmjene postojećih i objavu informacija važnih za rad kluba.

Na slici ispod prikazano je: Oglasnik.
![13. Oglasnik](images/admin-role/13. oglasnik.png)
Na slici ispod prikazano je: Unos oglasa.
![14. Unos oglasa](images/admin-role/14. unos oglasa.png)
Na slici ispod prikazano je: Unos članka.
![15. Unos članka](images/admin-role/15. unos članka.png)
Na slici ispod prikazano je: Uređivanje članka.
![15a. Uređivanje članka](images/admin-role/15a. uređivanje članka.png)

## 7. Škola streličarstva

U modulu škole administrator vodi aktivne i neaktivne polaznike, status školarine i evidenciju dolazaka.

Na slici ispod prikazano je: Polaznici škole.
![17. Polaznici škole](images/admin-role/17. polaznici škole.png)
Na slici ispod prikazano je: Evidencija dolazaka.
![18. Evidencija dolazaka](images/admin-role/18.evidencija dolazaka.png)

Na profilu polaznika administrator može:

1. uređivati osobne podatke,
2. voditi dokumente,
3. postaviti model školarine i potvrđivati uplate,
4. po potrebi prebaciti polaznika u članove.

**Važno: potvrde plaćanja treba označavati nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa u tom razdoblju može postojati uplata koja je stvarno izvršena, ali još nije administrativno označena kao podmirena.**

## 8. Teme i vizualni identitet

Kroz modul tema administrator bira i uređuje aktivni vizualni stil sučelja.

Na slici ispod prikazano je: Uređivanje teme.
![19. Uređivanje teme](images/admin-role/19. uređivanje teme.png)

Ovdje se centralno upravlja bojama i izgledom, pa se promjena odmah vidi na cijelom web sučelju.

## 9. Klupski zid i moderiranje poruka

Administrator može pisati poruke na `Klupski zid` kao i ostali ovlašteni korisnici. Uz to, administrator ima dodatne moderatorske ovlasti: može istaknuti bilo koju poruku na zidu i može obrisati bilo koju poruku.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid ne treba upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

## Završna operativna napomena

Najstabilniji način rada je da se svaka promjena radi redom: prvo korisnici i povezivanja, zatim sadržaj i postavke, pa tek onda operativni moduli poput prijava i plaćanja. Tako se izbjegnu situacije u kojima je funkcionalnost tehnički dostupna, ali korisnik zbog pogrešne role ili nepovezanog profila ne vidi očekivane opcije.

# Instalacija i prvi koraci

Ovaj vodič pokriva cijeli put od prazne instalacije do prvog stvarnog administratora koji preuzima sustav.

## 1. Preduvjeti

Prije instalacije provjeri da je okruženje spremno:

1. PHP 8.2+
2. Composer 2+
3. MySQL 8+
4. Node.js 18+ i npm
5. web server (Apache/Nginx) ili lokalno `php artisan serve`

## 2. Osnovna instalacija projekta

U rootu projekta pokreni:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Zatim u `.env` postavi barem osnovne stavke:

```dotenv
APP_NAME="Archery Club"
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

## 3. Baza, seed i asseti

Pokreni migracije i početni seed:

```bash
php artisan migrate --seed
```

Napravi storage link:

```bash
php artisan storage:link
```

Složi frontend assete:

```bash
npm install
npm run build
```

Za lokalni razvoj može i:

```bash
npm run dev
php artisan serve
```

## 4. Što seed automatski postavlja

Početni seed postavlja ključne početne podatke, između ostalog:

1. stilove luka (`stilovis`)
2. kategorije (`kategorijes`)
3. tipove turnira i polja za unos rezultata (`tipovi_turniras`, `polja_za_tipove_turniras`)
4. početne teme i aktivnu default temu
5. bootstrap administratorski račun

Bootstrap admin podaci:

1. e-mail: `administrator@archery.local`
2. lozinka: `poklonOdSKDubrava`

## 5. Prvi ulaz i obavezni handover administratora

Nakon seeda početna stranica je funkcionalna, ali sustav je još uvijek na privremenom bootstrap administratoru.

Na slici ispod prikazano je: Početna nakon instalacije.
![Početna nakon instalacije](images/setup/01-home-after-install.png)

Prvo registriraj stvarnog korisnika kluba (osoba koja će trajno biti administrator):

Na slici ispod prikazano je: Registracija.
![Registracija](images/setup/02-register-form.png)
Na slici ispod prikazano je: Nakon registracije.
![Nakon registracije](images/setup/03-after-registration.png)

Zatim se prijavi bootstrap admin računom i otvori `Admin -> Korisnici`:

Na slici ispod prikazano je: Bootstrap admin - korisnici.
![Bootstrap admin - korisnici](images/setup/04-bootstrap-admin-users.png)

Uredi stvarno registriranog korisnika i postavi mu rolu `1 - Admin`, pa spremi:

Na slici ispod prikazano je: Promocija korisnika u admina.
![Promocija korisnika u admina](images/setup/05-edit-user-promote-admin.png)

Nakon spremanja sustav automatski:

1. odjavljuje bootstrap admin sesiju,
2. briše bootstrap korisnika,
3. traži prijavu novog administratora.

Na slici ispod prikazano je: Bootstrap korisnik uklonjen.
![Bootstrap korisnik uklonjen](images/setup/06-bootstrap-removed-login.png)
Na slici ispod prikazano je: Novi admin prijavljen.
![Novi admin prijavljen](images/setup/07-new-admin-logged-in.png)

## 6. Produkcija (preporučeni oblik instalacije)

Za produkcijski deploy koristi:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --seed --force
npm ci
npm run build
```

Ako deploy pipeline odvojeno rješava migracije/seed, prilagodi naredbe prema procesu tima.

## 7. Uvoz nadolazećih turnira (archery.hr)

Za uvoz kalendara koristi artisan komandu:

```bash
php artisan turniri:import-archery
```

Česti primjeri:

```bash
# pregled bez upisa
php artisan turniri:import-archery --year=2026 --dry-run

# uvoz nadolazećih turnira za godinu
php artisan turniri:import-archery --year=2026

# samo novi zapisi
php artisan turniri:import-archery --year=2026 --skip-existing

# uključi i prošle turnire
php artisan turniri:import-archery --year=2026 --include-past
```

## 8. Kratka provjera nakon instalacije

Instalacija se smatra uspješnom kada su ispunjena sva četiri uvjeta:

1. aplikacija se otvara bez greške,
2. stvarni korisnik je promoviran u admina,
3. bootstrap admin više ne postoji,
4. `Admin` izbornik je vidljiv novom administratoru.
