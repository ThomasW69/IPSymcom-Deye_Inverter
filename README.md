[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.00-blue.svg)](https://community.symcon.de/t/Module_deye_Hybridwechselrichter)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
[![Version](https://img.shields.io/badge/Symcon%20Version-5.1%20%3E-green.svg)](https://www.symcon.de/service/dokumentation/installation/migrationen/v50-v51-q2-2019/)


# IPSymcon-Modul: Deye PV Hybrid-Wechselrichter <!-- omit in toc -->  
![DeyeBild](./imgs/DeyeBild.png) 

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-hardware-installation)
- [4. Software-Installation](#4-software-installation)
- [5. Einrichten der Instanzen in IP-Symcon](#5-einrichten-der-instanzen-in-ip-symcon)
- [6. Anhang](#6-anhang)
  - [1. GUID der Module](#1-guid-der-module)
  - [2. Changelog](#2-changelog)
- [7. Lizenz](#7-lizenz)

## 1. Funktionsumfang

Ermöglicht die Einbindung von 3-phasigen Hybridwechselrichtern der Firma Ningbo Deye in IPS.  
Es können mehrere Wechselrichter auf einem physikalischen RS485-Bus betrieben werden.  
Aktuell befindet sich das Modul noch in Entwicklung und noch nicht alle geplanten Funktionen sind umgesetzt.
Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.
Der Nutzer stimmt den oben angegebenen Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

Folgende Module beinhaltet die Deye Library (vorerst):

- __SUN xxK_SG04LP3__  
	3-Phasen Wechselrichter vom Typ Deye SUN-xxK-SG04-LP3  


## 2. Voraussetzungen

 - IPS 5.1 oder höher  
 - Unterstützte Wechselrichter  
  Deye SUN-5K-SG04-LP3
  Deye SUN-6K-SG04-LP3
  Deye SUN-8K-SG04-LP3
  Deye SUN-10K-SG04-LP3
  Deye SUN-12K-SG04-LP3
 - physikalisches RS485-Interface bzw. RS485 to Ehternet Konverter  

## 3. Hardware-Installation

Die Wechselrichter von Deye haben eine auf der Unterseite eine serielle Buchse in die ein WiFi-Dongle eingesteckt 
werden kann, welches dann im lokalen WLAN arbeiten kann. Über diese Schnittstelle lässt sich en Firmware-update 
durchführen und wohl auch Daten des Wechselrichters nach China senden. Hier ist jedoch das genaue Protokoll unbekannt.

Das vorliegende Modul wählt den Weg über die MODBUS-Schnittstelle des Deye, welche mit "ModeBUS" gekennzeichnet ist und
sich neben der BMS Buchse befindet.
Ein T568B-Patchkabel ist mit den Pins 6, 7 und 8 auf dem Port des DEYE-Wechselrichters aufgelegt.
PIN6 grün        -> Ground
PIN7 braun/weiss -> RS485 B
PIN8 braun       -> RS485 A

Mit diesem Leitungen könnte man direkt auf einen RS485-USB-Converter am IPS-Rechner gehen. Im IPS-Modul ist hier "Modbus RTU"
einzustellen und der Port zu konfigurieren.

![ModBus Buchse](../SUNxxKSG04LP3/imgs/ModbusConnector.PNG) 

ACHTUNG: Es sind Firmwareversionen bekannt, die keine Kommuniklation über die "ModeBUS"-Buchse mehr zulassen. Hier muss dann das 
entsprechende Signal über die "BMS"-Buchse abgegriffen werden. Hierzu ist ggf. ein Y-Kabel nötig.

Um die Daten über das lokale Netzwerk zu IPSymcon zu übertragen, ist der Einsatz eines RS485-Ethernet oder RS485-WLAN Umsetzers nötig.
diese gibt es inverschiedenen Ausführungen dürften aber alle ähnlich funktionieren. Ich habe die beiden ANchfolgenden in meiner 
IPS-Installation im Einsatz 
![Waveshare](SUNxxKSG04LP3/imgs/RS485toEthernet1.PNG) 
![USR-W630](SUNxxKSG04LP3/imgs/USRW630.jpg) 

## 4. Software-Installation

  Aktuell ist das Modul noch nicht über den Modulstore von IPSymcon ladbar. Sobald die finale Version ausgiebig getestet wurde, wird es jedoch im ModulStore verfügbar gemacht.
  Zur Installation im Objektbaum unter 
  
   **Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  
  

## 4. Einrichten der Instanzen in IP-Symcon

Ist direkt in der Dokumentation der jeweiligen Module beschrieben:  

- __[SUN xxK-SG04LP3](SUNxxKSG04LP3/README.md#4-einrichten-der-instanzen-in-ip-symcon)__

## 5. Anhang

###  1. GUID der Module

 
|     Modul      |  Typ   |    Prefix    |                  GUID                  |
| :------------: | :----: | :----------: | :------------------------------------: |
| SUNxxKSG04LP3| Device |  SUNxxKSG04LP3   | {5A793A9B-ADEE-307E-20B7-FBE05C7B4C7E} |


### 2. Changelog

__Version 0.1:__
 - Erstes inoffizielles Release (Noch in Entwicklung!!!) 


 

## 7. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
 
