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
 * @version       0.20
 *
 */
require_once __DIR__ . '/SemaphoreHelper.php';  # diverse Klassen
eval('declare(strict_types=1);namespace Deye {?>' . file_get_contents(__DIR__ . '/helper/VariableProfileHelper.php') . '}');



    #spezielle Konstanten für die Typkonvertierung
    const VALTYPE_BYTE      = 0;      #Quelldaten sind Byte 0x00 - 0XFF
    const VALTYPE_WORD      = 1;      #Quelldaten sind WORD 0x0000 - 0XFFFF
    const VALTYPE_SWORD     = 2;      #Quelldaten sind WORD mit Vorzeichen 0x0000 - 0XFFFF -32768,32767
    const VALTYPE_DWORD     = 3;      #Quelldaten sind WORD 0x00000000 - 0XFFFFFFFF
    const VALTYPE_DWWORD    = 4;      #Quelldaten sind WORD 0x0000000000000000 - 0XFFFFFFFFFFFFFFFF
    const VALTYPE_ASTRING   = 5;      #Quelldaten sind ASCI-String
    const VALTYPE_STRING    = 6;      #Quelldaten sind byte Codierter String z.B. Für Versionsnummern 0x01 0x04 -> 1.04
    const VALTYPE_TIME      = 7;      #Quelldaten sind Zeit als Integerzahl 0000-2359

        

/**
 * Deye ist die Basisklasse für alle  Wechselrichter der Forma Deye
 * Erweitert ipsmodule.
 * @property array $Variables
 */
class Deye extends IPSModuleStrict
{
    use \Deye\SemaphoreHelper;
    use \Deye\VariableProfileHelper;

    const Swap = true;

    #Die Assoziationen für den Invertertyp und den Status
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


    public static     $AssChargeMode = [
        [0,'None','',0xFF0000],
        [1,'Grid','',0x00FF00],
        [2,'Generator','',0x0000FF],
        [3,'Both','',0xFF00FF]
        ];   

    public static     $AssLimitControl = [
        [0,'Selling First','',0xFF0000],
        [1,'Zero To Load','',0x00FF00],
        [2,'Zero To CT','',0x0000FF]
        ];   
    

            /**
     * Update interval for a cyclic timer.
     *
     * @param string $ident  Name and ident of the timer.
     * @param int    $hour   Start hour.
     * @param int    $minute Start minute.
     * @param int    $second Start second.
     */
    private function UpdateTimerInterval($ident, $hour, $minute, $second)
    {
        $now = new DateTime();
        $target = new DateTime();
        $target->modify('+1 day');
        $target->setTime($hour, $minute, $second);
        $diff = $target->getTimestamp() - $now->getTimestamp();
        $interval = $diff * 1000;
        $this->SetTimerInterval($ident, $interval);
    }


    

    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {

        parent::Create();
        
        #ArchivID Holen
        $ACID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        
        $this->ConnectParent('{A5F663AB-C400-4FE5-B207-4D67CC030564}');
        $this->RegisterPropertyInteger('Interval', 0);                        #Abrufintervall

        #Variable Stromtarife
        $this->RegisterPropertyBoolean('UseVarTarif', false);                 #Ladestrategie mit Preisminimierung durch variablen Strompreis
        $this->RegisterPropertyInteger('Provider', 0);                        #Ladestrategie mit Preisminimierung durch variablen Strompreis
        $this->RegisterPropertyString('Token', "");                           #Token des Dienstanbieters
        
        #Solarprognose
        $this->RegisterPropertyBoolean('UseSolarprog', false);                #Einzbeziehung der Solarprognose
        $this->RegisterPropertyInteger('SolarProgID', 0);                     #ID der Solarprognose
        $this->RegisterPropertyInteger('SolarProgTimeshift', -24);              #Zeitversatz der Zeitreihe in Stunden

        #Zeitreihe mit dem Strompreis
        $PID = $this->RegisterVariableFloat("EnergyPrice","EnergyPrice");       #Zeitreihe für den variablen Stromtarif
        $this->RegisterPropertyInteger('PriceID', $PID);                        #ZeitreihenID in Property Speichern
        AC_SetLoggingStatus ($ACID, $PID, true);                                #Logging einschalten
        
        
        #Zuerst die Statusvariablen (ReadOnly)
        $Variables = [];
        foreach (static::$Variables as $Pos => $Variable) {
            $Variables[] = [
                'Ident'    => str_replace(' ', '', $Variable[0]),
                'Name'     => $this->Translate($Variable[0]),
                'VarType'  => $Variable[1],
                'ValType'  => $Variable[2],
                'Profile'  => $Variable[3],
                'Address'  => $Variable[4],
                'Function' => $Variable[5],
                'Quantity' => $Variable[6],
				'Factor'   => $Variable[7],
				'Offset'   => $Variable[8],
                'Keep'     => $Variable[9],
                'Pos'      => $Pos + 1
            ];
        }
        $this->RegisterPropertyString('Variables', json_encode($Variables));
        $this->RegisterTimer('UpdateTimer', 0, static::PREFIX . '_RequestRead($_IPS["TARGET"]);');  #Timer zum Abruf der Daten vom Deye
        $this->RegisterTimer('TarifTimer', 0, static::PREFIX . '_getVariableTarif($_IPS["TARGET"]);');  #Timer zum Abruf der Daten vom Tarifanbieter Erster Aufruf 
        

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
            [2,'Normal','',0xFFFFFF],
            [3,'Alarm','',0xFF00FF],
            [4,'Failure','',0xFF0000]
            ];
    
    
        $AssChargeMode = [
                [0,'None','',0xFF0000],
                [1,'Grid','',0x00FF00],
                [2,'Generator','',0x0000FF],
                [3,'Both','',0xFF00FF]
                ];   
    
