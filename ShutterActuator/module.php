<?php

declare(strict_types=1);

/** Generell funktions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\ShutterActuator\DebugHelper;
use Wilkware\ShutterActuator\VariableHelper;

/**
 * CLASS ShutterActuator
 */
class ShutterActuator extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use DebugHelper;
    use VariableHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Min IPS Object ID */
    private const IPS_MIN_ID = 10000;

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // Shutter variables
        $this->RegisterPropertyInteger('ReceiverVariable', 1);
        $this->RegisterPropertyInteger('TransmitterVariable', 1);

        // Position(Level) Variables
        $this->RegisterPropertyFloat('Position0', 1.0);
        $this->RegisterPropertyFloat('Position25', 0.85);
        $this->RegisterPropertyFloat('Position50', 0.70);
        $this->RegisterPropertyFloat('Position75', 0.50);
        $this->RegisterPropertyFloat('Position99', 0.25);
        $this->RegisterPropertyFloat('Position100', 0.0);

        // Advanced variables
        $this->RegisterPropertyInteger('BlockingVariable', 1);
        $this->RegisterPropertyBoolean('BlockingUpCheck', true);
        $this->RegisterPropertyBoolean('BlockingDownCheck', true);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
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
        $variable = $this->ReadPropertyInteger('BlockingVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }

        // Position
        $this->MaintainVariable('Position', 'Position', VARIABLETYPE_INTEGER, 'HM.ShutterActuator', 1, true);

        // Enable Action / Request Action
        $this->EnableAction('Position');

        // Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('ReceiverVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('ReceiverVariable'), VM_UPDATE);
        }
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param int   $timestamp Continuous counter timestamp
     * @param int   $sender    Sender ID
     * @param int   $message   ID of the message
     * @param array{0:mixed,1:bool,2:mixed,3:int} $data Data of the message
     *
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        //$this->LogDebug(__FUNCTION__, 'SenderId: '.$sender.' Data: '.print_r($data, true), 0);
        switch ($message) {
            case VM_UPDATE:
                // ReceiverVariable
                if ($sender != $this->ReadPropertyInteger('ReceiverVariable')) {
                    $this->LogDebug(__FUNCTION__, 'SenderID: ' . $sender . ' unknown!');
                } else {
                    // Read changes!
                    if ($data[1] == true) { // OnChange - new value?
                        $this->LogDebug(__FUNCTION__, 'Level: ' . $data[2] . ' => ' . $data[0]);
                        $this->LevelToPosition($data[0]);
                    } else { // OnChange - nothing changed!
                        $this->LogDebug(__FUNCTION__, 'Level unchanged - no change in value!');
                    }
                }
                break;
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     *
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        //$this->LogDebug('RequestAction', 'Ident: '.$ident.' Value: '.$value);
        switch ($ident) {
            case 'Position':
                $this->LogDebug(__FUNCTION__, 'New position selected: ' . $value);
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
     * 
     * @return void
     */
    public function Up(): void
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $this->LogDebug(__FUNCTION__, 'Raise shutter!');
            RequestAction($vid, 1.0);
        } else {
            $this->LogDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Down($id);
     * 
     * @return void
     */
    public function Down(): void
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if ($vid != 0) {
            $this->LogDebug(__FUNCTION__, 'Lower shutter!');
            RequestAction($vid, 0.0);
        } else {
            $this->LogDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Stop($id);
     * 
     * @return void
     */
    public function Stop(): void
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if (($vid >= self::IPS_MIN_ID) && IPS_VariableExists($vid)) {
            $pid = IPS_GetParent($vid);
            $this->LogDebug(__FUNCTION__, 'Shutter stopped!');
            HM_WriteValueBoolean($pid, 'STOP', true);
            //RequestAction($vid, true);
        } else {
            $this->LogDebug(__FUNCTION__, 'VVariable to control the shutter not set!');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Level($id);
     *
     * @return float|int The actual internal level (position), or -1 if the variable is not set.
     */
    public function Level(): float|int
    {
        $vid = $this->ReadPropertyInteger('ReceiverVariable');
        if (($vid >= self::IPS_MIN_ID) && IPS_VariableExists($vid)) {
            $level = GetValue($vid);
            $this->LogDebug(__FUNCTION__, 'Current internal position is: ' . $level);
            return (float)sprintf('%.2f', $level);
        } else {
            $this->LogDebug(__FUNCTION__, 'Variable to control the shutter not set!');
            return -1;
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * TSA_Position($id, $position);
     * 
     * @param int $position Position value to set (0-100)
     * 
     * @return void
     */
    public function Position(int $position): void
    {
        $vid = $this->ReadPropertyInteger('TransmitterVariable');
        if (($vid >= self::IPS_MIN_ID) && IPS_VariableExists($vid)) {
            $this->LogDebug(__FUNCTION__, 'Move roller shutter to position ' . $position . '%!');
            $this->PositionToLevel($position);
        } else {
            $this->LogDebug(__FUNCTION__, 'Variable to control the shutter not set!');
        }
    }

    /**
     * Map Level to Position.
     *
     * @param float $level Shutter level value
     * 
     * @return void
     */
    private function LevelToPosition(float $level): void
    {
        // Mapping values
        $pos000 = $this->ReadPropertyFloat('Position0');
        $pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos075 = $this->ReadPropertyFloat('Position75');
        $pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Level Position assign
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
        // Mapping
        $this->LogDebug(__FUNCTION__, 'Level ' . $level . ' reached, i.e. position: ' . $pos);
        $this->SetValueInteger('Position', $pos);
    }

    /**
     * Map position to Level.
     *
     * @param int $position Shutter position value
     * 
     * @return void
     */
    private function PositionToLevel(int $position): void
    {
        // Level Variable
        $tid = $this->ReadPropertyInteger('TransmitterVariable');
        $bid = $this->ReadPropertyInteger('BlockingVariable');
        // Mapping values
        $pos000 = $this->ReadPropertyFloat('Position0');
        $pos025 = $this->ReadPropertyFloat('Position25');
        $pos050 = $this->ReadPropertyFloat('Position50');
        $pos075 = $this->ReadPropertyFloat('Position75');
        $pos099 = $this->ReadPropertyFloat('Position99');
        $pos100 = $this->ReadPropertyFloat('Position100');
        // Position can also be set manually via Voicontrol
        // then normalise to profile values :(
        if ($position > 0 && $position < 25) {
            $position = 25;
        } elseif ($position > 25 && $position < 50) {
            $position = 50;
        } elseif ($position > 50 && $position < 75) {
            $position = 75;
        } elseif ($position > 75 && $position < 100) {
            $position = 99;
        }
        // Switch position - Level position - assign
        $level = 0.;
        // Translate position
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
        $this->LogDebug(__FUNCTION__, 'Move to position: ' . $position . ', i.e. level: ' . $level);
        // Check Blocking
        if (($bid >= self::IPS_MIN_ID) && IPS_VariableExists($bid)) {
            if (boolval(GetValue($bid))) {
                $current = $this->GetValue('Position');
                // Level goes Up
                if ($position > $current) {
                    $doit = $this->ReadPropertyBoolean('BlockingUpCheck');
                    if ($doit) {
                        $this->LogDebug(__FUNCTION__, 'Blocking to move up to new position: ' . $position . ', current: ' . $current);
                        return;
                    }
                }
                // Level goes Down
                if ($position < $current) {
                    $doit = $this->ReadPropertyBoolean('BlockingDownCheck');
                    if ($doit) {
                        $this->LogDebug(__FUNCTION__, 'Blocking to move down to new position: ' . $position . ', current: ' . $current);
                        return;
                    }
                }
            }
        }
        // Send Action
        if (($tid >= self::IPS_MIN_ID) && IPS_VariableExists($tid)) {
            RequestAction($tid, $level);
        }
    }
}
