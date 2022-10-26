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
require_once __DIR__ . '/SemaphoreHelper.php';  // diverse Klassen
eval('declare(strict_types=1);namespace Deye {?>' . file_get_contents(__DIR__ . '/helper/VariableProfileHelper.php') . '}');



    //spezielle Konstanten für die Typkonvertierung
    const VALTYPE_BYTE      = 0;      //Quelldaten sind Byte 0x00 - 0XFF
    const VALTYPE_WORD      = 1;      //Quelldaten sind WORD 0x0000 - 0XFFFF
    const VALTYPE_SWORD     = 2;      //Quelldaten sind WORD mit Vorzeichen 0x0000 - 0XFFFF -32768,32767
    const VALTYPE_DWORD     = 3;      //Quelldaten sind WORD 0x00000000 - 0XFFFFFFFF
    const VALTYPE_DWWORD    = 4;      //Quelldaten sind WORD 0x0000000000000000 - 0XFFFFFFFFFFFFFFFF
    const VALTYPE_ASTRING   = 5;      //Quelldaten sind ASCI-String
    const VALTYPE_STRING    = 6;      //Quelldaten sind byte Codierter String z.B. Für Versionsnummern 0x01 0x04 -> 1.04

        

/**
 * Deye ist die Basisklasse für alle  Wechselrichter der Forma Deye
 * Erweitert ipsmodule.
 * @property array $Variables
 */
class Deye extends IPSModule
{
    use \Deye\SemaphoreHelper;
    use \Deye\VariableProfileHelper;
    const Swap = true;

    //Die Assoziationen für den Invertertyp und den Status
    public static $AssInvType = [
        [2,'Stringinverter','',0xFFFFFF],
        [3,'Hybridinverter 1-ph','',0xFFFFFF],
        [4,'Microinverter','',0xFFFFFF],
        [5,'Hybridinverter 3-ph','',0xFFFFFF]
        ];
        
    public static $AssStatus = [
        [0,'Standby','',0xFFFFFF],
        [1,'Self-Check','',0xFFFFFF],
        [2,'Normal','',0xFFFFFF],
        [3,'Alarm','',0xFF00FF],
        [4,'Failure','',0xFF0000]
        ];





    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{A5F663AB-C400-4FE5-B207-4D67CC030564}');
        $this->RegisterPropertyInteger('Interval', 0);
        $Variables = [];
        foreach (static::$Variables as $Pos => $Variable) {
            $Variables[] = [
                'Ident'    => str_replace(' ', '', $Variable[0]),
                'Name'     => $this->Translate($Variable[0]),
                'VarType'  => $Variable[1],
                'Profile'  => $Variable[3],
                'Address'  => $Variable[4],
                'Function' => $Variable[5],
                'Quantity' => $Variable[6],
                'Pos'      => $Pos + 1,
                'Keep'     => $Variable[9],
                'ValType'  => $Variable[2],
				'Factor'   => $Variable[7],
				'Offset'   => $Variable[8]
            ];
        }

        $this->RegisterPropertyString('Variables', json_encode($Variables));
        $this->RegisterTimer('UpdateTimer', 0, static::PREFIX . '_RequestRead($_IPS["TARGET"]);');
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        $AssInvType = [
            [2,'Stringinverter','',0xFFFFFF],
            [3,'Hybridinverter 1-ph','',0xFFFFFF],
            [4,'Microinverter','',0xFFFFFF],
            [5,'Hybridinverter 3-ph','',0xFFFFFF]
            ];
            
        $AssStatus = [
            [0,'Standby','',0xFFFFFF],
            [1,'Self-Check','',0xFFFFFF],
            [2,'Normal','',0x00FF00],
            [3,'Alarm','',0xFF00FF],
            [4,'Failure','',0xFF0000]
            ];
    
        parent::ApplyChanges();
        //Invertertyp und Status
        $this->RegisterProfileIntegerEx('DeyeType', '', '','', $AssInvType, 5, 1);
        $this->RegisterProfileIntegerEx('DeyeStatus', '', '','', $AssStatus, 4, 1);

