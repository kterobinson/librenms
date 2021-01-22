<?php

$multiplier = 1;
$divisor = 10000;
$user_func = 'mw_to_dbm';

#timos_oids = tmnxDigitalDiagMonitorEntry, mibfile mib_dirs
#/includes/discovery/sensors/pre-cache/timos.inc.php

#$pre_cache['timos_oids'] = snmpwalk_cache_multi_oid($device, 'tmnxDigitalDiagMonitorEntry', [], 'TIMETRA-PORT-MIB', 'timos');


foreach ($pre_cache['timos_oids'] as $index => $entry) {
    if (is_numeric($entry['tmnxDDMRxOpticalPower']) && $entry['tmnxDDMRxOpticalPower'] != 0 && $entry['tmnxDDMTxOutputPower'] != 0) {
        $oid = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.21.' . $index;
        $value = round(10 * log10($entry['tmnxDDMRxOpticalPower'] / $divisor), 2);

        $int_ext = $entry['tmnxDDMExternallyCalibrated'];
        if ($int_ext == 'true') {
            $int_ext = 'Ext-Cal';
        } elseif ($int_ext == 'false') {
            $int_ext = 'Int-Cal';
        } else {
            $int_ext = 'Unknown';
        }

        $entPhysicalIndex = $index;
        $entPhysicalIndex_measured = 'ports';

        $limit_low = round(10 * log10($entry['tmnxDDMRxOpticalPowerLowAlarm'] / $divisor), 2);
        $warn_limit_low = round(10 * log10($entry['tmnxDDMRxOpticalPowerLowWarning'] / $divisor), 2);
        $limit = round(10 * log10($entry['tmnxDDMRxOpticalPowerHiAlarm'] / $divisor), 2);
        $warn_limit = round(10 * log10($entry['tmnxDDMRxOpticalPowerHiWarning'] / $divisor), 2);

        $port_descr = get_port_by_index_cache($device['device_id'], str_replace('1.', '', $index));
        $descr = $port_descr['ifName'] . ' RX Power ' . $int_ext;

        discover_sensor($valid['sensor'], 'dbm', $device, $oid, 'rx-' . $index, 'timos', $descr, $divisor, $multiplier, $limit_low, $warn_limit_low, $warn_limit, $limit, $value, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured, $user_func);
    }
    if (is_numeric($entry['tmnxDDMTxOutputPower']) && $entry['tmnxDDMTxOutputPower'] != 0 && $entry['tmnxDDMRxOpticalPower'] != 0) {
        $oid = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.16.' . $index;
        $value = round(10 * log10($entry['tmnxDDMTxOutputPower'] / $divisor), 2);
        $entPhysicalIndex = $index;
        $entPhysicalIndex_measured = 'ports';

        $limit_low = round(10 * log10($entry['tmnxDDMTxOutputPowerLowAlarm'] / $divisor), 2);
        $warn_limit_low = round(10 * log10($entry['tmnxDDMTxOutputPowerLowWarning'] / $divisor), 2);
        $limit = round(10 * log10($entry['tmnxDDMTxOutputPowerHiAlarm'] / $divisor), 2);
        $warn_limit = round(10 * log10($entry['tmnxDDMTxOutputPowerHiWarning'] / $divisor), 2);

        $port_descr = get_port_by_index_cache($device['device_id'], str_replace('1.', '', $index));
        $descr = $port_descr['ifName'] . ' TX Power';

        discover_sensor($valid['sensor'], 'dbm', $device, $oid, 'tx-' . $index, 'timos', $descr, $divisor, $multiplier, $limit_low, $warn_limit_low, $warn_limit, $limit, $value, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured, $user_func);
    }
}

#new code follows for multi-lane optics

#tmnxPortsSFPNumLanes MIB shows number of lanes for the optic

#$pre_cache['timos_lane_oids'] = snmpwalk_cache_multi_oid($device, 'tmnxPortEntry', [], 'TIMETRA-PORT-MIB', 'timos');
# tmnxPortSFPNumLanes is in tmnxPortEntry which is part of tmnxPortTable

