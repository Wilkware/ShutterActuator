# Rollladenaktor (Shutter Actuator)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-4.1.20250529-orange.svg?style=flat-square)](https://github.com/Wilkware/ShutterActuator)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/ShutterActuator/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/ShutterActuator/actions)

Das Modul dient zur Ansteuerung der korrekten Öffnungsposition in Abhängigkeit der Motor-laufzeit. Dabei wird die nicht lineare Laufzeit des Motors zur Position der Lamellen übersetzt.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [Visualisierung](#user-content-6-visualisierung)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

* Übersetzung der Laufzeit des Rollladenmotors zur Position der Lamellen.

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Software-Installation

* Über den Modul Store das Modul _Shutter Actuator_ installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/ShutterActuator` oder `git://github.com/Wilkware/ShutterActuator.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Rollladenaktor'_-Modul unter dem Hersteller _'(Geräte)'_ aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Erläuterng ...

Kurze Erläterung der Funktionsweise der Ansteuerung.

> Geräte ...

Name           | Beschreibung
---------------| ---------------------------------
Empfänger      | Positions-Variable des Rollladen-Steuergerätes (Kanal 3:LEVEL)
Sender         | Schalt-Variable des Rollladen-Steuergerätes (Kanal 4:LEVEL)

> Ansteuerung ...

Name                          | Beschreibung
------------------------------| ---------------------------------
Geöffnet/Oben (0%)            | Levelwert bei geöffneten Rollläden
Viertel (25%)                 | Levelwert bei virtel geschlossenen Rollläden
Mitte (50%)                   | Levelwert bei halb geschlossenen Rollläden
Dreiviertel (75%)             | Levelwert bei dreiviertel geschlossenen Rollläden
Blickdicht (99%)              | Levelwert bei fast geschlossenen Rollläden
Geschlossen/Unten (100%)      | Levelwert bei geschlossenen Rollläden

Die Laufzeit (Level) muss vorher manuell gestoppt und aus der 'Level' Gerätevariable ausgelesen werden (siehe nachfolgenden Aktionsbereich)!

> Erweiterte Einstellungen ...

Name                          | Beschreibung
------------------------------| ---------------------------------
Blockier-Kontakt              | Variable, die beim Ein- bzw. Ausfahren vorher geprüft wird  (Test auf TRUE).
Berücksichtigen beim Ausfahren| Prüfung bzw. Berücksichtigung des Variablewertes nur beim Ausfahren
Berücksichtigen beim Einfahren| Prüfung bzw. Berücksichtigung des Variablewertes nur beim Einfahren

Aktionsbereich:

Aktion         | Beschreibung
-------------- | ------------------------------------------------------------
HOCH           | Startet das Hochfahren des Rollladens
STOP           | Stoppt den Rollladen an aktueller Position
RUNTER         | Startet das Runterfahren des Rollladens
ANZEIGEN       | Zeigt die interne Position (0.0 - 1.0%) des Geätes an

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Position             | Integer   | Öffnungsgrad des Rollladens

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
HM.ShutterActuator   | Integer   | Öffnungsgrad in Prozent(-schritte) (0% = Auf, 25%, 50%, 75%, 99%, 100% = Zu)

### 6. Visualisierung

Die erzeugten Variable kann direkt in die Visualisierung verlinkt werden.

### 7. PHP-Befehlsreferenz

```php
void TSA_Up(int $InstanzID);
```

Fährt den Rollladen ganz hoch.  
Die Funktion liefert keinerlei Rückgabewert.  

```php
void TSA_Down(int $InstanzID);
```

Fährt den Rollladen ganz nach unten.  
Die Funktion liefert keinerlei Rückgabewert.  

```php
void TSA_Stop(int $InstanzID);
```

Hält den Rollladen sofort an.  
Die Funktion liefert keinerlei Rückgabewert.  

```php
float TSA_Level(int $InstanzID);
```

Liefert die aktuelle Position (Level) des Rollladens.  
Die Funktion liefert die prozentualen Level (00 - 1.0) als Rückgabewert zurück. Im Fehlerfall wird -1 zurückgegeben.

```php
void TSA_Position(int $InstanzID, int $Position);
```

Fährt den Rollladen an die übergebene Postion (0-100).  
Die Funktion liefert keinerlei Rückgabewert.  

### 8. Versionshistorie

v4.1.20250529

* _NEU_: Das Modul wurde in Rollladenaktor umbenannt. Die Steuerung gab es bereits von Symcon.
* _NEU_: Blockiermodus (Kontakt) eingebaut.
* _FIX_: Test auf Variablenauswahl vereinheitlicht (nicht mehr nur auf 0).
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation vereinheitlicht

v4.0.20240907

* _NEU_: Kompatibilität auf IPS 6.4 hoch gesetzt
* _NEU_: Referenzieren der Gerätevariablen hinzugefügt
* _NEU_: Farbedefinition aus Profil gelöscht wegen besserer Darstellung in der TileVisu
* _FIX_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _FIX_: Namensnennung und Repo vereinheitlicht
* _FIX_: Update Style-Checks
* _FIX_: Übersetzungen überarbeitet und verbessert
* _FIX_: Dokumentation vereinheitlicht 

v3.0.20221117

* _NEU_: Konfigurationsformular überarbeitet und vereinheitlicht
* _NEU_: Kompatibilität auf 6.0 hoch gesetzt
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Bibliotheksdefinition überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v2.0.20210712

* _NEU_: Konfigurationsformular überarbeitet und vereinheitlicht
* _FIX_: Übersetzungen nachgezogen
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Debug Meldungen überarbeitet
* _FIX_: Dokumentation überarbeitet

v1.2.20200813

* _NEU_: Funktion zum Anfahren einer bestimmeten Position hinzugefügt
* _FIX_: Dokumentation überarbeitet

v1.1.20190818

* _NEU_: Umstellung für Module Store
* _FIX_: Dokumentation überarbeitet

v1.0.20190415

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