        $AssBattType = [
                [0,'Lead','',0x0000],
                [1,'LiIon','',0x0001]
                ];           

        $AssBattView = [
                [0,'Volt','',0x0000],
                [1,'Percent','',0x0001],
                [2,'NoBatt','',0x0002]
                ];           

        $AssLimitControl = [
                [0,'Selling First','',0xFF0000],
                [1,'Zero To Load','',0x00FF00],
                [2,'Zero To CT','',0x0000FF]
                ];   
       
        parent::ApplyChanges();
        #Invertertyp und Status
        $this->RegisterProfileIntegerEx('DeyeType', '', '','', $AssInvType, 5, 1);
        $this->RegisterProfileIntegerEx('DeyeStatus', '', '','', $AssStatus, 4, 1);
        $this->RegisterProfileIntegerEx('DeyeChgMode', '', '','', $AssChargeMode, 3, 1);
        $this->RegisterProfileIntegerEx('DeyeBattType', '', '','', $AssBattType, 1, 1);
        $this->RegisterProfileIntegerEx('DeyeBattView', '', '','', $AssBattView, 2, 1);
        $this->RegisterProfileIntegerEx('DeyeLimControl', '', '','', $AssLimitControl, 2, 1);

        #Float Variablen
        $this->RegisterProfileFloat('VaR', '', '', ' VAr', 0, 0, 0, 2);
        $this->RegisterProfileFloat('VA', '', '', ' VA', 0, 0, 0, 2);
        $this->RegisterProfileFloat('PhaseAngle', '', '', ' °', 0, 0, 0, 2);
        $this->RegisterProfileFloat('kVArh', '', '', ' kVArh', 0, 100, 0, 2);
        #Integer Variablen
        $this->RegisterProfileInteger('Volt.I', 'Electricity', '', ' V', 0, 0, 0);
        $this->RegisterProfileInteger('Watt.I', 'Electricity', '', ' W', 0, 0, 0);
        $this->RegisterProfileInteger('AmpHour.I', 'Electricity', '', ' Ah', 0, 0, 0);
        $this->RegisterProfileInteger('VaR.I', '', '', ' VAr', 0, 0, 0);
        $this->RegisterProfileInteger('VA.I', '', '', ' VA', 0, 0, 0);
        $this->RegisterProfileInteger('Electricity.I', '', '', ' kWh', 0, 0, 0);
 
