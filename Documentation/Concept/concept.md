# SVR Auswertung
Ziel dieses Tools ist es eine Auswertung von Wettkämpfen des Schützenverein Rellingen mit anschließender Generierung von Ergebnislisten und Urkunden.

## Verschiedene Wettkämpfe
Es sollen beliebige Wettkämpfe abgebildet werden können. Angenommen wird das ein Wettkampf im Voraus angelegt und konfiguriert wird.
Eine Herausforderung ist hierbei der mitunter sehr unterschiedliche Ablauf der Wettkämpfe.
Nachfolgend ein paar Beispiele, diese sind allerdings nicht abschließend.

### Vereinsmeisterschaft
Eine eher einfache Variante. Jede Person schießt eine beliebige Anzahl Disziplinen. Eine Auswertung erfolgt nur innerhalb einer Disziplin. Es werden keine Teams gebildet. Die Disziplinen sind dabei nach der [NDSB Sportordnung](https://www.ndsb-sh.de/download_ndsb_sportordnung.pdf) abschließend definiert welche auch das [Alter der Schützen berücksichtigt](https://www.ndsb-sh.de/download_/sport2025/klasseneinteilung_sportjahr_2025.pdf). Alle Personen gehören zum selben Verein.

### Feuerwehrschießen
Beim Feuerwehrschießen stellen Feuerwehren eine belibige Anzahl von Manschaften. Eine Mannschaft besteht aus genau 4 Personen. Eine Einteiölung in Alters oder geschlechter Klassen erfolgt nicht.
Jeder schütze in dem Team muss 50m KK schießen. Eine Person muss zusätzlich 100m KK schießen. Eine Person muss zusätzlich 25m SpoPi schießen. Eine Person muss zusätzlich 10m Luftgewehr schießen. Eine Person muss zusätzlich Bogen schießen.
In allen Disziplinen wird jeweils 10 Schuss geschossen. Bei Bogen und Pistole werden volle Ringe gezählt, bei den anderen Disziplinen erfolgt eine Zehntelwertung.
Für die Mannschaftswertung werden alle Ergebnisse aller Disziplinen zusammen gezählt. Bei den Einzelwertungen wird nach Disziplin unterschiedenen. 
Es kann eine getrennte Wertung für Teams mit und ohne Profischützen geben.

## Anbindung DISAG
Der SVR nutzt aktuell für 50m KK und 10m Luft jeweils ein DISAG OptiScore system welches alle Schüsse digital erfasst. Das Programm ermöglicht einen Export als xls. Dort enthalten sind alle Schüße einer Serie mit ihrem Wert und der exakten Position.

## Datenbank
Als Datenbank soll MySQL oder MariaDB zum einsatz kommen. Ein Konzept für Tabellen liegt in der datei [database.dbml](database.dbml)