        //Float Variablen
        $this->RegisterProfileFloat('VaR', '', '', ' VAr', 0, 0, 0, 2);
        $this->RegisterProfileFloat('VA', '', '', ' VA', 0, 0, 0, 2);
        $this->RegisterProfileFloat('PhaseAngle', '', '', ' °', 0, 0, 0, 2);
        $this->RegisterProfileFloat('kVArh', '', '', ' kVArh', 0, 100, 0, 2);
        //Integer Variablen
        $this->RegisterProfileInteger('Volt.I', 'Electricity', '', ' V', 0, 0, 0);
        $this->RegisterProfileInteger('Watt.I', 'Electricity', '', ' W', 0, 0, 0);
        $this->RegisterProfileInteger('AmpHour.I', 'Electricity', '', ' Ah', 0, 0, 0);
        $this->RegisterProfileInteger('VaR.I', '', '', ' VAr', 0, 0, 0);
        $this->RegisterProfileInteger('VA.I', '', '', ' VA', 0, 0, 0);
        $this->RegisterProfileInteger('Electricity.I', '', '', ' kWh', 0, 0, 0);
        //Create Variables and check when new Rows in config appear after an update.
        $NewRows = static::$Variables;
        $NewPos = 0;
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            @$this->MaintainVariable($Variable['Ident'], $Variable['Name'], $Variable['VarType'], $Variable['Profile'], $Variable['Pos'], $Variable['Keep']);
            foreach ($NewRows as $Index => $Row) {
                if ($Variable['Ident'] == str_replace(' ', '', $Row[0])) {
                    unset($NewRows[$Index]);
                }
            }
            if ($NewPos < $Variable['Pos']) {
                $NewPos = $Variable['Pos'];
            }
        }
        if (count($NewRows) != 0) {
            foreach ($NewRows as $NewVariable) {
                $Variables[] = [
                    'Ident'    => str_replace(' ', '', $NewVariable[0]),
                    'Name'     => $this->Translate($NewVariable[0]),
                    'VarType'  => $NewVariable[1],
                    'Profile'  => $NewVariable[3],
                    'Address'  => $NewVariable[4],
                    'Function' => $NewVariable[5],
                    'Quantity' => $NewVariable[6],
                    'Pos'      => ++$NewPos,
                    'Keep'     => $NewVariable[9],
                    'ValType'  => $NewVariable[2],
                    'Factor'   => $NewVariable[7],
                    'Offset'   => $NewVariable[8]
                ];
            }
            IPS_SetProperty($this->InstanceID, 'Variables', json_encode($Variables));
            IPS_ApplyChanges($this->InstanceID);
            return;
        }
        if ($this->ReadPropertyInteger('Interval') < 500) {
            if ($this->ReadPropertyInteger('Interval') != 0) {
                $this->SetStatus(IS_EBASE + 1);
            } else {
                $this->SetStatus(IS_ACTIVE);
            }
            $this->SetTimerInterval('UpdateTimer', 0);
        } else {
            $this->SetTimerInterval('UpdateTimer', $this->ReadPropertyInteger('Interval'));
            $this->SetStatus(IS_ACTIVE);
        }
    }

    /**
     * IPS-Instanz Funktion PREFIX_RequestRead.
     * Ließt alle Werte aus dem Gerät.
     *
     * @return bool True wenn Befehl erfolgreich ausgeführt wurde, sonst false.
     */
    public function RequestRead()
    {
        $Gateway = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($Gateway == 0) {
            return false;
        }
        $IO = IPS_GetInstance($Gateway)['ConnectionID'];
        if ($IO == 0) {
            return false;
        }
        if (!$this->lock($IO)) {
            return false;
        }
        $Result = $this->ReadData();
        IPS_Sleep(333);
        $this->unlock($IO);
        return $Result;
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Form['actions'][0]['onClick'] = static::PREFIX . '_RequestRead($id);';
        if (count(static::$Variables) == 1) {
            unset($Form['elements'][1]);
        }
        return json_encode($Form);
    }

    protected function ModulErrorHandler($errno, $errstr)
    {
        $this->SendDebug('ERROR', utf8_decode($errstr), 0);
        echo $errstr;
    }

    /**
     * Setzte eine IPS-Variableauf den Wert von $value.
     *
     * @param array $Variable Statusvariable
     * @param mixed $Value    Neuer Wert der Statusvariable.
     */
    protected function SetValueExt($Variable, $Value)
    {
        $id = @$this->GetIDForIdent($Variable['Ident']);
        if ($id == false) {
            $this->MaintainVariable($Variable['Ident'], $Variable['Name'], $Variable['VarType'], $Variable['Profile'], $Variable['Pos'], $Variable['Keep']);
        }
        $this->SetValue($Variable['Ident'], $Value);
        return true;
    }
    



	// lesen der Daten aus den übergeordneten Modbus Instanz
    private function ReadDataOld()
    {
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if (!$Variable['Keep']) {
                continue;
            }
            $SendData['DataID'] = '{E310B701-4AE7-458E-B618-EC13A1A6F6A8}';
            $SendData['Function'] = $Variable['Function'];                  //in der Regel 0x03 zum lesen und 0x10 zum Schreiben
            $SendData['Address']  = $Variable['Address'];
            $SendData['Quantity'] = $Variable['Quantity'];
            $SendData['Data'] = '';
            set_error_handler([$this, 'ModulErrorHandler']);
    
            $ReadData = $this->SendDataToParent(json_encode($SendData));
            restore_error_handler();
            if ($ReadData === false) {
                return false;
            }
            $this->SendDebug($Variable['Name'] . ' ReadData', $ReadData, 1);

            $ReadValue = substr($ReadData, 2);
            $this->SendDebug($Variable['Name'] . ' RAW', $ReadValue, 1);
			 if (static::Swap) {
                $ReadValue = strrev($ReadValue);
             }
            $Value = $this->ConvertValue($Variable, $ReadValue);
            
            if ($Value === null) {
                $this->LogMessage(sprintf($this->Translate('Combination of type and size of value (%s) not supported.'), $Variable['Name']), KL_ERROR);
                continue;
            }
            //Bei FloatVAriablen jetzt noch den Faktor einrechnen //Hier noch eventuell den Offset einrechnen falls vorhanden
            if ($Variable['VarType'] == VARIABLETYPE_FLOAT){
                $Value= ($Value - $Variable['Offset']) * $Variable['Factor'];
            }

            
            $this->SendDebug($Variable['Name'], $Value, 0);
            $this->SetValueExt($Variable, $Value);
        }
        return true;
    }
    

	// schnelleres blockweises lesen der Daten aus den übergeordneten Modbus Instanz
    // Da muss aber auf zwei blöcke aufgeteilt werden.
    private function ReadDataFast()
    {
        $SendData['DataID'] = '{E310B701-4AE7-458E-B618-EC13A1A6F6A8}';
        $SendData['Function'] = $Variable['Function'];                  //in der Regel 0x03 zum lesen und 0x10 zum Schreiben
        $SendData['Address']  = 500;                                    //Startadresse 500
        $SendData['Quantity'] = 100;                                    //100 Werte (200 byte) im Block lesen
        $SendData['Data'] = '';
        set_error_handler([$this, 'ModulErrorHandler']);

        $ReadData = $this->SendDataToParent(json_encode($SendData));
        restore_error_handler();
        if ($ReadData === false) {
            return false;
        }
        $this->SendDebug('Block ReadData', $ReadData, 1);

        $ReadValue = substr($ReadData, 2);
        //jetzt durch die einzelnen Bytes durch gehen und die Werte auslesen
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if (!$Variable['Keep']) {
                continue;
            }
            if ($Variable['Address'] > 499 ){
             $this->SendDebug($Variable['Name'] . ' RAW', $ReadValue, 1);
             //Den Wert aus dem Block herauslesen
             $SValue = substr($ReadValue, $Variable['Address'] - 500, $Variable['Quantity']*2);

             if (static::Swap) {
                $SValue = strrev($SValue);
             }

             $Value = $this->ConvertValue($Variable, $SValue);
            
             if ($Value === null) {
                 $this->LogMessage(sprintf($this->Translate('Combination of type and size of value (%s) not supported.'), $Variable['Name']), KL_ERROR);
                continue;
             }
             //Bei Float_Variablen jetzt noch den Faktor einrechnen Hier noch eventuell den Offset einrechnen falls vorhanden
             if ($Variable['VarType'] == VARIABLETYPE_FLOAT){
                 $Value= ($Value - $Variable['Offset']) * $Variable['Factor'];
             }

            
            $this->SendDebug($Variable['Name'], $Value, 0);
            $this->SetValueExt($Variable, $Value);
          }
        }
        return true;
    }
    

    private function ReadDataBase()
    {
        $SendData['DataID'] = '{E310B701-4AE7-458E-B618-EC13A1A6F6A8}';
        $SendData['Function'] = $Variable['Function'];                  //in der Regel 0x03 zum lesen und 0x10 zum Schreiben
        $SendData['Address']  = 0;                                    //Startadresse 0
        $SendData['Quantity'] = 20;                                    //20 Werte (200 byte) im Block lesen
        $SendData['Data'] = '';
        set_error_handler([$this, 'ModulErrorHandler']);

        $ReadData = $this->SendDataToParent(json_encode($SendData));
        restore_error_handler();
        if ($ReadData === false) {
            return false;
        }
        $this->SendDebug('Block ReadData', $ReadData, 1);

        $ReadValue = substr($ReadData, 2);
        //jetzt durch die einzelnen Bytes durch gehen und die Werte auslesen
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if (!$Variable['Keep']) {
                continue;
            }
            if ($Variable['Address'] > 100 ){
             $this->SendDebug($Variable['Name'] . ' RAW', $ReadValue, 1);
             //Den Wert aus dem Block herauslesen

             $SValue = substr($ReadValue, $Variable['Address'] - 500, $Variable['Quantity']*2);

             if (static::Swap) {
                $SValue = strrev($SValue);
             }

             $Value = $this->ConvertValue($Variable, $SValue);
            
             if ($Value === null) {
                 $this->LogMessage(sprintf($this->Translate('Combination of type and size of value (%s) not supported.'), $Variable['Name']), KL_ERROR);
                continue;
             }
             //Bei FloatVAriablen jetzt noch den Faktor einrechnen //Hier noch eventuell den Offset einrechnen falls vorhanden
             if ($Variable['VarType'] == VARIABLETYPE_FLOAT){
                 $Value= ($Value - $Variable['Offset']) * $Variable['Factor'];
             }

            
            $this->SendDebug($Variable['Name'], $Value, 0);
            $this->SetValueExt($Variable, $Value);
          }
        }
        return true;
    }



