# Home OS - Agent Context

Ten plik jest szybkim kontekstem dla Codex/Claude/innych agentow pracujacych w tym repozytorium. Ma ograniczyc ponowna analize projektu i przypomniec najwazniejsze decyzje produktowe, techniczne oraz lokalne komendy.

Kanoniczna roadmapa produktu i prac technicznych jest w `docs/development-roadmap.md`. Aktualizuj ja po istotnych sesjach developerskich, gdy zmienia sie zakres, priorytety albo status milestone.

## Projekt

Home OS to prywatna aplikacja do zarzadzania domem i zyciem rodziny:

- finanse domowe w PLN,
- zdrowie, wyniki badan i trendy markerow,
- dokumenty i umowy,
- przyszla integracja ze smart home przez Home Assistant,
- dzienny dashboard pokazujacy rzeczy wymagajace uwagi.

Aplikacja jest tworzona najpierw lokalnie dla Damiana i rodziny. W przyszlosci moze dostac aplikacje mobilna, dlatego API powinno pozostac czytelne i stabilne.

## Stack

- Backend: PHP 8.5, Symfony 8.1, PostgreSQL.
- Frontend: React, TypeScript, Vite.
- Dev: Docker Compose.
- Waluta: tylko PLN.
- Routing frontend: obecnie hash pages, bez React Router.
- Architektura backendu: DDD + CQRS, dzielona po domenach.

## Lokalny Start

Najczesciej uzywane adresy:

- Frontend: `http://localhost:5173`
- Backend host: `http://localhost:8080`
- Backend w kontenerze: `http://127.0.0.1:8000`
- Mailpit: `http://localhost:8025`

Dev login:

- Email: `damian@example.test`
- Haslo: `password123`

Lokalne seed/test IDs, przydatne do szybkiego sprawdzania API:

- Household: `fb61a6fa-143f-4da9-a304-e2aa1bd542c6`
- Damian: `0c01f945-1e8f-4d3d-b537-cdb5831d176f`
- Klaudia: `144aaf0d-274d-49bd-81fe-cb9faf1ebd1c`

## Komendy

Start:

```sh
make start
```

Stan kontenerow:

```sh
docker compose ps
```

Migracje:

```sh
docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
```

Backend checks:

```sh
docker compose exec -T backend php bin/console lint:container
docker compose exec -T backend php bin/console doctrine:schema:validate
```

Frontend build:

```sh
docker compose run --rm --no-deps frontend npm run build
```

## Modul: Dashboard

Dashboard jest "Attention Center", nie statyczna strona demo.

Backend:

- `GET /api/dashboard`
- Dane musza byc household-aware i wymagac zalogowanego uzytkownika.
- Dashboard agreguje sygnaly z finansow i zdrowia.

Frontend:

- summary cards powinny uzywac realnych danych,
- attention items sa grupowane po severity: `critical`, `warning`, `info`,
- akcje powinny prowadzic do istniejacych sekcji, np. `#expenses` albo `#health`.

## Modul: Expenses

Expenses to obecnie najwiekszy modul. Ma sluzyc do miesiecznej kontroli pieniedzy, a nie tylko listy wydatkow.

Glowne funkcje:

- expenses z soft delete,
- recurring bills z platnosciami miesiecznymi,
- income sources i income entries,
- category budgets per miesiac, bez rollover,
- overview API z filtrami: month, category, paidBy,
- import review dla danych bankowych,
- finance review rules, np. `OBI -> Other`,
- finance review batches i undo ostatniej operacji bulk,
- Monthly Review jako osobna zakladka.

Frontend Expenses ma wewnetrzne zakladki:

- `Overview`
- `Monthly Review`
- `Analytics`
- `Transactions`
- `Import Review`
- `Budgets`
- `Bills`

Wazne UX:

- dodawanie danych powinno byc zwijane, nie zawsze widoczne,
- import, wykresy i prezentacja danych powinny byc oddzielone,
- tabele i formularze maja wygladac jak czesc aplikacji, nie natywne HTML bez stylu,
- unikac przechowywania wszystkiego na jednej dlugiej stronie.

Kategorie finansowe obecnie obejmuja m.in.:

- Bills
- Groceries/Home
- Health
- Mortgage
- Other
- Phone/Internet
- Transport

Uwaga: `OBI` powinno byc klasyfikowane jako `Other`, nie `Groceries/Home`.

## Modul: Health

Health ma byc prosty w utrzymaniu i przydatny zyciowo, szczegolnie dla historii badan.

Glowne funkcje:

- profile domownikow,
- wyniki badan krwi,
- import dokumentow/skanow/PDF/obrazow,
- ekstrakcja markerow do review,
- trendy markerow na osi czasu,
- status markerow: normal / low / high / unknown,
- marker catalog i ulubione/tracked markery.