        #Create Variables and check when new Rows in config appear after an update.
        $NewRows = static::$Variables;
        $NewPos = 0;
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            @$this->MaintainVariable($Variable['Ident'], $Variable['Name'], $Variable['VarType'], $Variable['Profile'], $Variable['Pos'], $Variable['Keep']);
            #Die schreibbaren Variablen im Registerbereich 99 bis 499 editierbar machen
            if (($Variable['Address'] >= 60 ) && ($Variable['Address'] <= 499 )) {  
                $this->EnableAction($Variable['Ident']);
            }

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
                    'ValType'  => $NewVariable[2],
                    'Profile'  => $NewVariable[3],
                    'Address'  => $NewVariable[4],
                    'Function' => $NewVariable[5],
                    'Quantity' => $NewVariable[6],
                    'Factor'   => $NewVariable[7],
                    'Offset'   => $NewVariable[8],
                    'Keep'     => $NewVariable[9],
                    'Pos'      => ++$NewPos
                ];
            }
            IPS_SetProperty($this->InstanceID, 'Variables', json_encode($Variables));
            IPS_ApplyChanges($this->InstanceID);
            return;
        }

        #Timer für die Datenabfrage vom Deye aktualisieren
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
        
        //Timer für die Tarifabfrage aktualisieren
        if ($this->ReadPropertyInteger('Provider') < 1) {
            $this->SetTimerInterval('TarifTimer', 0);
        } else {
            $this->UpdateTimerInterval('TarifTimer',23,30,0);
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
        $this->SendDebug('ERROR', mb_convert_encoding($errstr, 'ISO-8859-1', 'UTF-8'), 0);  #UTF8 Decode Ersetzen
        echo $errstr;
    }

    /**
     * Setzte eine IPS-Variable auf den Wert von $value.
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


    /*****************************************************************************************************/
    /* ab hier funktionen zum Setzen einzelner Betriebezustände 
    /* zunächst Hilfsfunktionen zum setzen einzelner Variablentypen
    /*****************************************************************************************************/
    
    
    
    /*
     * Setzt eine boolesche Variable deren Name angegeben wird
     * 
     * @param string $VariableName Name der zu setzenden Variable
     * @param bool $Value Boolescher Wert, der gesetzt werden soll
     * @return bool True wenn erfolgreich, sonst false
     */
    protected function SetVariableByBool(string $VariableName, bool $Value): bool
    {
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if ($Variable['Name'] == $this->Translate($VariableName)) {
                return $this->SetValueExt($Variable, $Value);
            }
        }
        return false;
    }

    /*
     * Setzt eine integer Variable deren Name angegeben wird
     * 
     * @param string $VariableName Name der zu setzenden Variable
     * @param bool $Value Boolescher Wert, der gesetzt werden soll
     * @return bool True wenn erfolgreich, sonst false
     */
    protected function SetVariableByInteger(string $VariableName, int $Value): bool
    {
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if ($Variable['Name'] == $this->Translate($VariableName) && $Variable['VarType'] == VARIABLETYPE_INTEGER) {
                return $this->SetValueExt($Variable, $Value);
            }
        }
        return false;
    }


     /*
     * Aktiviert bzw Deaktiviert die Akku-Ladung
     *
     * @param bool $Value Boolescher Wert, der gesetzt werden soll
     * @return bool True wenn erfolgreich, sonst false
     */
    public function SetAkkuCharging(bool $Value): bool
    { 
        if ($Value) {
           $this->SendDataToDeye('Chg_Mode1', 1); 
           //$this->SetVariableByInteger('Limit_Control', 2); //Limitcontrol auf Zero to CT setzen
           //$this->SetVariableByInteger('Max_Sell_Power', 5000); //Maximale Einspeiseleistung auf 5000W setzen
           //$this->SetVariablebyInteger( 'Chg_Mode1',1); 
    
        } else {
           $this->SendDataToDeye('Chg_Mode1', 0);
           //$this->SetVariableByInteger('Limit_Control', 0); //Limitcontrol auf Selling First setzen
           //$this->SetVariableByInteger('Max_Sell_Power', 0); //Maximale Einspeiseleistung auf 0 setzen
           //$this->SetVariablebyInteger( 'Chg_Mode1',0); 

        }
        return true;
    }
   

    

        

	# schnelleres blockweises lesen der Daten aus den übergeordneten Modbus Instanz
    # Da muss aber auf zwei blöcke aufgeteilt werden.
    private function ReadDataBlock(int $Start, int $End)
    {   
        $SendData['DataID'] = '{E310B701-4AE7-458E-B618-EC13A1A6F6A8}';
        $SendData['Function'] = 0x03;                  #in der Regel 0x03 zum lesen und 0x10 zum Schreiben
        $SendData['Address']  = $Start;                #Startadresse 
        $SendData['Quantity'] = $End-$Start+4;         #Anzahl Blöcke
        $SendData['Data'] = '';
        set_error_handler([$this, 'ModulErrorHandler']);

        $ReadData = $this->SendDataToParent(json_encode($SendData));
       // $this->SendDebug('Eingangsdaten', $ReadData,0);
        restore_error_handler();
        if ($ReadData === false) {
            return false;
        }
    
        $ReadValue = substr($ReadData, 2);
        //$this->SendDebug('ReadValue', $ReadValue,0);

        #jetzt durch die einzelnen Bytes durch gehen und die Werte auslesen
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            if (!$Variable['Keep']) {
                continue;
            }
            #Nur die Variablen, die auch in diesem bereich sind           
            if ($Variable['Address'] >= $Start && $Variable['Address'] <= $End)
            {
              #Den Wert aus dem Block herauslesen
              #$this->SendDebug('Pos.: Count: ', (($Variable['Address'] - $Start) *2 . ' ' . ($Variable['Quantity']*2)) , 1);

              $SValue = substr($ReadValue, ($Variable['Address'] - $Start) *2, $Variable['Quantity']*2);

             /* if (($Variable['Address'] >= 148) && ($Variable['Address'] <= 149)){
                $this->SendDebug('Return', $ReadValue, 0); 
                $this->SendDebug('Value', $SValue, 0); 
              }*/    

              if (static::Swap) {
                 $SValue = strrev($SValue);
              }
             # $this->SendDebug('ValueSwap', $SValue, 0); 
    


              $Value = $this->ConvertValue($Variable, $SValue);
            
              if ($Value === null) {
                  $this->LogMessage(sprintf($this->Translate('Combination of type and size of value (%s) not supported.'), $Variable['Name']), KL_ERROR);
                 continue;
              }
              #Bei Float_Variablen jetzt noch den Faktor einrechnen Hier noch eventuell den Offset einrechnen falls vorhanden
              if ($Variable['VarType'] == VARIABLETYPE_FLOAT){
                  $Value= ($Value - $Variable['Offset']) * $Variable['Factor'];
              }

            # $this->SendDebug($Variable['Name'], $Value, 0);
             $this->SetValueExt($Variable, $Value);
          }
        }
        return true;
    }
    




