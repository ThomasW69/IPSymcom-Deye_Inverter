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
    
/*
                'Name'     => $this->Translate($Variable[0]),
                'VarType'  => $Variable[1],
                'ValType'  => $Variable[2],
                'Profile'  => $Variable[3],
                'Address'  => $Variable[4],
                'Function' => $Variable[5],
                'Quantity' => $Variable[6],
                'Pos'      => $Pos + 1,
				'Factor'   => $Variable[7],
                'Keep'     => $Variable[8]
*/

    public static $Variables = [
	//Name, VarType, ValueType, Profile, Address, Function, Quantity, Factor, Offset, Keep
    ['Type',    	    VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    '', 		 0,     3, 1,   1,      0,  true],
    ['ModbusID', 	    VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    '', 		 1,     3, 1, 0.1,      0,  true],
    ['ProtVer', 	    VARIABLETYPE_STRING,    VALTYPE_STRING,	    '', 		 2,     3, 1, 0.1,      0,  true],
    ['SerNo', 	        VARIABLETYPE_STRING,    VALTYPE_ASTRING,	'', 		 3,     3, 5, 0.1,      0,  true],
    ['DC_Trans_Temp', 	VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    '', 	   540,     3, 1, 0.1,   1000,  true],
    ['Batt_Temp',    	VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    '', 	   586,     3, 1, 0.1,   1000,  true],
    ['Batt_V',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '', 	   587,     3, 1, 0.001,  0,  true],
    ['Batt_Lvl',      	VARIABLETYPE_INTEGER,   VALTYPE_WORD,	    'Percent', 588,     3, 1,    1,  0,  true],
    ['Batt_I',      	VARIABLETYPE_FLOAT,     VALTYPE_WORD,	    '', 	   591,     3, 1, 0.001,  0,  true]
	];
}
