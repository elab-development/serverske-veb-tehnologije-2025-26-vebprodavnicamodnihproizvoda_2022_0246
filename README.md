# Prodavnica modnih proizvoda

Serverska veb aplikacija razvijena u Laravel okviru za potrebe predmeta Serverske veb tehnologije.

Aplikacija predstavlja backend sistem za prodaju modnih proizvoda i omogućava rad sa korisnicima, proizvodima, kategorijama, porudžbinama i stavkama porudžbine kroz REST API.

## Funkcionalnosti

Aplikacija omogućava:

- registraciju korisnika
- prijavu korisnika
- odjavu korisnika
- autentifikaciju pomoću Laravel Sanctum tokena
- pregled svih proizvoda
- pregled pojedinačnog proizvoda
- pretragu proizvoda
- filtriranje proizvoda
- sortiranje proizvoda
- paginaciju proizvoda
- prikaz statistike lagera
- kreiranje proizvoda
- izmenu proizvoda
- brisanje proizvoda
- upload slike proizvoda
- kreiranje porudžbine
- pregled porudžbina
- izmenu porudžbine
- brisanje porudžbine
- pregled porudžbina određenog korisnika
- pregled stavki određene porudžbine
- administratorski izveštaj o porudžbinama
- automatsko računanje ukupne cene porudžbine
- automatsko smanjenje količine proizvoda na stanju
- rad sa baznim transakcijama
- integraciju sa eksternim REST servisima

## Tehnologije

U projektu su korišćene sledeće tehnologije:

- PHP
- Laravel
- Laravel Sanctum
- MySQL
- Laravel Eloquent ORM
- Laravel Query Builder
- Docker
- Laravel Sail
- Postman
- Git
- GitHub

## Modeli

Aplikacija koristi sledeće osnovne modele:

- `User`
- `Category`
- `Product`
- `Order`
- `OrderItem`

Modeli su međusobno povezani.

Osnovne relacije su:

- jedan korisnik može imati više porudžbina
- jedna porudžbina pripada jednom korisniku
- jedna porudžbina može imati više stavki
- jedna stavka pripada jednoj porudžbini
- jedna stavka je povezana sa jednim proizvodom
- jedan proizvod pripada jednoj kategoriji
- jedna kategorija može imati više proizvoda

## Korisničke uloge

Sistem razlikuje tri nivoa pristupa.

### Gost

Neautentifikovani korisnik može da koristi javne funkcionalnosti aplikacije, kao što su:

- pregled proizvoda
- pregled pojedinačnog proizvoda
- pretraga proizvoda
- filtriranje proizvoda
- sortiranje proizvoda
- paginacija
- pristup javnim REST servisima

### Autentifikovani korisnik

Prijavljeni korisnik može da:

- kreira porudžbine
- pregleda svoje porudžbine
- menja svoje porudžbine
- briše svoje porudžbine
- pregleda stavke svojih porudžbina

### Administrator

Administrator ima proširena prava i može da:

- kreira proizvode
- menja proizvode
- briše proizvode
- pregleda sve porudžbine
- koristi administratorski izveštaj

## REST API

Aplikacija koristi sopstveni REST API.

### Autentifikacija

POST /api/register
POST /api/login
POST /api/logout

### Proizvodi
GET    /api/products
GET    /api/products/{id}
POST   /api/products
PUT    /api/products/{id}
PATCH  /api/products/{id}
DELETE /api/products/{id}

### Dodatne rute:
GET /api/products/search
GET /api/products/category/{categoryId}
GET /api/products/stats/stock

### Primer pretrage:
GET /api/products/search?term=Puma

### Primer filtriranja:
GET /api/products?brand=Zara

### Primer paginacije:
GET /api/products?page=2

### Primer sortiranja:
GET /api/products?sort_by=price&sort_order=asc

### Porudžbine
GET    /api/orders
POST   /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}
PATCH  /api/orders/{id}
DELETE /api/orders/{id}

### Ugnježdene rute
Aplikacija implementira dve ugnježdene REST rute:
GET /api/users/{id}/orders
GET /api/orders/{id}/items
Prva ruta vraća porudžbine konkretnog korisnika, dok druga vraća stavke određene porudžbine.

### Administratorski izveštaj
Za administratorski pregled porudžbina implementirana je ruta:
GET /api/reports/orders
Ova funkcionalnost koristi višestruki JOIN nad tabelama:
users
orders
order_items
products
categories
Na taj način se u jednom odgovoru dobijaju informacije o korisniku, porudžbini, proizvodu i kategoriji.