Wazne UX:

- uzytkownik powinien latwo zobaczyc co jest poza norma,
- wyniki z przeszlosci musza miec date badania/importu,
- import powinien prowadzic do spokojnego review, a nie surowych danych technicznych.

## Modul: Documents

Na razie bardziej kierunek niz pelny modul. Docelowo:

- umowy,
- faktury,
- dokumenty domowe,
- powiazanie dokumentow z finansami, zdrowiem i domem.

Nie budowac duzego modulu dokumentow bez potwierdzenia zakresu.

## Modul: Home

Na razie placeholder. Docelowo:

- zadania domowe,
- przeglady i serwisy,
- sprzet/inwentarz,
- integracja Home Assistant na wlasnym serwerze.

Uzytkownik nie ma jeszcze smart home, wiec nie zakladac konkretnych urzadzen.

## Backend Style

- Trzymac DDD/CQRS.
- Preferowac osobne `Command`, `CommandHandler`, `Query`, `QueryHandler`.
- Kontrolery HTTP powinny byc cienkie.
- Dostep do household musi byc chroniony istniejacym mechanizmem access check.
- Nie obchodzic domeny bez potrzeby przez kontrolery.
- Soft delete oznacza `deleted_at`, a overview/listy zwykle musza pomijac usuniete rekordy.
- API ma byc gotowe pod przyszla aplikacje mobilna.

## Frontend Style

- Nowoczesny, kolorowy, ale praktyczny wyglad.
- To jest aplikacja operacyjna, nie landing page.
- Layout ma byc czytelny, z gestymi informacjami, ale bez chaosu.
- Uzywac istniejacych klas i wzorcow w `frontend/src/App.tsx` oraz `frontend/src/App.css`, dopoki komponenty nie zostana wydzielone.
- Przy wiekszych zmianach UX sprawdzic widok w browserze na `http://localhost:5173`.
- Nie dodawac nowych UI bibliotek bez wyraznej potrzeby.

## Dane Lokalnej Bazy

Baza lokalna zawiera importowane dane finansowe Damiana z pliku bankowego. To sa dane lokalne, nie commitowac dumpow ani eksportow bez prosby.

Nie kasowac lokalnych danych, chyba ze uzytkownik wyraznie o to poprosi.

## Pliki i Zmiany

- Nie ruszac `tmp/` bez prosby.
- Nie ruszac `tools/build_damian_cv.py` bez prosby.
- Repo moze miec niepowiazane lokalne zmiany. Nie revertowac cudzych zmian.
- Do recznych edycji uzywac `apply_patch`.
- Przy szukaniu uzywac najpierw `rg`.

## Git

- Remote: `git@github.com:damian-13/Home-OS.git`
- Glowna galaz: `main`
- Standard commit message: krotki opis po angielsku, np. `Add monthly expense review flow`.

Po wiekszej zakonczonej zmianie zwykle:

```sh
git status --short
git add <files>
git commit -m "<message>"
git push
```

## Najblizsze Naturalne Kierunki

Mozliwe kolejne kroki, jesli uzytkownik pyta "co dalej":

- sprawdzic `docs/development-roadmap.md` i wybrac najwyzszy element z backlogu,
- uporzadkowac `frontend/src/App.tsx` przez wydzielenie modulow/komponentow,
- dopracowac Health Review Center,
- dodac UI importu finansow zamiast manualnego importu pliku,
- automatycznie stosowac zapisane finance review rules przy imporcie,
- rozbudowac Documents jako realny modul,
- zaczac Home Maintenance przed integracja Home Assistant,
- dodac testy API dla najwazniejszych przeplywow Expenses i Dashboard.

## Zasada Produktowa

Najlepsze funkcje w Home OS powinny odpowiadac na pytania:

- "Czy w tym miesiacu jestesmy finansowo OK?"
- "Co wymaga mojej uwagi dzisiaj?"
- "Co powinienem zrobic jako nastepny krok?"
- "Co moge przejrzec w jednym Inboxie zamiast szukac po modulach?"
- "Czy moje zdrowie ma trend, ktorego nie powinienem przegapic?"
- "Gdzie jest dokument, ktorego potrzebuje?"
- "Co w domu trzeba zrobic zanim stanie sie problemem?"

Dashboard powinien ewoluowac w Decision Center: akcje i decyzje przed pasywnymi listami danych. Inbox powinien agregowac rzeczy do przejrzenia z importow, OCR, AI sugestii i modulow domenowych.

Jesli funkcja nie pomaga w jednym z tych pytan, najpierw zaproponowac mniejszy MVP.
