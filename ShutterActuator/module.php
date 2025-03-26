<?php

declare(strict_types=1);

// General functions
require_once __DIR__ . '/../libs/_traits.php';

/**
 * CLASS ShutterActuator
 */
//class ShutterActuator extends IPSModule
class xcomfortshutter extends IPSModule
{
    use DebugHelper;
    use ProfileHelper;
    use VariableHelper;

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // Shutter variables
        $this->RegisterPropertyInteger('ReceiverVariable', 0);
        $this->RegisterPropertyInteger('TransmitterVariable', 0);
        // Position(Level) Variables
        $this->RegisterPropertyFloat('Position0', 0);
        $this->RegisterPropertyFloat('Position50', 50);
        $this->RegisterPropertyFloat('Position85', 85);
        $this->RegisterPropertyFloat('Position100', 100);
    }

    /**
     * Overrides the internal IPSModule::Destroy($id) function
     */
    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Overrides the internal IPSModule::ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        //Register references
        $variable = $this->ReadPropertyInteger('ReceiverVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('TransmitterVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }

        // Profile
        $profile = [
            [0, 'offen', '', -1],
            [50, 'Mitte', '', -1],
            [85, 'unten', '', -1],
            [100, 'geschlossen', '', -1],
        ];
        $this->RegisterProfileInteger('xcomfort.ShutterActuator', 'Jalousie', '', '', 0, 100, 0, $profile);

        // Position
        $this->MaintainVariable('Position', 'Position', VARIABLETYPE_INTEGER, 'xcomfort.ShutterActuator', 1, true);

        // Enable Action / Request Action
        $this->EnableAction('Position');

        // Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('ReceiverVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('ReceiverVariable'), VM_UPDATE);
        }
    }

    /**
     * MessageSink - internal SDK funktion.
     *
     * @param mixed $timeStamp Message timeStamp
     * @param mixed $senderID Sender ID
     * @param mixed $message Message type
     * @param mixed $data data[0] = new value, data[1] = value changed, data[2] = old value, data[3] = timestamp
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
                    // Read changes!
                    if ($data[1] == true) { // OnChange - new value?
                        $this->SendDebug(__FUNCTION__, 'Level: ' . $data[2] . ' => ' . $data[0]);
                        $this->LevelToPosition($data[0]);
                    } else { // OnChange - nothing changed!
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
                $this->SendDebug('RequestAction', 'Ident: '.$ident.' Value: '.$value, 0);
                //$this->SendDebug(__FUNCTION__, 'New position selected: ' . $value, 0);
                $this->MoveShutter($value);
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
            //RequestAction($vid, 1.0);
            RequestAction($vid, 0);
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
            //RequestAction($vid, 0.0);
            RequestAction($vid, 4);
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
            //HM_WriteValueBoolean($pid, 'STOP', true);
            RequestAction($vid, true); // XComfort Stop-Befehl
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
            $this->MoveShutter($position);
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
        //$pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos085 = $this->ReadPropertyFloat('Position85');
        //$pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Level Position - Schalt Position zuweisen
        $pos = 0;
        if ($level == $pos100) {
            $pos = 100;
        } elseif ($level > $pos100 && $level <= $pos085) {
            $pos = 85;
        } elseif ($level > $pos085 && $level <= $pos050) {
            $pos = 50;
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
        //$pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos085 = $this->ReadPropertyFloat('Position85');
        //$pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Position kann manuell, via Voicontrol auch gesetzt worden sein
        // dann normieren auf Profilwerte :(
        if ($position > 0 && $position < 50) {
            $position = 50;
        } elseif ($position > 50 && $position < 85) {
            $position = 85;
        } elseif ($position > 75 && $position < 100) {
            $position = 100;
        }
        // Schalt Position - Level Position - zuweisen
        $level = 0.;
        // Positon übersetzen
        switch ($position) {
            case 0:
                $level = $pos000;
                break;
/*            case 25:
                $level = $pos025;
                break;*/
            case 50:
                $level = $pos050;
                break;
            case 85:
                $level = $pos085;
                break;
  /*          case 99:
                $level = $pos099;
                break;*/
            default:
                $level = $pos100;
        }
        $this->SendDebug(__FUNCTION__, 'Move to position: ' . $position . ', i.e. pevel: ' . $level);
        RequestAction($vid, $level);
    }

    public function MoveShutter(int $position)
    {
        $shutterID = $this->ReadPropertyInteger('TransmitterVariable');
        $currentPosition = floatval($this->Level());

        if ($currentPosition == -1) {
            $this->SendDebug(__FUNCTION__, 'Shutter position could not be retrieved!', 0);
            return;
        }

        $directionDown = $currentPosition < $targetPosition; // true = runter, false = hoch

        // Lade Fahrzeiten aus der form.json
        $times = [];
        if ($directionDown) {
            // Runterfahren (0% → X%)
            $times = [
                0   => 0, // Startpunkt
                50  => $this->ReadPropertyFloat('time_down_50'),
                85  => $this->ReadPropertyFloat('time_down_85'),
                100 => $this->ReadPropertyFloat('time_down_100')
            ];
        } else {
            // Hochfahren (100% → X%)
            $times = [
                100 => 0, // Startpunkt
                85  => $this->ReadPropertyFloat('time_up_85'),
                50  => $this->ReadPropertyFloat('time_up_50'),
                0   => $this->ReadPropertyFloat('time_up_0')
            ];
        }

        // Berechnung der tatsächlichen Fahrzeit
        $driveTime = $this->calculateDriveTime($currentPosition, $targetPosition, $times);

        if ($driveTime > 0) {
            $this->SendDebug(__FUNCTION__, "Moving shutter to $targetPosition%. Estimated time: $driveTime s", 0);
            RequestAction($shutterID, $directionDown); // Bewegung starten

            IPS_Sleep($driveTime * 1000); // Wartezeit für die Bewegung

            RequestAction($shutterID, false); // Stoppen nach der berechneten Zeit
            $this->SendDebug(__FUNCTION__, "Shutter movement stopped.", 0);
        } else {
            $this->SendDebug(__FUNCTION__, "No movement needed. Current position is already at $targetPosition%.", 0);
        }
    }

    // Funktion zur Berechnung der Fahrzeit mit den vorgegebenen Messpunkten
    private function calculateDriveTime(float $from, float $to, array $timeTable): float
    {
        if ($from == $to) return 0; // Keine Bewegung nötig

        // Interpolation für Start- und Zielposition
        $fromTime = $this->interpolateTime($from, $timeTable);
        $toTime   = $this->interpolateTime($to, $timeTable);

        return abs($toTime - $fromTime);
    }

    // Interpolation für eine beliebige Position basierend auf den bekannten Messwerten
    private function interpolateTime(float $position, array $timeTable): float
    {
        $keys = array_keys($timeTable);
        sort($keys);

        foreach ($keys as $index => $key) {
            if ($position == $key) {
                return $timeTable[$key];
            } elseif ($position < $key) {
                $prevKey = $keys[$index - 1] ?? $key;
                $prevTime = $timeTable[$prevKey];
                $currentTime = $timeTable[$key];

                // Lineare Interpolation
                $ratio = ($position - $prevKey) / ($key - $prevKey);
                return $prevTime + $ratio * ($currentTime - $prevTime);
            }
        }

        return end($timeTable); // Falls Position über max. Wert hinausgeht
    }


}
