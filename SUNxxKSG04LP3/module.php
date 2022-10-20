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
        ['DayActive PowerWh', VARIABLETYPE_FLOAT, 'Electricity.I', 60, 4, 2, 0.1, true],
        ['DayReactive PowerWh', VARIABLETYPE_FLOAT, 'kVArh', 61, 4, 2, 0.1, true],
        ['Today_Gen_PowerWh', VARIABLETYPE_FLOAT, 'Electricity.I', 62, 4, 2, 0.1, true]  //Heute erzeugte Arbeit
	];
}
