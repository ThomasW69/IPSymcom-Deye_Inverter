<?php

declare(strict_types=1);

/*
 * @addtogroup deye
 * @{
 *
 * @package       Deye
 * @file          module.php
 * @author        Thomas Westerhoff <thomas.westerhoff24@gmx.de>
 * @copyright     2022 Thomas Westerhoff
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 * @version       0.10
 *  
 */
require_once __DIR__ . '/../libs/DeyeModule.php';  // diverse Klassen

    


/**
 * SUNxxKSG04LP3 ist die Klasse für die SUN-xxK-SG04LP3 Hybridwechselrichter der Firma Deye
 * Erweitert Deye.
 */
class SUNxxKSG04LPx extends Deye
{
    const PREFIX = 'SUNxxKSG04LPx';
    

    public static $Variables = [
	//Name,                     VarType,                ValueType,          Profile,          Address,  Function, Quantity, Factor, Offset, Keep
    ['Inverter_Type',    	    VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    'DeyeType', 		 0,     3, 1,    1,      0,  true],  //Invertertyp 2=Serial, 3=Hybrid 1ph,4= Microinverter, 5=Hybrid 3ph 
    ['Rated_Power', 	        VARIABLETYPE_FLOAT,     VALTYPE_DWORD, 	    'Watt.I', 		    20,     3, 2,  0.1,      0,  true],  // Leistung   
    ['Modbus_ID', 	            VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    '', 		         1,     3, 1,  0.1,      0,  true],  // ModBusID
 //   ['Protocol_Ver', 	        VARIABLETYPE_STRING,    VALTYPE_STRING,	    '', 		         2,     3, 1,  0.1,      0,  true],  //Protokollversion
    ['Serial_Number', 	        VARIABLETYPE_STRING,    VALTYPE_ASTRING,	'', 		         3,     3, 5,  0.1,      0,  true],  //Seriennummer
    ['Health_Status',      	    VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'DeyeStatus', 	   500,     3, 1,    1,      0,  true], // HealtStatus 0=Standby, 1=SelfCheck, 2=Normal, 3=Alarm, 4=Fault
   // ['Day_ActGen_Power',      	VARIABLETYPE_FLOAT,     VALTYPE_SWORD,	    '~Electricity',    501,     3, 1,  0.1,      0,  true], // Gesamterzeugung heute 
    ['Day_Battery_Charge',      VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    514,     3, 1,  0.1,      0,  true], // //Batterieladung heute kWh
    ['Total_Battery_Charge',    VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    516,     3, 2,  0.1,      0,  true], // Batterieladung gesamt [kWh]
    ['Total_Battery_Discharge', VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    518,     3, 2,  0.1,       0,  true], // Batterieentladung total [kWh]
    ['Day_GridBuy_Power',       VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    520,     3, 1, 0.01,      0,  true], // Netzbezug heute [kWh]
    ['Day_GridSell_Power',      VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    521,     3, 1, 0.01,      0,  true], // Einspeisung heute [kWh]
    ['Total_GridBuy_Power',     VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    522,     3, 2,  0.1,      0,  true], // Gesamter Netzbezug [kWh]
    ['Total_GridSell_Power',    VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    524,     3, 2,  0.1,      0,  true], // Gesamte Einspeisung [kWh]
    ['Day_Load_Demand',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    526,     3, 1,  0.1,      0,  true], //  Hausverbrauch  heute [kWh]
    ['Total_Load_Demand',      	VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    527,     3, 2,  0.1,      0,  true], // Gesamtverbrauch [kWh]
    ['Day_PV_Output',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    529,     3, 1,  0.1,      0,  true], //  Solarerzeugung heute [kWh]
    ['Day_PV1_Output',     	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    530,     3, 1,  0.1,      0,  true], //  Solarerzeugung heute String 1 [kWh]
    ['Day_PV2_Output',          VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    531,     3, 1,  0.1,      0,  true], //  Solarerzeugung heute String 2[kWh]
    ['Day_PV3_Output',       	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    532,     3, 1,  0.1,      0,  true], //  Solarerzeugung heute String 3[kWh]
    ['Day_PV4_Output',          VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Electricity',    533,     3, 1,  0.1,      0,  true], //  Solarerzeugung heute String 4[kWh]
    ['Total_PV_Output',      	VARIABLETYPE_FLOAT,     VALTYPE_DWORD,	    '~Electricity',    534,     3, 2,  0.1,      0,  true], // Solarerzeugung Gesamt [kWh] (auslesewert in Wh)
    ['DC_Trans_Temp', 	        VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    'Temperature', 	   540,     3, 1,  0.1,   1000,  true], //Transprmatortemperatur [°C]
    ['Heatsink_Temp', 	        VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    'Temperature', 	   541,     3, 1,  0.1,   1000,  true], //Kühlkörpertemperatur [°C]
//Battery
    ['Battery_Temp',    	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    'Temperature', 	   586,     3, 1,  0.1,   1000,  true], //Batterietemperatur [°C]
    ['Battery_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       587,     3, 1, 0.01,      0,  true], //Battereispannung [V]
    ['Battery_SOC',      	    VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    '~Battery.100',    588,     3, 1,    1,      0,  true], //Batterielevel [%]
    ['Battery_Out_Power',      	VARIABLETYPE_INTEGER,   VALTYPE_SWORD,	    'Watt.I', 	       590,     3, 1,    1,      0,  true], //Batterieausgangsleistung [W]
    ['Battery_Out_Curr',      	VARIABLETYPE_FLOAT,     VALTYPE_SWORD,	    '~Ampere', 	       591,     3, 1, 0.01,      0,  true], //Batterieausgangsstrom [A] 
    ['Battery_Capacyty',      	VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'AmpHour.I', 	   592,     3, 1,    1,      0,  true], //Batteriekapazität [Ah] 
//Grid
    ['Grid_L1_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       598,     3, 1,  0.1,      0,  true], // Netz Spannung Phase L1 [V]
    ['Grid_L2_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       599,     3, 1,  0.1,      0,  true], // Netz Spannung Phase L2 [V]
    ['Grid_L3_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       600,     3, 1,  0.1,      0,  true], // Netz Spannung Phase L3 [V]
    ['Out_Grid_L1_Power',       VARIABLETYPE_INTEGER,   VALTYPE_SWORD,	    'Watt.I', 	       616,     3, 1,    1,      0,  true], // Gesamtleistung in/aus Netz [W]
    ['Out_Grid_L2_Power',       VARIABLETYPE_INTEGER,   VALTYPE_SWORD,	    'Watt.I', 	       617,     3, 1,    1,      0,  true], // Gesamtleistung in/aus Netz [W]
    ['Out_Grid_L3_Power',       VARIABLETYPE_INTEGER,   VALTYPE_SWORD,	    'Watt.I', 	       618,     3, 1,    1,      0,  true], // Gesamtleistung in/aus Netz [W]
    ['Out_Grid_Total_Power',    VARIABLETYPE_INTEGER,   VALTYPE_SWORD,	    'Watt.I', 	       619,     3, 1,    1,      0,  true], // Gesamtleistung in/aus Netz [W]
    ['Inverter_Freq',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Hertz', 	       638,     3, 1, 0.01,      0,  true], // //Inverterfequqnz [Hz]
//Load
    ['Load_L1_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       644,     3, 1,  0.1,      0,  true], // Load Spannung Phase L1 [V]
    ['Load_L2_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       645,     3, 1,  0.1,      0,  true], // Load Spannung Phase L2 [V]
    ['Load_L3_Voltage',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       646,     3, 1,  0.1,      0,  true], // Load Spannung Phase L3 [V]
    ['Load_L1_Power',      	    VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       650,     3, 1,    1,      0,  true], // Load Leistung Phase L1 [W]
    ['Load_L2_Power',      	    VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       651,     3, 1,    1,      0,  true], // Load Leistung Phase L2 [W]
    ['Load_L3_Power',      	    VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       652,     3, 1,    1,      0,  true], // Load Leistung Phase L3 [W]
    ['Total_Load_Power',      	VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       653,     3, 1,    1,      0,  true], // Load Leistung Gesamt [W]
    ['Load_Freq',      	        VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Hertz', 	       655,     3, 1, 0.01,      0,  true], // //Inverterfequqnz [Hz]
//PV
    ['PV1_Power',      	        VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       672,     3, 1,    1,      0,  true], // PV1 Eingangsleistung [W]
    ['PV1_Voltage',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       676,     3, 1,  0.1,      0,  true], // PV1 Eingangsspannung [V]
    ['PV1_Current',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Ampere', 	       677,     3, 1,  0.1,      0,  true], // PV1 EingangssStrom [A]
    ['PV2_Power',      	        VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       673,     3, 1,    1,      0,  true], // PV2 Eingangsleistung [W]
    ['PV2_Voltage',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       678,     3, 1,  0.1,      0,  true], // PV2 Eingangsspannung [V]
    ['PV2_Current',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Ampere', 	       679,     3, 1,  0.1,      0,  true], // PV2 EingangssStrom [A]
    ['PV3_Power',      	        VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       674,     3, 1,    1,      0,  true], // PV3 Eingangsleistung [W]
    ['PV3_Voltage',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       680,     3, 1,  0.1,      0,  true], // PV3 Eingangsspannung [V]
    ['PV3_Current',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Ampere', 	       681,     3, 1,  0.1,      0,  true], // PV3 EingangssStrom [A]
    ['PV4_Power',      	        VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Watt.I', 	       675,     3, 1,    1,      0,  true], // PV4 Eingangsleistung [W]
    ['PV4_Voltage',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Volt', 	       682,     3, 1,  0.1,      0,  true], // PV4 Eingangsspannung [V]
    ['PV4_Current',      	    VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '~Ampere', 	       683,     3, 1,  0.1,      0,  true],  // PV4 EingangssStrom [A]

//Hier die Variablen auf die Geschrieben werden kann
//Diese werden auch von der Ladeoptimierung benutzt und geschrieben.
	//Name,                     VarType,                ValueType,          Profile,   Address, Function, Quantity, Factor, Offset, Keep
    //Die Akkubetriebsparameter, wenn der WR den Akku steuert. 
    ['Battery_Type',    	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeBattType',	98,     10, 1,  1,         0,  true],  //Batterietyp 0x0000 Blei, 0x0001 LiIon
    ['Epui_Voltage',    	    VARIABLETYPE_FLOAT,     VALTYPE_WORD, 	    '~Volt', 		99,     10, 1,  0.01,      0,  true],  //Equilisation-Spannung [V] 
    ['Absorp_Voltage',    	    VARIABLETYPE_FLOAT,     VALTYPE_WORD, 	    '~Volt', 		100,    10, 1,  0.01,      0,  true],  //Absorption-Spannung [V]
    ['Float_Voltage',    	    VARIABLETYPE_FLOAT,     VALTYPE_WORD, 	    '~Volt', 		101,    10, 1,  0.01,      0,  true],  //Float-Spannung [V]
    ['Batt_Capacity',    	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'AmpHour.I',    102,    10, 1,  1,         0,  true],  //Batteriekapazität [Ah]
    ['MaxChg_Current',    	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Ampere',      108,    10, 1,  1,         0,  true],  //Max. Ladestrom [A]
    ['MaxDsc_Current',    	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Ampere',      109,    10, 1,  1,         0,  true],  //Max. EntLadestrom [A]
    ['Battery_View',         	VARIABLETYPE_INTEGER,   VALTYPE_WORD,   	'DeyeBattView', 111,    10, 1,  1,         0,  true],  //Akkuanzeigemodus 0:Volt; 1:Prozent; 2:No Batt
    
    // Die sechs einstellbaren Betriebszeitenbereiche, in denen eine Ladeaktion ausgeführt werden kann 
    ['Time1',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',148,   10, 1,  1,      0,  true],     //Zeitbreich 1
    ['Time2',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',149,   10, 1,  1,      0,  true],     //Zeitbreich 2
    ['Time3',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',150,   10, 1,  1,      0,  true],     //Zeitbreich 3
    ['Time4',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',151,   10, 1,  1,      0,  true],     //Zeitbreich 4
    ['Time5',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',152,   10, 1,  1,      0,  true],     //Zeitbreich 5
    ['Time6',           	    VARIABLETYPE_INTEGER,   VALTYPE_TIME, 	    '~UnixTimestampTime',153,   10, 1,  1,      0,  true],     //Zeitbreich 6
    //Die Ladeleistung mit der die Batterie maximal geladen wird.
    ['ChgPwr1',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,154,   10, 1,  1,      0,  true],     //Ladeleistung 1
    ['ChgPwr2',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,155,   10, 1,  1,      0,  true],     //Ladeleistung 2
    ['ChgPwr3',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,156,   10, 1,  1,      0,  true],     //Ladeleistung 3
    ['ChgPwr4',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,157,   10, 1,  1,      0,  true],     //Ladeleistung 4
    ['ChgPwr5',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,158,   10, 1,  1,      0,  true],     //Ladeleistung 5
    ['ChgPwr6',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'Watt.I'        ,159,   10, 1,  1,      0,  true],     //Ladeleistung 6
    //Der Ladezustand der Batterie, der erreiht werden soll
    ['Capacity1',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,166,   10, 1,  1,      0,  true],     //Kapazität 1
    ['Capacity2',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,167,   10, 1,  1,      0,  true],     //Kapazität 2
    ['Capacity3',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,168,   10, 1,  1,      0,  true],     //Kapazität 3
    ['Capacity4',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,169,   10, 1,  1,      0,  true],     //Kapazität 4
    ['Capacity5',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,170,   10, 1,  1,      0,  true],     //Kapazität 5
    ['Capacity6',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    '~Battery.100'  ,171,   10, 1,  1,      0,  true],     //Kapazität 6
    //Der Lademodus (0:Keiner, 1:Grid-Charge, 2:Generator-Charge oder 3:beides)
    ['Chg_Mode1',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,172,   10, 1,  1,      0,  true],     //Lademodus 1
    ['Chg_Mode2',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,173,   10, 1,  1,      0,  true],     //Lademodus 2
    ['Chg_Mode3',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,174,   10, 1,  1,      0,  true],     //Lademodus 3
    ['Chg_Mode4',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,175,   10, 1,  1,      0,  true],     //Lademodus 4
    ['Chg_Mode5',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,176,   10, 1,  1,      0,  true],     //Lademodus 5
    ['Chg_Mode6',          	    VARIABLETYPE_INTEGER,   VALTYPE_WORD, 	    'DeyeChgMode'   ,177,   10, 1,  1,      0,  true]     //Lademodus 6
];    
}