### Transakcije
Kreiranje porudžbine realizovano je korišćenjem bazne transakcije.
U okviru jedne transakcije vrši se:
kreiranje porudžbine
kreiranje stavki porudžbine
provera količine proizvoda na stanju
smanjenje količine proizvoda na stanju
računanje ukupne cene porudžbine
Ako tokom izvršavanja dođe do greške, transakcija se poništava i promene se ne čuvaju u bazi.

### Eksterni REST servisi
Aplikacija koristi dva javna REST servisa.

### Konverzija valuta
Ruta:
GET /api/currency/convert
Primer:
GET /api/currency/convert?amount=100&from=EUR&to=USD
Laravel aplikacija poziva javni Frankfurter API i na osnovu dobijenog kursa izračunava konvertovanu vrednost.

### Eksterni modni katalog
Ruta:
GET /api/external-fashion
Ova ruta poziva javni SceneSKU REST servis i preuzima podatke o modnim proizvodima.

### Bezbednost
U aplikaciji su implementirane različite mere bezbednosti.
Lozinke korisnika se ne čuvaju u otvorenom obliku, već se heširaju korišćenjem Laravel mehanizma za zaštitu lozinki.
Za autentifikaciju se koristi Laravel Sanctum.
Zaštićene API rute zahtevaju validan Bearer token.
Administratorske operacije dodatno su zaštićene admin middleware-om.
Kod rada sa porudžbinama proverava se da li je prijavljeni korisnik vlasnik porudžbine ili administrator, čime se sprečava neovlašćen pristup tuđim podacima.
Za rad sa bazom koriste se Eloquent ORM i Query Builder.

### Pokretanje projekta na lokalnoj mašini
1. Kloniranje repozitorijuma
git clone https://github.com/elab-development/serverske-veb-tehnologije-2025-26-vebprodavnicamodnihproizvoda_2022_0246.git
Zatim ući u folder projekta:
cd serverske-veb-tehnologije-2025-26-vebprodavnicamodnihproizvoda_2022_0246
2. Instalacija PHP zavisnosti
composer install
3. Kreiranje .env fajla
cp .env.example .env
4. Generisanje aplikacionog ključa
Ako se koristi lokalni PHP:
php artisan key:generate
Ako se koristi Laravel Sail:
./vendor/bin/sail artisan key:generate
5. Pokretanje Docker kontejnera
./vendor/bin/sail up -d
6. Migracije i početni podaci
Za kreiranje tabela i ubacivanje početnih podataka:
./vendor/bin/sail artisan migrate --seed
Ukoliko je potrebno potpuno resetovanje baze:
./vendor/bin/sail artisan migrate:fresh --seed
7. Storage link
Za javni pristup uploadovanim slikama proizvoda:
./vendor/bin/sail artisan storage:link
8. Pokretanje i testiranje
Aplikacija je nakon pokretanja dostupna na:
http://localhost
API rute koriste prefiks:
http://localhost/api
Za testiranje REST API funkcionalnosti koristi se Postman.

### Autentifikacija u Postmanu
Nakon uspešnog logovanja API vraća pristupni token.
Token se za zaštićene rute šalje kroz HTTP zaglavlje:
Authorization: Bearer TOKEN
U Postmanu je potrebno izabrati:
Authorization -> Bearer Token
i uneti dobijeni token.

### Primer kreiranja porudžbine
Ruta:
POST /api/orders
Primer JSON zahteva:
{
    "delivery_address": "Kralja Milana 25, Beograd",
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ]
}
Identifikator korisnika se određuje na osnovu autentifikovanog korisnika.
Ukupna cena porudžbine se automatski računa na serverskoj strani na osnovu cena i količina proizvoda.
Nakon kreiranja porudžbine količina proizvoda na stanju se automatski smanjuje.

### Primeri HTTP statusnih kodova
Aplikacija koristi odgovarajuće HTTP statusne kodove.
200 OK                  - uspešno izvršen zahtev
201 Created             - uspešno kreiran resurs
401 Unauthorized        - korisnik nije autentifikovan
403 Forbidden           - korisnik nema pravo pristupa
404 Not Found           - traženi resurs ne postoji
422 Unprocessable Entity - greška pri validaciji podataka
500 Internal Server Error - serverska greška
502 Bad Gateway         - greška pri komunikaciji sa eksternim servisom

### Git repozitorijum
Projekat je javno dostupan na GitHub-u:
https://github.com/elab-development/serverske-veb-tehnologije-2025-26-vebprodavnicamodnihproizvoda_2022_0246
Razvoj projekta praćen je pomoću Git sistema za verzionisanje i repozitorijum sadrži više od 20 smislenih commitova.
Svi članovi tima učestvovali su u razvoju projekta i imaju sopstvene commitove.

### Autori
Projekat je razvijen kao timski rad u okviru predmeta Serverske veb tehnologije.
Saska Savic 2022/0246
Jana Simic 2022/0447