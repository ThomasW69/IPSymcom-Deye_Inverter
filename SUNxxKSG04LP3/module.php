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
class SUNxxKSG04LP3 extends Deye
{
    const PREFIX = 'SUNxxKSG04LP3';

    public static $Variables = [
	//Name, VarType, Profile,Address,Function,Quantity, Keep
        ['SerialNo', 				VARIABLETYPE_STRING, '', 			  3, 0x03, 5, 0, true],
        ['Inverter Voltage L1', 	VARIABLETYPE_FLOAT, 'Volt.I', 		154, 0x03, 1, 0.1, true],
        ['Inverter Voltage L2', 	VARIABLETYPE_FLOAT, 'Volt.I', 		155, 0x03, 1, 0.1, true],
        ['DayActive PowerWh', 		VARIABLETYPE_FLOAT, 'Electricity.I', 60, 0x03, 1, 0.1, true],
        ['DayReactive PowerWh', 	VARIABLETYPE_FLOAT, 'kVArh', 		 61, 0x03, 1, 0.1, true],
        ['Today_Gen_PowerWh', 		VARIABLETYPE_FLOAT, 'Electricity.I', 62, 0x03, 1, 0.1, true],  //Heute erzeugte Arbeit
        ['Load Voltage L1', 		VARIABLETYPE_FLOAT, 'Volt.I', 		157, 0x03, 1, 1, true],  			 //Spannung am Load Phase L1
        ['Load Voltage L2', 		VARIABLETYPE_FLOAT, 'Volt.I', 		158, 0x03, 1, 1, true]  			 //Spannung am Load Phase L2
	];
}
