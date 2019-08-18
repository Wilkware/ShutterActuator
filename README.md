[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.1.20190818-orange.svg)](https://github.com/Wilkware/IPSymconShutterActuator)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/76893952/shield?style=flat)](https://github.styleci.io/repos/76893952)

# Rollladensteuerung

Modul zur Übersetzung der Laufzeit des Rollladenmotors zur Position der Lamellen.

## Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

* Ansteuerung der korrekten Öffnungsposition in Abhängigkeit der Laufzeit

### 2. Voraussetzungen

* IP-Symcon ab Version 5.0

### 3. Software-Installation

* Über den Modul Store das Modul 'Shutter Actuator' installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconShutterActuator` oder `git://github.com/Wilkware/IPSymconShutterActuator.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das 'Rollladensteuerung'-Modul (Alias: Jalousiesteuerung) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Name                          | Beschreibung
------------------------------| ---------------------------------
Empfänger (3:LEVEL)           | Positions-Variable des Rollladen-Steuergerätes
Sender (4:LEVEL)              | Schalt-Variable des Rollladen-Steuergerätes
Geöffnet/Oben (0%)            | Levelwert bei geöffneten Rollläden
Viertel (25%)                 | Levelwert bei virtel geschlossenen Rollläden
Mitte (50%)                   | Levelwert bei halb geschlossenen Rollläden
Dreiviertel (75%)             | Levelwert bei dreiviertel geschlossenen Rollläden
Blickdicht (99%)              | Levelwert bei fast geschlossenen Rollläden
Geschlossen/Unten (100%)      | Levelwert bei geschlossenen Rollläden

Die Laufzeit (Level) muss vorher manuell gestoppt und aus der 'Level' Gerätevariable ausgelesen werden!

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Position             | Float     | Öffnungsgrad des Rollladens

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
TSA.Position         | Float     | Öffnungsgrad in Prozent(-schritte)

### 6. WebFront

Die erzeugten Variable kann direkt ins Webfront verlinkt werden.

### 7. PHP-Befehlsreferenz

`float TSA_Level(int $InstanzID);`  
Liefert die aktuelle Position (Level) des Rollladens.  
Die Funktion liefert die prozentualen Level (00 - 1.0) als Rückgabewert zurück. Im Fehlerfall wird -1 zurückgegeben.

`void TSA_Up(int $InstanzID);`  
Fährt den Rollladen ganz hoch.  
Die Funktion liefert keinerlei Rückgabewert.  

`void TSA_Down(int $InstanzID);`  
Fährt den Rollladen ganz nach unten.  
Die Funktion liefert keinerlei Rückgabewert.  

`void TSA_Stop(int $InstanzID);`  
Hält den Rollladen sofort an.  
Die Funktion liefert keinerlei Rückgabewert.  

### 8. Versionshistorie

v1.1.20190818

* _NEU_: Umstellung für Module Store
* _FIX_: Dokumentation überarbeitet

v1.0.20190415

* _NEU_: Initialversion

## Entwickler

* Heiko Wilknitz ([@wilkware](https://github.com/wilkware))

## Spenden

Die Software ist für die nicht kommzerielle Nutzung kostenlos, Schenkungen als Unterstützung für den Entwickler bitte hier:  
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166" target="_blank"><img src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_LG.gif" border="0" /></a>

## Lizenz

[![Licence](https://licensebuttons.net/i/l/by-nc-sa/transparent/00/00/00/88x31-e.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
