<?php

declare(strict_types=1);

// Allgemeine Funktionen
require_once __DIR__ . '/../libs/_traits.php';

// CLASS ShutterActuator
class ShutterActuator extends IPSModule
{
    use ProfileHelper;
    use DebugHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // Shutter variables
        $this->RegisterPropertyInteger('ReceiverVariable', 0);
        $this->RegisterPropertyInteger('TransmitterVariable', 0);
        // Position(Level) Variables
        $this->RegisterPropertyFloat('Position0', 1.0);
        $this->RegisterPropertyFloat('Position25', 0.85);
        $this->RegisterPropertyFloat('Position50', 0.70);
        $this->RegisterPropertyFloat('Position75', 0.50);
        $this->RegisterPropertyFloat('Position99', 0.25);
        $this->RegisterPropertyFloat('Position100', 0.0);
    }

    public function ApplyChanges()
    {
        // Level Trigger
        if ($this->ReadPropertyInteger('ReceiverVariable') != 0) {
            $this->UnregisterMessage($this->ReadPropertyInteger('ReceiverVariable'), VM_UPDATE);
        }
        // Never delete this line!
        parent::ApplyChanges();
        // Profiles
        $association = [
            [0, 'Auf', '', 0x00FF00],
            [25, '25 %%', '', 0x00FF00],
            [50, '50 %%', '', 0x00FF00],
            [75, '75 %%', '', 0x00FF00],
            [99, '99 %%', '', 0x00FF00],
            [100, 'Zu', '', -1],
        ];
        $this->RegisterProfile(vtInteger, 'HM.ShutterActuator', 'Jalousie', '', '', 0, 100, 0, 0, $association);
        // Position
        $this->MaintainVariable('Position', 'Position', vtInteger, 'HM.ShutterActuator', 1, true);
        // Enable Action / Request Action
        $this->EnableAction('Position');
        // Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('ReceiverVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('ReceiverVariable'), VM_UPDATE);
        }
    }

    /**
     * Internal SDK funktion.
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     */
    public function MessageSink($timeStamp, $senderID, $message, $data)
    {
        //$this->SendDebug(__FUNCTION__, 'SenderId: '.$senderID.' Data: '.print_r($data, true), 0);
        switch ($message) {
            case VM_UPDATE:
                // ReceiverVariable
                if ($senderID != $this->ReadPropertyInteger('ReceiverVariable')) {
                    $this->SendDebug(__FUNCTION__, 'SenderID: ' . $senderID . ' unknown!');
                } else {
                    // Aenderungen auslesen
                    if ($data[1] == true) { // OnChange - neuer Wert?
                        $this->SendDebug(__FUNCTION__, 'Level: ' . $data[2] . ' => ' . $data[0]);
                        $this->LevelToPosition($data[0]);
                    } else { // OnChange - keine Zustandsaenderung
                        $this->SendDebug(__FUNCTION__, 'Level unchanged - no change in value!');
                    }
                }
                break;
          }
    }

    /**
     * RequestAction (SDK function).
     *
     * @param string $ident Ident.
     * @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        //$this->SendDebug('RequestAction', 'Ident: '.$ident.' Value: '.$value, 0);
        switch ($ident) {
            case 'Position':
                $this->SendDebug(__FUNCTION__, 'New position selected: ' . $value, 0);
                $this->PositionToLevel($value);
                break;
            default:
                throw new Exception('Invalid ident!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Up($id);
     */
    public function Up()
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $this->SendDebug(__FUNCTION__, 'Raise shutter!');
            RequestAction($vid, 1.0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Down($id);
     */
    public function Down()
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $this->SendDebug(__FUNCTION__, 'Lower shutter!');
            RequestAction($vid, 0.0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Stop($id);
     */
    public function Stop()
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $pid = IPS_GetParent($vid);
            $this->SendDebug(__FUNCTION__, 'Shutter stopped!');
            HM_WriteValueBoolean($pid, 'STOP', true);
        //RequestAction($vid, true);
        } else {
            $this->SendDebug(__FUNCTION__, 'VVariable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Level($id);
     *
     * @return float The actual internal level (position).
     */
    public function Level()
    {
        $vid = $this->ReadPropertyInteger('ReceiverVariable');
        if ($vid != 0) {
            $level = GetValue($vid);
            $this->SendDebug(__FUNCTION__, 'Current internal position is: ' . $level);
            return sprintf('%.2f', $level);
        } else {
            $this->SendDebug(__FUNCTION__, 'Variable to control the shutter not set!');
            return '-1';
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Position($id, $position);
     */
    public function Position(int $position)
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $this->SendDebug(__FUNCTION__, 'Move roller shutter to position ' . $position . '%!');
            $this->PositionToLevel($position);
        } else {
            $this->SendDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * Map Level to Position.
     *
     * @param float $level Shutter level value
     */
    private function LevelToPosition(float $level)
    {
        // Mapping values
        $pos000 = $this->ReadPropertyFloat('Position0');
        $pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos075 = $this->ReadPropertyFloat('Position75');
        $pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Level Position - Schalt Position zuweisen
        $pos = 0;
        if ($level == $pos100) {
            $pos = 100;
        } elseif ($level > $pos100 && $level <= $pos099) {
            $pos = 99;
        } elseif ($level > $pos099 && $level <= $pos075) {
            $pos = 75;
        } elseif ($level > $pos075 && $level <= $pos050) {
            $pos = 50;
        } elseif ($level > $pos050 && $level <= $pos025) {
            $pos = 25;
        } else {
            $pos = 0;
        }
        // Zuordnen
        $this->SendDebug(__FUNCTION__, 'Level ' . $level . ' reached, i.e. position: ' . $pos);
        $this->SetValueInteger('Position', $pos);
    }

    /**
     * Map Position to Level.
     *
     * @param int $level Shutter level value
     */
    private function PositionToLevel(int $position)
    {
        // Level Variable
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        // Mapping values
        $pos000 = $this->ReadPropertyFloat('Position0');
        $pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos075 = $this->ReadPropertyFloat('Position75');
        $pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Position kann manuell, via Voicontrol auch gesetzt worden sein
        // dann normieren auf Profilwerte :(
        if ($position > 0 && $position < 25) {
            $position = 25;
        } elseif ($position > 25 && $position < 50) {
            $position = 50;
        } elseif ($position > 50 && $position < 75) {
            $position = 75;
        } elseif ($position > 75 && $position < 100) {
            $position = 99;
        }
        // Schalt Position - Level Position - zuweisen
        $level = 0.;
        // Positon übersetzen
        switch ($position) {
            case 0:
                $level = $pos000;
                break;
            case 25:
                $level = $pos025;
                break;
            case 50:
                $level = $pos050;
                break;
            case 75:
                $level = $pos075;
                break;
            case 99:
                $level = $pos099;
                break;
            default:
                $level = $pos100;
        }
        $this->SendDebug(__FUNCTION__, 'Move to position: ' . $position . ', i.e. pevel: ' . $level);
        RequestAction($vid, $level);
    }

    /**
     * Update a boolean value.
     *
     * @param string $ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     */
    private function SetValueBoolean(string $ident, bool $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueBoolean($id, $value);
    }

    /**
     * Update a string value.
     *
     * @param string $ident Ident of the string variable
     * @param string $value Value of the string variable
     */
    private function SetValueString(string $ident, string $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueString($id, $value);
    }

    /**
     * Update a integer value.
     *
     * @param string $ident Ident of the integer variable
     * @param int    $value Value of the integer variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }
}
