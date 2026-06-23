# Auctions API

Laravel 12 API backend za aukcijsku aplikaciju. Projekat pokriva registraciju i prijavu korisnika, role korisnika, kategorije, aukcije, bidove, automatsko zavrsavanje isteklih aukcija, CSV eksport aukcija i Swagger dokumentaciju.

## Tehnologije

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- MySQL
- Pest/PHPUnit testovi
- darkaonline/l5-swagger

## Povlacenje projekta

Kloniraj repozitorijum i udji u folder projekta:

```bash
git clone <repository-url>
cd auctions
```

Instaliraj PHP zavisnosti:

```bash
composer install
```

Ako zelis da koristis Vite/Laravel frontend alatke koje dolaze uz Laravel skeleton:

```bash
npm install
```

## Podesavanje lokalnog okruzenja

Kopiraj `.env.example` u `.env`:

```bash
cp .env.example .env
```

Na Windows PowerShell-u mozes koristiti:

```powershell
Copy-Item .env.example .env
```

Generisi aplikacioni kljuc:

```bash
php artisan key:generate
```

U `.env` podesi konekciju ka lokalnoj MySQL bazi. Podrazumevano je:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auctions
DB_USERNAME=root
DB_PASSWORD=
```

Pre migracija napravi bazu `auctions` u MySQL-u.

## Migracije i seed podaci

Pokreni migracije:

```bash
php artisan migrate
```

Popuni bazu pocetnim podacima:

```bash
php artisan db:seed
```

Ili sve odjednom za svezu lokalnu bazu:

```bash
php artisan migrate:fresh --seed
```

Seeder kreira admin korisnika i nekoliko seller naloga. Svi rucno definisani seed korisnici imaju lozinku:

```text
password
```

Primeri naloga:

```text
admin@auctions.test
seller.electronics@auctions.test
seller.vehicles@auctions.test
seller.collectibles@auctions.test
```

## Pokretanje aplikacije

Pokreni Laravel server:

```bash
php artisan serve
```

Aplikacija ce biti dostupna na:

```text
http://127.0.0.1:8000
```

API rute su pod `/api`, na primer:

```text
http://127.0.0.1:8000/api/auctions
```

## Swagger dokumentacija

Projekat koristi `darkaonline/l5-swagger`.

Generisi OpenAPI dokumentaciju:

```bash
php artisan l5-swagger:generate
```

Swagger UI se otvara na:

```text
http://127.0.0.1:8000/api/documentation
```

Raw OpenAPI JSON je dostupan na:

```text
http://127.0.0.1:8000/docs
```

Za autorizovane rute prvo pozovi `/api/login` ili `/api/register`, kopiraj `access_token`, pa u Swagger UI klikni `Authorize` i unesi token u formatu:

```text
Bearer <token>
```

## Scheduler i zavrsavanje aukcija

Postoji komanda koja zavrsava istekle aktivne aukcije:

```bash
php artisan auctions:finish-ended
```

Komanda:

- pronalazi aktivne aukcije kojima je prosao `ends_at`
- postavlja status na `finished`
- postavlja pobednika na korisnika sa najvecim bidom
- ostavlja `winner_id` praznim ako aukcija nema bidove

Scheduler je podesen da komandu pokrece svake minute. Lokalno ga mozes pokrenuti sa:

```bash
php artisan schedule:work
```

U produkciji treba podesiti sistemski cron da pokrece Laravel scheduler.

## Testovi

Pokretanje svih testova:

```bash
php artisan test
```

## Glavne funkcionalnosti

### Autentifikacija

- Registracija korisnika
- Login korisnika
- Logout korisnika
- Sanctum Bearer token autentifikacija
- Role korisnika: `admin`, `seller`, `buyer`

Registracija dozvoljava samo role `buyer` i `seller`. Admin korisnik se kreira kroz seeder.

### Kategorije

- Javni pregled svih kategorija
- Javni pregled jedne kategorije
- Pregled aukcija po kategoriji
- Kreiranje, azuriranje i brisanje kategorija samo za admin korisnika

Kategorija ima naziv i opis.

### Aukcije

- Javni pregled aukcija
- Javni pregled jedne aukcije
- Pretraga aukcija
- Filteri po statusu, kategoriji, selleru, winneru, ceni i datumima
- Sortiranje i paginacija
- Kreiranje aukcije samo za `seller` korisnika
- Azuriranje i brisanje aukcije za vlasnika aukcije ili admina
- Pravila za azuriranje i brisanje zavise od statusa aukcije

Statusi aukcije:

```text
draft
active
finished
cancelled
```

`current_price` se ne postavlja rucno pri kreiranju ili azuriranju aukcije. To polje se menja kroz bidove.

### Bidovi

- Bid moze postaviti samo `buyer`
- Bid se postavlja nad aktivnom aukcijom koja je pocela i nije zavrsena
- Iznos mora biti veci od trenutne cene ili pocetne cene ako trenutna cena ne postoji
- Ako buyer vec ima bid za istu aukciju, novi zahtev azurira postojeci bid
- Brisanje bidova nije predvidjeno

Pregled bidova:

- `buyer` vidi svoj bid za konkretnu aukciju
- `seller` vidi sve bidove samo za svoju aukciju
- `admin` vidi bidove za svaku aukciju

### Eksterni katalozi

Ruta `/api/auction-external-catalog` poziva javne API servise i vraca reference proizvoda sa cenama:

- DummyJSON Products
- Fake Store API

Podrzani query parametri:

```text
query
limit
```

### CSV eksport

Ruta:

```text
GET /api/auctions/export
```

Preuzima CSV fajl sa podacima o aukcijama. CSV sadrzi osnovne podatke aukcije, kategoriju, sellera, winnera, cene, status, datume i broj bidova.

## Korisne komande

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan l5-swagger:generate
php artisan serve
php artisan schedule:work
php artisan test
```