#$pre_cache['timos_multilane_oids'] = snmpwalk_cache_multi_oid($device, 'tmnxDDMLaneEntry', [], 'TIMETRA-PORT-MIB', 'timos');
#tmnxDDMLaneRX(TX)OpticalPower is in tmnxDDMLaneEntry which is in table tmnxDDMLaneTable

#snmpbulkwalk -m +ALL -M +/opt/librenms/mibs/nokia -v [snmpversionnumber] -c [communitystring] devicename -On tmnxPortSFPNumLanes

foreach ($pre_cache['timos_lane_oids'] as $index => $entry) {
    if (is_numeric($entry['tmnxPortSFPNumLanes']) && $entry['tmnxPortSFPNumLanes'] > 1) {
        for ($x = 0; $x < $entry['tmnxPortSFPNumLanes']; $x++) {
            $lane = $pre_cache['timos_multilane_oids'][$index . '.' . $x];
            if (is_numeric($lane['tmnxDDMLaneRxOpticalPower']) && $lane['tmnxDDMLaneRxOpticalPower'] != 0 && $lane['tmnxDDMLaneTxOutputPower'] != 0) {
                $oid = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.17.' . $index . '.' . $x;
                $value = round(10 * log10($entry['tmnxDDMLaneRxOpticalPower'] / $divisor), 2);
                $port_descr = get_port_by_index_cache($device['device_id'], str_replace('1.', '', $index));
                $descr = $port_descr['ifName'] . ' RX Power ' . $x;
                
                $limit_low = round(10 * log10($entry['tmnxDDMLaneRxOpticalPwrLowAlarm'] / $divisor), 2);
                $limit = round(10 * log10($entry['tmnxDDMLaneRxOpticalPwrHiAlarm'] / $divisor), 2);
                
                $warn_limit_low = $entry['tmnxDDMLaneRxOpticalPwrLowWarn'] / $divisor;
                $warn_limit = $entry['tmnxDDMLaneRxOpticalPwrHiWarn'] / $divisor;
                
                $current = $lane['tmnxDDMLaneRxOpticalPower'] / $divisor;
                $entPhysicalIndex = $index;
                $entPhysicalIndex_measured = 'ports';
                discover_sensor($valid['sensor'], 'dbm', $device, $oid, 'rx-' . $index . '.' . $x, 'timos', $descr, $divisor, $multiplier, $limit_low, $warn_limit_low, $warn_limit, $limit, $value, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured, $user_func);
            }
            if (is_numeric($lane['tmnxDDMLaneTxOutputPower']) && $lane['tmnxDDMLaneTxOutputPower'] && $lane['tmnxDDMLaneRxOpticalPower']) {
                $oid = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.12.' . $index . '.' . $x;
                $value = round(10 * log10($entry['tmnxDDMLaneTxOutputPower'] / $divisor), 2);
                $port_descr = get_port_by_index_cache($device['device_id'], str_replace('1.', '', $index));
                $descr = $port_descr['ifName'] . ' TX Power ' . $x;
                
                $limit_low = round(10 * log10($entry[' tmnxDDMLaneTxOutputPowerLowAlarm'] / $divisor), 2);
                $limit = round(10 * log10($entry[' tmnxDDMLaneTxOutputPowerHiAlarn'] / $divisor), 2);
                
                $warn_limit_low = $entry[' tmnxDDMLaneTxOutputPowerLowWarn'] / $divisor;
                $warn_limit = $entry['tmnxDDMLaneTxOutputPowerHiWarn'] / $divisor;
                
                $current = $lane['tmnxDDMLaneTxOutputPower'] / $divisor;
                $entPhysicalIndex = $index;
                $entPhysicalIndex_measured = 'ports';
                discover_sensor($valid['sensor'], 'dbm', $device, $oid, 'tx-' . $index . '.' . $x, 'timos', $descr, $divisor, $multiplier, $limit_low, $warn_limit_low, $warn_limit, $limit, $value, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured, $user_func);
            }
        }
    }
}