// lesen der Daten aus den übergeordneten Modbus Instanz
private function ReadData()
{
  ReadDataBase();
  ReadDataFast();
  return true;
}



    // Hier die Konvertierung der Variablen
    private function ConvertValue(array $Variable, string $Value)
    {
        $vt = 0; 
        switch ($Variable['VarType']) {
            case VARIABLETYPE_BOOLEAN:
                if ($Variable['Quantity'] == 1) {
                    return ord($Value) == 0x01;
                }
                break;
            case VARIABLETYPE_INTEGER:
                switch ($Variable['ValType']) {
                    case VALTYPE_BYTE:
                        return ord($Value);
                    case VALTYPE_WORD:
                        return unpack('v', $Value)[1]; //Vorzichenlos word
                    case VALTYPE_SWORD:
                         return unpack('s', $Value)[1]; //Vorzeichenbehaftet word
                    case VALTYPE_DWORD:
                        return unpack('V', $Value)[1]; //Vorzeichenlos Long
                    case VALTYPE_DWWORD:
                        return unpack('P', $Value)[1]; //Vorzeichenlos LongLong
                }
                break;
            
            case VARIABLETYPE_FLOAT:
                switch ($Variable['ValType']) {
                    case VALTYPE_BYTE:
                        return ord($Value);
                    case VALTYPE_WORD:
                        return unpack('v', $Value)[1]; //Vorzichenlos Short
                    case VALTYPE_DWORD:                  
                       if (strlen($Value) > 3) {
                        $s = $Value[0];
                        $Value[0] = $Value[2];
                        $Value[2] = $s;
                        $s = $Value[1];
                        $Value[1] = $Value[3];
                        $Value[3] = $s;
                        $this->SendDebug(' SWAP', $Value, 1);
                       }
                        return unpack('V', $Value)[1]; //Vorzeichenlos Long    
                    case VALTYPE_DWWORD:
                        return unpack('P', $Value)[1]; //Vorzeichenlos LongLong
                }
                break;
       
            case VARIABLETYPE_STRING:
                switch ($Variable['ValType']) {
                    case VALTYPE_ASTRING:
                       return strrev($Value);  //Strings immer in korrekter reihenfolge
                    case VALTYPE_STRING:
                        return 'Kommt noch';  //Strings immer in korrekter reihenfolge        
                }
        }        
        return null;
    }
}
