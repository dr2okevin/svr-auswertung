# SVR Auswertung
Ziel dieses Tools ist es eine Auswertung von Wettkämpfen des Schützenverein Rellingen mit anschließender Generierung von Ergebnislisten und Urkunden.

## Verschiedene Wettkämpfe
Es sollen beliebige Wettkämpfe abgebildet werden können. Angenommen wird das ein Wettkampf im Voraus angelegt und konfiguriert wird.
Eine Herausforderung ist hierbei der mitunter sehr unterschiedliche Ablauf der Wettkämpfe.
Nachfolgend ein paar Beispiele, diese sind allerdings nicht abschließend.

### Vereinsmeisterschaft
Eine eher einfache Variante. Jede Person schießt eine beliebige Anzahl Disziplinen. Eine Auswertung erfolgt nur innerhalb einer Disziplin. Es werden keine Teams gebildet. Die Disziplinen sind dabei nach der [NDSB Sportordnung](https://www.ndsb-sh.de/download_ndsb_sportordnung.pdf) abschließend definiert welche auch das [Alter der Schützen berücksichtigt](https://www.ndsb-sh.de/download_/sport2025/klasseneinteilung_sportjahr_2025.pdf). Alle Personen gehören zum selben Verein.

### Pokalschießen
Ähnlich wie vereinsmeisterschaft, es nehmen aber auch andere Vereine teil, und es können auch Teams gebildet werden.
Beispiel einer Ergebnisliste https://www.sv-rellingen.de/wp-content/uploads/2025/09/Pokalschiessen-2025-ergebnisse.pdf

### Feuerwehrschießen
Beim Feuerwehrschießen stellen Feuerwehren eine beliebige Anzahl von Mannschaften. Eine Mannschaft besteht aus genau 4 Personen. Eine Einteilung in Alters oder geschlechter Klassen erfolgt nicht.
Jeder schütze in dem Team, muss 50 m KK schießen. Eine Person muss zusätzlich 100 m KK schießen. Eine Person muss zusätzlich 25 m SpoPi schießen. Eine Person muss zusätzlich 10m Luftgewehr schießen. Eine Person muss zusätzlich Bogen schießen.
In allen Disziplinen wird jeweils 10 Schuss geschossen. Bei Bogen und Pistole werden volle Ringe gezählt, bei den anderen Disziplinen erfolgt eine Zehntelwertung.
Für die Mannschaftswertung werden alle Ergebnisse aller Disziplinen zusammen gezählt. Bei den Einzelwertungen wird nach Disziplin unterschiedenen. 
Es kann eine getrennte Wertung für Teams mit und ohne Profischützen geben.
Es kann vorkommen das eine Person in mehreren Teams schießt.

## Anbindung DISAG
Der SVR nutzt aktuell für 50 m KK und 10 m Luft jeweils ein DISAG OptiScore system welches alle Schüsse digital erfasst. Das Programm ermöglicht einen Export als xls. Dort enthalten sind alle Schüße einer Serie mit ihrem Wert und der exakten Position.

## Wertung der Ringe
Es gibt die Wertung in ganzen Ringen, in Zehntel wertung und in Teiler wertung. Die Wertung in ganzen Ringen ist einfach die Addition der geschossenen Ringe, also z.B. eine 9 oder eine 10. Diese ganzen Ringe werden dann aufaddiert. Um das Ergebnis stärker zu differenzieren gibt es die Zehntelwertung. Jeder Ring wird dabei in 10 Einheiten aufgeteilt, so dass eine 10 sowohl eine knappe 10, also 10,0  ,als auch eine sehr gute 10, also eine 10,9 sein kann. Diese werden am Ende des Wettkampfes ebenfalls addiert.
Bei der Teiler wertung wird die Entfernung des Schusses zum Scheibenmittelpunkt gemessen. Dabei reicht z.B. eine 10,9 vom Teiler 0, also absolut zentral, bis zum Teiler 25, also außerhalb des Scheibenzentrums.

### Ergebnisgleichheit
Es kann vorkommen das Schützen oder Teams dasselbe Ergebnis schießen. Zur lösung der Rangfolge gibt es dann folgende Möglichkeiten.
#### Wettbewerbe mit voller Ringwertung
1. das höchste Ergebnis der Zehner-serien zurück vergleichend, bis ein Unterschied besteht;
2. durch die Höchstzahl der 10er, 9er, 8er, usw …
3. durch die Höchstzahl der Innenzehner;
4. durch das höchste Gesamtergebnis mit Zehntelwertung.
5. Ist dann noch Gleichheit vorhanden werden die Sportler auf den gleichen Rang gesetzt.

#### Wettbewerbe mit Zehntelwertung
Bei Ringgleichheit werden folgende Sortierkriterien angewandt:
1. Gesamtsumme in Zehntelwertung;
2. das höchste Ergebnis der letzten Zehnerserien in Zehntelwertung,
   zurückvergleichend bis ein Unterschied besteht;
3. ist dann noch Gleichheit vorhanden, werden die Sportler auf den gleichen Platz gesetzt.

#### Erreichung des Höchstergebnisses voller Ringwertung
Die Reihung für diese Schützen wird durch Stechen entschieden. Stechschüsse werden wiederholt bis keine gleichheit mehr besteht.

#### Profi vs Amateur
Besonderheit beim Feuerwehr und Betriebe Schießen. Hier schießen hauptsächlich Personen ohne Schießerfahrung, oft haben diese im Wettkampf erstmals eine Waffe in der Hand. Vereinzelt finden sich darunter aber auch Sportschützen, Jäger oder andere mit Schießerfahrung welche dann als Profis definiert sind.
Für den Fall das diese nicht getrennt gewertet werden, erfolgt bei Ergebnisgleichheit die Platzierung zu gunsten des Amateurs.

## Datenbank
Als Datenbank soll MySQL oder MariaDB zum einsatz kommen. Ein Konzept für Tabellen liegt in der datei [database.dbml](database.dbml)

## Export
Es soll einen Export für jeden Wettkampf geben. Dabei soll es verschiedene Formate geben.
 - Maschinenlesbar zum import in einer anderen instanz.
 - PDF zum Drucken oder online verteilen.

## Nutzer
Für die erste Phase gibt es nur Admin user welche alle Daten eingeben und später die Auswertung exportieren. Später könnten noch einfache Nutzer dazu kommen, welche einem Schützen zugeordnet werden, welcher damit seine ergebnisse gesammelt einsehen kann.

## Framework
Als Framework kommt Symfony 7.3 zum einsatz. Abhängigkeiten werden via Composer verwaltet. Templates sind in twig.