# lesen der Daten aus den übergeordneten Modbus Instanz
     private function ReadData()
     {
    #Daten werden in ganzen Blöcken gelesen. Das braucht nur drei Modbus anfragen und geht wesentlich schneller
    if (IPS_SemaphoreEnter("DeyeModbusRequest", 1000)) {
      $this->ReadDataBlock(0, 40);  //allgemeines
      $this->ReadDataBlock(98, 177); //Akkuparameter und Lademodus
      $this->ReadDataBlock(500, 599); //Statistiken 2
      $this->ReadDataBlock(600, 699); //Statistiken 2 
      IPS_SemaphoreLeave("DeyeModbusRequest");
    } 
      return true;
    }





    # Hier die Konvertierung der Variablen
    private function ConvertValue(array $Variable, string $Value)
    {
        $vt = 0; 
        $v  = 0;
        $h  = 0;
        $min= 0;


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
                        return unpack('v', $Value)[1]; #Vorzeichenlos word
                    case VALTYPE_SWORD:
                        return unpack('s', $Value)[1]; #Vorzeichenbehaftet word
                    case VALTYPE_DWORD:
                        return unpack('V', $Value)[1]; #Vorzeichenlos Long
                    case VALTYPE_DWWORD:
                        return unpack('P', $Value)[1]; #Vorzeichenlos LongLong
                    case VALTYPE_TIME: {
                         $v   = unpack('v', $Value)[1]; #vorzeichenloser Short-Typ (immer 16 Bit (Word), Byte-Folge Little-Endian)
                         $h   = intval($v/100); #Die Stunden sind das erste Byte dezimal-Codiert 
                         $min = $v%100;                #die Minuten der Rest im zweiten Byte Dezimalcodiert
                         return ($h*3600+$min*60)-3600; # in einen Unix Timestring umwandeln
                        } 
                }
                break;
            
            case VARIABLETYPE_FLOAT:
                switch ($Variable['ValType']) {
                    case VALTYPE_BYTE:
                        return ord($Value);
                    case VALTYPE_WORD:
                        return unpack('v', $Value)[1]; #Vorzeichenlos Short
                    case VALTYPE_SWORD:
                        return unpack('s', $Value)[1]; #Vorzeichenbehaftet word
                      
                    case VALTYPE_DWORD:                  
                       if (strlen($Value) > 3) {
                        $s = $Value[0];
                        $Value[0] = $Value[2];
                        $Value[2] = $s;
                        $s = $Value[1];
                        $Value[1] = $Value[3];
                        $Value[3] = $s;
                       }
                        return unpack('V', $Value)[1]; #Vorzeichenlos Long    
                    case VALTYPE_DWWORD:
                        return unpack('P', $Value)[1]; #Vorzeichenlos LongLong
                }
                break;
       
            case VARIABLETYPE_STRING:
                switch ($Variable['ValType']) {
                    case VALTYPE_ASTRING:
                       return strrev($Value);  #Strings immer in korrekter reihenfolge
                    case VALTYPE_STRING:
                        return 'Kommt noch';  #Strings immer in korrekter reihenfolge        
                }
        }        
        return null;
    }
    





    public function RequestAction($Ident, $Value) {
        $this->LogMessage("RequestAction : $Ident, $Value",KL_NOTIFY);
        switch ($Ident) {
           // case "GetDeye":
           //     $this->SendDataToDeye($Ident, $Value);
           //     break;
            case "GetPrice":
                $this->getVariableTarif();
                break;
            default:     
                $this->SendDataToDeye($Ident, $Value);

        }  
    }


    /*******************************************************
    //Hier werden die Daten direkt an den Deye WR gesendet 
    //******************************************************
    */  
    

    private function SendDataToDeye($Ident, $Value){
    $str  = '';   #zu sendende Daten
    $Resp = '';   #Antwort
    $h    = 0;    #Hilfsvariable Stunden
    $m    = 0;    #Hilfsvariable Minuten

    if (IPS_SemaphoreEnter("DeyeModbusRequest", 1000)) {

    $Variables = json_decode($this->ReadPropertyString('Variables'), true);         #Modulvariablen holen 
    foreach ($Variables as $Variable) {                                             #durch die Variablen durchgehen und suchen

        if (!$Variable['Keep']) {
           continue;
        } 
        #$this->SendDebug('Suche ',$this->Translate($Ident).' | '. $Variable['Name'] , 0);
        
        if ($Variable['Name'] == $this->Translate($Ident)) {                        #Wenn Variable gefunden
            $Start = $Variable['Address'];                                          #Registeradresse holen
        
            #Variablen zurück in einen String wandeln
            #Bei den schreibenden Varianten gibt es nur VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT und das als VALTYPE_WORD nd VALTYPE_TIME
            switch ($Variable['VarType']) {

                case VARIABLETYPE_INTEGER:
                    switch ($Variable['ValType']) {
                        case VALTYPE_TIME:           
                           // Konvertiere den Timestamp in Stunden und Minuten
                            $hours = (int) date('H', $Value);
                            $minutes = (int) date('i', $Value);
                            // Kodierung: Stunden als Hunderter und Minuten als Einer
                            $decimalValue = ($hours * 100) + $minutes;
                            $formatted_hex = sprintf("%04x", $decimalValue);   #umformatierung in Hex
                            $first_char = chr(hexdec(substr($formatted_hex, 0, 2))); #aufspaltung in zwei ASCII Zeichen
                            $second_char = chr(hexdec(substr($formatted_hex, 2, 2)));
                            $str = $first_char . $second_char ; #ASCII Zusammenbauen
                            $Resp = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 0x10, "Address" => $Start , "Quantity" => 1, "Data" => utf8_encode($str))));
                            break;
                        case VALTYPE_WORD:
                            $str = pack('s', $Value); #Vorzeichenlos Word
                            $str = strrev($str);
                            $Resp = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 0x10, "Address" => $Start , "Quantity" => 1, "Data" => utf8_encode($str))));
                            break;
                    }
                    break;
            }        

     }
    }
    IPS_SemaphoreLeave("DeyeModbusRequest");  
  }
}




