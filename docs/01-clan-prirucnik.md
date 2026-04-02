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
![01. Naslovnica](<screenshots/member-role/01. naslovnica.png>)
Na slici ispod prikazano je: Registracija.
![02. Registracija](<screenshots/member-role/02. registracija.png>)
Na slici ispod prikazano je: Podaci za registraciju.
![03. Podaci za registraciju](<screenshots/member-role/03. podaci za registraciju.png>)
Na slici ispod prikazano je: Prijava na site.
![02a. Prijava na site](<screenshots/member-role/02a. prijava na site.png>)

## 2. Kako odmah prepoznati je li račun pravilno povezan

Nakon prve prijave najvažniji signal je izbornik `Korisnik`.

Kada je račun pravilno povezan s članom, uobičajeno se prikazuju stavke poput `Profil`, `Prijave na turnire` i `Odjava`. Ako je račun registriran, ali još nije povezan na člana, korisnik je prijavljen, no u izborniku najčešće ostaje samo `Odjava`.

To nije kvar, nego znak da je potreban kontakt s administratorom koji u administraciji korisnika postavlja odgovarajuću rolu i povezani profil člana.

Na slici ispod prikazano je: Provjera statusa.
![04. Provjera statusa](<screenshots/member-role/04. provjera statusa.png>)
Na slici ispod prikazano je: Admin povezuje člana sa korisnikom.
![05. Admin povezuje člana sa korisnikom](<screenshots/member-role/05. admin povezuje člana sa korisnikom.png>)
Na slici ispod prikazano je: Prijavljeni kao član.
![06. Prijavljeni kao član](<screenshots/member-role/06. prijavljeni kao član.png>)

## 3. Profil člana i dokumentacija

Profil člana je središnje mjesto za osobne podatke, sportske informacije i dokumentaciju. U sekciji `Pregled dokumenata` nalaze se podaci o dokumentima člana i liječničkim pregledima.

Važna stvar za razumjeti: član vidi svoje dokumente. U profilima gdje korisnik nema pravo na dokumente ta sekcija se u pravilu uopće ne prikazuje. Dakle, korisnik bez prava najčešće ne dobiva poruku o zabrani, nego taj dio sučelja jednostavno ne vidi.

Na slici ispod prikazano je: Profil korisnika.
![07. Profil korisnika](<screenshots/member-role/07. profil korisnika.png>)
Na slici ispod prikazano je: Dokumenti i podaci.
![08. Dokumenti i podaci](<screenshots/member-role/08. dokumenti i podaci.png>)

## 4. Treninzi člana

Modul `Moji treninzi` služi za osobnu evidenciju dvoranskih i vanjskih treninga. Član unosi rezultate po serijama, kasnije ih pregledava i po potrebi uređuje ili briše.

Sustav treninge veže na prijavljenog korisnika i njegov profil člana, pa unosi ostaju odvojeni po korisnicima i ne miješaju se između članova.

Na slici ispod prikazano je: Pregled treninga.
![09. Pregled treninga](<screenshots/member-role/09. pregled treninga.png>)
Na slici ispod prikazano je: Unos treninga.
![10. Unos treninga](<screenshots/member-role/10. unos treninga.png>)
Na slici ispod prikazano je: Pregled i uređivanje treninga.
![11. Pregled i uređivanje treninga](<screenshots/member-role/11. pregled i uređivanje treninga.png>)

## 5. Popis članova i profil rezultata

Kroz izbornik `O nama` -> `Članovi` otvara se popis aktivnih članova. Tu se može otvoriti profil pojedinog člana i pregledati rezultate, rekorde i nastupe na turnirima.

Na slici ispod prikazano je: Popis članova.
![12. Popis članova](<screenshots/member-role/12. popis članova.png>)
Na slici ispod prikazano je: Profil člana.
![13. Profil člana](<screenshots/member-role/13. profil člana.png>)

## 6. Prijave na turnire

Prijava na turnire je jednostavna forma, ali sustav u pozadini radi nekoliko važnih provjera. Prikazuju se turniri u dostupnom razdoblju (u pravilu sljedećih 60 dana), ne dopušta se dvostruka aktivna prijava istog člana na isti turnir, a prijave na zaključane turnire nisu dopuštene.

Kategorija se može promijeniti. Sustav samo predlaže početnu kategoriju prema dobi i spolu člana, ali korisnik može odabrati drugu dostupnu kategoriju iz ponuđenog popisa.

Na slici ispod prikazano je: Prijava na turnire.
![14. Prijava na turnire](<screenshots/member-role/14. prijava na turnire.png>)
Na slici ispod prikazano je: Prijava na odabrani turnir.
![15. Prijava na odabrani turnir](<screenshots/member-role/15. prijava na odabrani turnir.png>)
Na slici ispod prikazano je: Pregled prijave.
![16. Pregled prijave](<screenshots/member-role/16. pregled prijave.png>)
Na slici ispod prikazano je: Popis prijava na turnire.
![17. Popis prijava na turnire](<screenshots/member-role/17. popis prijava na turnire.png>)

