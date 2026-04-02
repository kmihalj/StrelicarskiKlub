# Polaznik škole streličarstva - detaljne upute

Ovaj vodič opisuje kako sustav izgleda kada je korisnički račun povezan s profilom polaznika škole streličarstva. Naglasak je na tome što polaznik vidi i prati kroz svoj račun, a što administrativno održava klub.

## 1. Registracija i povezivanje

Polaznik se registrira svojim podacima. Kao i kod ostalih korisnika, sustav pri registraciji pokušava automatski povezati račun s postojećim profilom u bazi. Ako podaci odgovaraju, veza se napravi odmah; ako ne, administrator ručno dovršava povezivanje.

![01. Naslovnica i registracija](<screenshots/school-role/01. naslovnica i registracija.png>)
![02. Registracija](<screenshots/school-role/02. registracija.png>)
![03. Provjera registracije](<screenshots/school-role/03. provjera registracije.png>)
![04. Admin Vas povezuje sa profilom](<screenshots/school-role/04. admin Vas povezuje sa profilom.png>)

Nakon uspješnog povezivanja naslovnica počinje prikazivati blok `Moji podaci škole streličarstva`, s osnovnim statusom i brzim ulazom na detaljni profil.

![05. Naslovnica povezanog polatnika](<screenshots/school-role/05. naslovnica povezanog polatnika.png>)

## 2. Profil polaznika

Profil polaznika je glavno mjesto pregleda podataka. U njemu se vidi osobni profil, stanje dolazaka i školarina.

Važno je znati da polaznik u pravilu pregledava vlastite podatke, dok uređivanje ključnih administrativnih stavki (npr. promjene modela školarine i potvrde uplata) radi administrator.

![06. Pregled profila](<screenshots/school-role/06. pregled profila.png>)

## 3. Školarina i status uplata

U sekciji školarine prikazuju se stavke, status (`plaćeno` / `nije plaćeno`) i informativne poruke. Kada je stavka otvorena, na naslovnici i profilu pojavljuje se odgovarajuća obavijest.

Nakon što administrator potvrdi uplatu, status se ažurira i obavijest prelazi u uredno stanje.

![07. Pregled školarine](<screenshots/school-role/07. pregled školarine.png>)
![08. Sve plaćeno](<screenshots/school-role/08. sve plaćeno.png>)
![09. Sve plaćeno u profilu](<screenshots/school-role/09. sve plaćeno u profilu.png>)

## 4. Dolasci i praktična uporaba

Polaznik kroz svoj profil može redovito pratiti evidenciju dolazaka i promjene statusa školarine bez dodatne komunikacije za svaku pojedinu stavku. To olakšava praćenje obveza i daje jasan pregled što je već podmireno, a što još čeka potvrdu.

Ako se podaci ne prikazuju kako očekujete nakon registracije, najčešći uzrok je da korisnički račun još nije povezan s profilom polaznika. U tom slučaju administrator treba dovršiti povezivanje u modulu korisnika.