/**************************************************************************************************************
Ab Hier dann die Funktionen für die Vartiablen Stromtarife
Wird nicht mehr benötiggt, da steuerung über Energiemanager
***************************************************************************************************************/

public function getVariableTarif() {
#Irgendwas tun
$this->GetTibberPrice();

//Danach den Timer anpassen, damit er am kommenden Tag ausgeführt wird
$this->UpdateTimerInterval('TarifTimer', 23, 30, 00);

}





#Abruf der tibberdaten
private function GetTibberPrice() {
    # tibber Chart heute und morgen erstellen
# Stundenpreise in geloggte Variable schreiben um Diagramm von heute und morgen zu erstellen
# Script Ausführung immer um 13:30 (ab 13:00 liegen neue Daten für morgen bei tibber vor)

# *** Konfiguration ***
# eigener Token 
$ID_Variable = IPS_GetProperty($this->InstanceID,"PriceID");                      # ID zur PreisVariablen
$Token       = IPS_GetProperty($this->InstanceID,"Token");                              # eigenen tibber Token einsetzen

#Erst nmal zum Test
$ID_Variable = 29578;                                                                # ID der zu loggenden Variablen eintragen (leere Float-Variable, Cent)
$Token = '9fbTxxJXoc_G8D1FsKM9IM0efo6iexVv9mxMjd7fmUk';                                     # eigenen tibber Token einsetzen



$ID_Archive = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$this->LogMessage("GetTibberPrice : $ID_Variable, $Token",KL_NOTIFY);


# =============== ab hier nichts mehr ändern! ================
$json = '{"query":"{viewer {homes {currentSubscription {priceInfo {tomorrow{ total energy tax startsAt }}}}}}"}';

# Verbindung zur API
$ch = curl_init('https://api.tibber.com/v1-beta/gql');

# Optionen
curl_setopt($ch, CURLOPT_URL, 'https://api.tibber.com/v1-beta/gql');

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Bearer '.$Token));

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

# Antwort holen und Verbindung schließen
$response = curl_exec($ch);
curl_close($ch);

# Daten Decodieren
$data = json_decode($response);
$this->LogMessage("GetTibberPriceResponse : $response",KL_NOTIFY);

$prices_tomorrow = $data->data->viewer->homes[0]->currentSubscription->priceInfo->tomorrow;

# In VAriable Schreiben
$zaehler = 0;
foreach($prices_tomorrow as $price)
{
    $timestamp = strtotime($price->startsAt);
    $total = ($price->total);                               # Wert in ct/kWh
    AC_DeleteVariableData($ID_Archive, $ID_Variable, $timestamp-172800, $timestamp-172800); #    Eventuell schon vorhandene Archivwerte entfernen
    AC_AddLoggedValues($ID_Archive, $ID_Variable, [
    [
    'TimeStamp' => ($timestamp-172800),                     #um zwei Tage zurück versetzen, da in IPS keine zukunftswerte geloggt werden können
    'Value' => $total
    ]
]);
    $zaehler++;
}
AC_ReAggregateVariable ($ID_Archive, $ID_Variable);         #Variable Reaggregieren, damit wieder alles passt


# Ab hier dann die Suche nach dem günstigsten Zeitpunkt
 # $this-> GetLowestPrices($prices_tomorrow,2);
  



}

private function GetLowestPrices($prices, $duration) {
 $duration=1;
 $Lowest         = array();                     #Erstelle ein leeres Array für die Ostanlage
   asort($prices);                              # zuerst aufsteigend nach den Preisen sortieren. Somit sind die günstigsten am Anfang
   array_splice($prices,$duration,24);          #alle unnötigen löschen
   ksort($prices);                              #nun nach der Zeit sortieren

    
}



}    
