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
        // Fahrzeiten für Hoch- und Runterfahren
        $this->RegisterPropertyFloat('time_up_85', 0);
        $this->RegisterPropertyFloat('time_up_50', 0);
        $this->RegisterPropertyFloat('time_up_0', 0);
        $this->RegisterPropertyFloat('time_down_50', 0);
        $this->RegisterPropertyFloat('time_down_85', 0);
        $this->RegisterPropertyFloat('time_down_100', 0);
        $this->RegisterPropertyFloat('time_full_move_extra', 0);
        $this->RegisterPropertyFloat('time_start_delay', 0);

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

         // Delete all references
         foreach ($this->GetReferenceList() as $referenceID) {
             $this->UnregisterReference($referenceID);
         }

         // Delete all messages
         foreach ($this->GetMessageList() as $senderID => $messages) {
             foreach ($messages as $message) {
                 $this->UnregisterMessage($senderID, $message);
             }
         }

         // Register Receiver/Transmitter references
         $receiverID = $this->ReadPropertyInteger('ReceiverVariable');
         if (IPS_VariableExists($receiverID)) {
              $this->RegisterReference($receiverID);
              $this->RegisterMessage($receiverID, VM_UPDATE);

              // Initialwert setzen
              $current = GetValue($receiverID);
              $this->SetValueInteger('Position', (int)$current);
          }

         $transmitterID = $this->ReadPropertyInteger('TransmitterVariable');

         if (IPS_VariableExists($receiverID)) {
             $this->RegisterReference($receiverID);
             $this->RegisterMessage($receiverID, VM_UPDATE);
         }

         if (IPS_VariableExists($transmitterID)) {
             $this->RegisterReference($transmitterID);
         }

         // Register Profile for Shutter Position
         $profile = [
             [0, 'offen', '', -1],
             [26, 'Mitte', '', -1],
             [76, 'unten', '', -1],
             [91, 'geschlossen', '', -1],
         ];
         $this->RegisterProfileInteger('xcomfort.ShutterActuator', 'Jalousie', '', '', 0, 100, 0, $profile);

         // Maintain main Position variable
         $this->MaintainVariable('Position', 'Position', VARIABLETYPE_INTEGER, 'xcomfort.ShutterActuator', 1, true);
         $this->EnableAction('Position');

         // Optionale Trigger für Zeitwerte (falls du später reagieren willst)
         $timeProps = [
             'time_up_0', 'time_up_50', 'time_up_85',
             'time_down_50', 'time_down_85', 'time_down_100'
         ];

         foreach ($timeProps as $propName) {
             $value = $this->ReadPropertyFloat($propName);
             // Hier optional: Validierung oder Logik für Trigger
             // Beispiel: $this->SendDebug(__FUNCTION__, "$propName: $value", 0);
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
         switch ($message) {
             case VM_UPDATE:
                 $receiverID = $this->ReadPropertyInteger('ReceiverVariable');

                 if ($senderID != $receiverID) {
                     $this->SendDebug(__FUNCTION__, 'SenderID: ' . $senderID . ' unknown!');
                     return;
                 }

                 if ($data[1] === true) { // OnChange mit neuem Wert
                     $newLevel = $data[0];
                     $this->SendDebug(__FUNCTION__, 'Level changed: ' . $data[2] . ' → ' . $newLevel);
                     $this->SetValueInteger('Position', (int)$newLevel); // Position-Variable im Modul setzen
                 } else {
                     $this->SendDebug(__FUNCTION__, 'Level unchanged – no update needed.');
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
                $this->Position($value);
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
            RequestAction($vid, 2); // XComfort Stop-Befehl
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
             return floatval($level); // ⬅️ wichtig!
         } else {
             $this->SendDebug(__FUNCTION__, 'Variable to control the shutter not set!');
             return -1;
         }
     }
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Position($id, $position);
     */
     public function Position(int $value)
     {
         $vid = $this->ReadPropertyInteger('TransmitterVariable');

         if ($vid != 0) {
             $this->SendDebug(__FUNCTION__, 'Requested symbolic position: ' . $value . '%');

             // Symbolische Anzeige-Werte (aus dem Profil) auf reale Zielpositionen mappen
             switch ($value) {
                 case 0:
                     $realPosition = 0;
                     break;
                 case 26:
                     $realPosition = 50;
                     break;
                 case 76:
                     $realPosition = 85;
                     break;
                 case 91:
                     $realPosition = 100;
                     break;
                 default:
                     $realPosition = $value; // Falls manuell ein direkter Prozentwert kommt
                     break;
             }

             $this->SendDebug(__FUNCTION__, 'Mapped to real position: ' . $realPosition . '%');
             $this->MoveShutter($realPosition);
         } else {
             $this->SendDebug(__FUNCTION__, 'TransmitterVariable not set!');
         }
     }


    public function MoveShutter(float $targetPosition)
    {
        $currentPosition = floatval($this->Level());

        if (abs($currentPosition - $targetPosition) < 0.1) {
            $this->SendDebug(__FUNCTION__, "No movement needed. Current position ($currentPosition%) is close to $targetPosition%", 0);
            return;
        }

        $directionDown = $currentPosition < $targetPosition;

        $times = $directionDown ? [
            0   => 0,
            50  => $this->ReadPropertyFloat('time_down_50'),
            85  => $this->ReadPropertyFloat('time_down_85'),
            100 => $this->ReadPropertyFloat('time_down_100')
        ] : [
            100 => 0,
            85  => $this->ReadPropertyFloat('time_up_85'),
            50  => $this->ReadPropertyFloat('time_up_50'),
            0   => $this->ReadPropertyFloat('time_up_0')
        ];

        // Zeit berechnen
        $driveTime = $this->calculateDriveTime($currentPosition, $targetPosition, $times);

        // Zusätzliche Zeit beim vollen Öffnen/Schließen
        if (in_array((int)$targetPosition, [0, 100])) {
            $extraFullTime = $this->ReadPropertyFloat('time_full_move_extra');
            $driveTime += $extraFullTime;
            $this->SendDebug(__FUNCTION__, "Added $extraFullTime sec for full open/close", 0);
        }

        // Trägheitszeit beim Losfahren
        $startDelay = $this->ReadPropertyFloat('time_start_delay');
        $driveTime += $startDelay;
        $this->SendDebug(__FUNCTION__, "Added $startDelay sec start delay", 0);
        if ($driveTime <= 0) {
            $this->SendDebug(__FUNCTION__, "Calculated drive time is 0. No movement.", 0);
            return;
        }

        if ($directionDown) {
            $this->SendDebug(__FUNCTION__, "Shutter moving down to $targetPosition% for $driveTime seconds", 0);
            $this->Down();
        } else {
            $this->SendDebug(__FUNCTION__, "Shutter moving up to $targetPosition% for $driveTime seconds", 0);
            $this->Up();
        }

        IPS_Sleep($driveTime * 1000);
        $this->Stop();
        $this->SendDebug(__FUNCTION__, "Shutter movement stopped", 0);
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

    public function CalibrateDown(float $duration)
     {
         $this->SendDebug(__FUNCTION__, "Starte Kalibrierung: Runterfahrt ({$duration} sec)", 0);

         $reverseTime = $duration + 2;
         $this->SendDebug(__FUNCTION__, "Vorherige Hochfahrt ({$reverseTime} sec)", 0);
         $this->Up();
         IPS_Sleep($reverseTime * 1000);
         $this->Stop();

         $this->Down();
         IPS_Sleep($duration * 1000);
         $this->Stop();

         $end = floatval($this->Level());
         $this->SendDebug(__FUNCTION__, "Endposition: {$end}%", 0);

         if ($end <= 0) {
             $this->SendDebug(__FUNCTION__, "Shutter hat sich nicht bewegt.", 0);
             return;
         }

         $time_50 = $duration * (50 / $end);
         $time_85 = $duration * (85 / $end);
         $time_100 = $duration;

         IPS_SetProperty($this->InstanceID, 'time_down_50', round($time_50, 2));
         IPS_SetProperty($this->InstanceID, 'time_down_85', round($time_85, 2));
         IPS_SetProperty($this->InstanceID, 'time_down_100', round($time_100, 2));
         IPS_ApplyChanges($this->InstanceID);

         $this->SendDebug(__FUNCTION__, "Gespeicherte Zeiten (0 → X):", 0);
         $this->SendDebug(__FUNCTION__, "0 → 50% = " . round($time_50, 2) . " s", 0);
         $this->SendDebug(__FUNCTION__, "0 → 85% = " . round($time_85, 2) . " s", 0);
         $this->SendDebug(__FUNCTION__, "0 → 100% = " . round($time_100, 2) . " s", 0);

         echo "✅ Kalibrierung abgeschlossen (Runterfahrt). Neue Zeiten gespeichert.";
     }

     public function CalibrateUp(float $duration)
     {
       echo "Kalibrierung wird gestartet...";
         $this->SendDebug(__FUNCTION__, "Starte Kalibrierung: Hochfahrt ({$duration} sec)", 0);

         $reverseTime = $duration + 2;
         $this->SendDebug(__FUNCTION__, "Vorherige Runterfahrt ({$reverseTime} sec)", 0);
         $this->Down();
         IPS_Sleep($reverseTime * 1000);
         $this->Stop();

         $this->Up();
         IPS_Sleep($duration * 1000);
         $this->Stop();

         $end = floatval($this->Level());
         $this->SendDebug(__FUNCTION__, "Endposition: {$end}%", 0);

         if ($end >= 100) {
             $this->SendDebug(__FUNCTION__, "Shutter hat sich nicht bewegt.", 0);
             return;
         }

         $delta = 100 - $end;

         $time_85 = $duration * ((100 - 85) / $delta);
         $time_50 = $duration * ((100 - 50) / $delta);
         $time_0  = $duration;

         IPS_SetProperty($this->InstanceID, 'time_up_85', round($time_85, 2));
         IPS_SetProperty($this->InstanceID, 'time_up_50', round($time_50, 2));
         IPS_SetProperty($this->InstanceID, 'time_up_0', round($time_0, 2));
         IPS_ApplyChanges($this->InstanceID);

         $this->SendDebug(__FUNCTION__, "Gespeicherte Zeiten (100 → X):", 0);
         $this->SendDebug(__FUNCTION__, "100 → 85% = " . round($time_85, 2) . " s", 0);
         $this->SendDebug(__FUNCTION__, "100 → 50% = " . round($time_50, 2) . " s", 0);
         $this->SendDebug(__FUNCTION__, "100 → 0%  = " . round($time_0, 2) . " s", 0);

         echo "✅ Kalibrierung abgeschlossen (Hochfahrt). Neue Zeiten gespeichert.";
     }
}