## 7. Kotizacije i plaćanja

Kod turnira postoje dvije varijante naplate kotizacije:

1. gotovina
2. plaćanje preko računa kluba

Ako je za turnir postavljeno plaćanje preko računa kluba, u prijavi i popisu prijava pojavljuje se poveznica na `Plaćanja člana`, gdje se vidi stanje, odabire stavka duga i prikazuju podaci za uplatu s barkodom.

Kod članarine (sezonske ili godišnje) član može imati više ponuđenih varijanti plaćanja. O odabranoj varijanti ovisi status člana u pojedinoj sezoni. Ako je odabrana podupiruća varijanta za dvoransku i/ili vanjsku sezonu, osoba i dalje ostaje član kluba, ali u toj sezoni nema pravo korištenja dvorane i/ili terena.

**Važno: potvrde plaćanja radi administrator nakon uvida u bankovni izvod. Izvod se radi svakih 7 do 10 dana, pa ako je nešto plaćeno, a još nije evidentirano, to najčešće znači da administrator još nije napravio sljedeći uvid i označio da je uplata podmirena.**

Kada uplata bude evidentirana, status prelazi na `Plaćeno`.

Na slici ispod prikazano je: Prijava na turnir koji se plaća preko računa.
![18. Prijava na turnir koji se plaća preko računa](<screenshots/member-role/18. prijava na turnir koji se plaća preko računa.png>)
Na slici ispod prikazano je: Pregled prijave.
![19. Pregled prijave](<screenshots/member-role/19. pregled prijave_.png>)
Na slici ispod prikazano je: Popis prijava - link na plaćanje.
![20. Popis prijava - link na plaćanje](<screenshots/member-role/20. popis prijava - link na plaćanje.png>)
Na slici ispod prikazano je: Plaćanja.
![21. Plaćanja](<screenshots/member-role/21. plaćanja.png>)

Na naslovnici se kod otvorenog duga prikazuju upozorenja tipa `Potrebna uplata`. To nije greška, nego podsjetnik da treba otvoriti modul plaćanja i podmiriti stavku.

Na slici ispod prikazano je: Naslovnica - obavijesti članu.
![22. Naslovnica - obavijesti članu](<screenshots/member-role/22. naslovnica - obavijesti članu.png>)
Na slici ispod prikazano je: Naslovnica - članarina.
![23. Naslovnica - članarina](<screenshots/member-role/23. naslovnica - članarina.png>)
Na slici ispod prikazano je: Pregled plaćanja - članarina.
![24. Pregled plaćanja - članarina](<screenshots/member-role/24. pregled plaćanja - članarina.png>)
Na slici ispod prikazano je: Upute za plaćanje ako ne želite plaćati barkodom.
![25. Upute za plaćanje ako ne želite plaćati barkodom](<screenshots/member-role/25. upute za plaćanje ako ne želite plaćati barkodom.png>)
Na slici ispod prikazano je: Sva plaćanja su podmirena.
![26. Sva plaćanja su podmirena](<screenshots/member-role/26. sva plaćanja su podmirena.png>)

## 8. Oglasnik

`Oglasnik` služi za objavu, razmjenu i prodaju opreme. Član može izraditi oglas, dodati slike, kasnije ga uređivati, privremeno deaktivirati ili trajno obrisati.

Na slici ispod prikazano je: Oglasnik.
![27. Oglasnik](<screenshots/member-role/27. oglasnik.png>)
Na slici ispod prikazano je: Kreiranje oglasa.
![28. Kreiranje oglasa](<screenshots/member-role/28. kreiranje oglasa.png>)
Na slici ispod prikazano je: Radnje sa oglasom.
![29. Radnje sa oglasom](<screenshots/member-role/29. radnje sa oglasom.png>)
Na slici ispod prikazano je: Vaš oglas.
![30. Vaš oglas](<screenshots/member-role/30. vaš oglas.png>)

## 9. Klupski zid

Svi članovi imaju pravo pisati poruke na `Klupski zid`. To je mjesto za kratke klupske obavijesti, dogovore i informacije koje želite podijeliti sa zajednicom.

Važno je znati da su poruke s Klupskog zida vidljive svim posjetiteljima stranice, uključujući i one koji nisu prijavljeni. Zato na zid nemojte upisivati osjetljive osobne podatke (npr. OIB, adrese, brojeve dokumenata ili medicinske podatke), nego samo sadržaj koji smije biti javan.

## Završna napomena za člana

Ako nakon registracije ne vidite `Profil` i `Prijave na turnire`, najčešći uzrok je da račun još nije povezan s profilom člana. U toj situaciji treba kontaktirati administratora i poslati točne podatke korištene pri registraciji (ime i prezime, OIB, e-mail, broj telefona).

Kada je povezivanje dovršeno, članski prikaz i mogućnosti postaju dostupni odmah nakon sljedeće prijave.

