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
	//Name, VarType, ValueType, Profile, Address, Function, Quantity, Keep
    ['Type',    	VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    '', 		 0, 3, 1, 1,    true],
    ['ModbusID', 	VARIABLETYPE_INTEGER,   VALTYPE_BYTE, 	    '', 		 1, 3, 1, 0.1,  true],
    ['ProtVer', 	VARIABLETYPE_STRING,    VALTYPE_STRING,	    '', 		 2, 3, 1, 0.1,  true],
    ['SerNo', 	    VARIABLETYPE_STRING,    VALTYPE_ASTRING,	'', 		 3, 3, 5, 0.1,  true]
	];
}
