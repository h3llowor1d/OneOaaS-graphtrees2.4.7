<?php
    /*
    ** Zabbix
    ** Copyright (C) OneOaaS
    ** Author:Zhanhao hao.zhan@oneoaas.com
    **/

    require_once dirname(__FILE__) . '/include/config.inc.php';
    require_once dirname(__FILE__) . '/include/forms.inc.php';

    $page['title'] = _('Data diff');
    $page['file'] = 'report.datadiff.php';
    $page['type'] = detect_page_type(PAGE_TYPE_HTML);
    $page['hist_arg'] = array('itemids');
    $page['css'] = array(
        'jquery-datetimepicker/jquery.datetimepicker.css'
    );
    $page['scripts'] = array(
        'jquery.datetimepicker.full.min.js',
        'flickerfreescreen.js',
        'multiselect.js',
        'emcharts.js',
        'serial.js'
    );//'gtlc.js',

    require_once dirname(__FILE__) . '/include/page_header.php';
    // VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
    $fields = array(
        'itemids'          => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'hostids'          => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groupids'         => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'period'           => array(T_ZBX_INT, O_OPT, P_SYS, null, null),
        'periodInput'      => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'periodSeconds'    => array(T_ZBX_INT, O_OPT, P_SYS, null, null),
        'timestart'        => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'timeend'          => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        // filter
        'filter_set'       => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'filter_rst'       => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'filter_mode'      => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        // ajax
        'filterState'      => array(T_ZBX_INT, O_OPT, P_ACT, null, null),
        'datadiffgroupids' => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'periodInput1'     => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
    );
    check_fields($fields);

    /*
     * Ajax
     */
    if (hasRequest('filterState')) {
        CProfile::update('web.datadiff.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
    }

    if (hasRequest('datadiffgroupids')) {
        if ($_REQUEST['datadiffgroupids']) {
            CProfile::updateArray('web.datadiff.filter.groupids', getRequest('datadiffgroupids', array()), PROFILE_TYPE_STR);
        } else {
            CProfile::deleteIdx('web.datadiff.filter.groupids');
        }
    }

    if (hasRequest('periodInput1')) {
        if ($_REQUEST['periodInput1']) {
            $pattern = "/\d+[y|m|w|d|h]/";
            $inputPeriod = mb_strtolower(getRequest('periodInput1'));
            preg_match_all($pattern, $inputPeriod, $datetime);

            $datetime = reset($datetime);
            if (!$datetime) {
                echo json_encode(array('status' => 1, 'result' => '', 'msg' => 'wrong time'));
            } else {
                $periodSeconds = 0;
                foreach($datetime as $v) {
                    preg_match("/(\d+)([y|m|w|d|h])/", $v, $match);
                    $d = $match[1];
                    $unit = $match[2];
                    switch ($unit) {
                        case "y":
                            $periodSeconds += SEC_PER_YEAR * $d;
                            break;
                        case "m":
                            $periodSeconds += SEC_PER_MONTH * $d;
                            break;
                        case "w":
                            $periodSeconds += SEC_PER_WEEK * $d;
                            break;
                        case "d":
                            $periodSeconds += SEC_PER_DAY * $d;
                            break;
                        case "h":
                            $periodSeconds += SEC_PER_HOUR * $d;
                            break;
                    }
                }

                CProfile::update('web.datadiff.filter.periodInput', getRequest('periodInput1', ''), PROFILE_TYPE_STR);
                CProfile::update('web.datadiff.filter.period', $periodSeconds, PROFILE_TYPE_STR);
                echo json_encode(array('status' => 0, 'result' => $periodSeconds, 'msg' => 'success'));
            }
        }
    }

    if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
        require_once dirname(__FILE__) . '/include/page_footer.php';
        exit;
    }

    $periodSeconds = getRequest('periodSeconds', SEC_PER_HOUR);

    $timestart = getRequest('timestart', array());
    $timeend = getRequest('timeend', array());

    $timestart1 = reset($timestart);
    $timeend1 = reset($timeend);

    $timeline = array();
    if (!empty($timestart1) && !empty($timeend1) && count($timestart) === count($timeend)) {
        for($idx = 0; $idx < count($timestart); $idx++) {
            if (!empty($timestart[$idx]) && !empty($timeend[$idx])) {
                $timeline[] = array(
                    'start'     => strtotime($timestart[$idx]),
                    'end'       => strtotime($timeend[$idx]),
                    'start_str' => $timestart[$idx],
                    'end_str'   => $timeend[$idx]
                );
            }
        }
    }

    /*
     * Filter
     */
    if (hasRequest('filter_set')) {
        CProfile::update('web.datadiff.filter.mode', getRequest('filter_mode', ''), PROFILE_TYPE_STR);
        CProfile::update('web.datadiff.filter.period', $periodSeconds, PROFILE_TYPE_STR);
        CProfile::update('web.datadiff.filter.periodInput', getRequest('periodInput', ''), PROFILE_TYPE_STR);
        CProfile::updateArray('web.datadiff.filter.itemids', getRequest('itemids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.datadiff.filter.hostids', getRequest('hostids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.datadiff.filter.groupids', getRequest('groupids', array()), PROFILE_TYPE_STR);
    } elseif (hasRequest('filter_rst')) {
        DBStart();
        CProfile::delete('web.datadiff.filter.mode');
        CProfile::delete('web.datadiff.filter.period');
        CProfile::delete('web.datadiff.filter.periodInput');
        CProfile::deleteIdx('web.datadiff.filter.itemids');
        CProfile::deleteIdx('web.datadiff.filter.hostids');
        CProfile::deleteIdx('web.datadiff.filter.groupids');
        DBend();
    }

    $filter = array(
        'mode'        => CProfile::get('web.datadiff.filter.mode', 'one'),
        'period'      => CProfile::get('web.datadiff.filter.period', SEC_PER_HOUR),
        'periodInput' => CProfile::get('web.datadiff.filter.periodInput', ''),
        'groupids'    => CProfile::getArray('web.datadiff.filter.groupids', ''),
        'hostids'     => CProfile::getArray('web.datadiff.filter.hostids', ''),
        'itemids'     => CProfile::getArray('web.datadiff.filter.itemids', ''),
    );

    $filterSet = ($filter['groupids'] !== '' && $filter['itemids'] !== '' && !empty($timeline));

    // multiselect host groups
    $multiSelectHostGroupData = array();
    if ($filter['groupids'] !== null) {
        $filterGroups = API::HostGroup()->get(array(
            'output'   => array('groupid', 'name'),
            'groupids' => $filter['groupids']
        ));

        foreach($filterGroups as $group) {
            $multiSelectHostGroupData[] = array(
                'id'   => $group['groupid'],
                'name' => $group['name']
            );
        }
        unset($group);
    }

    // multiselect hosts
    $multiSelectHostData = array();
    if ($filter['hostids']) {
        $filterHosts = API::Host()->get(array(
            'output'  => array('hostid', 'name'),
            'hostids' => $filter['hostids']
        ));

        foreach($filterHosts as $host) {
            $multiSelectHostData[] = array(
                'id'   => $host['hostid'],
                'name' => $host['name']
            );
        }

        unset($host);
    }

    $multiSelectItemData = array();
    if ($filter['itemids']) {
        $filterItems = API::Item()->get(array(
            'output'   => array('itemid', 'key_'),
            'itemids'  => $filter['itemids'],
            'webitems' => true,
            'editable' => true,
            'filter'   => array(
                'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED)
            )
        ));

        foreach($filterItems as $item) {
            $multiSelectItemData[] = array(
                'id'   => $item['itemid'],
                'name' => $item['key_']
            );
        }
        unset($item);
    }

    /*
     * Items data
     */
    $hosts = array();
    $itemkeys = array();
    $dataTable = array();
    $itemExpanded = array();
    if ($filterSet) {
        //get item key_
        $itemKeyTmp = reset($filterItems);
        $itemkey = $itemKeyTmp['key_'];
        unset($itemKeyTmp);

        if ($filter['mode'] == 'one') {
            if (empty($filter['hostids'])) {
                error('select one host please');
            } elseif (empty($filter['itemids'])) {
                error('select one itemkey please');
            } else {
                //get item
                $hostid = reset($filter['hostids']);
                $item = getItem(array('hostid' => $hostid, 'itemkey' => $itemkey));
                if (!empty($item)) {
                    $now = time();
                    $history = $item[0]['history'];
                    $historySeconds = $now - intval($history) * SEC_PER_DAY;
                    $dataFrom = "history";
                    //set table
                    foreach($timeline as $time) {
                        if (intval($time['end']) < $historySeconds) {
                            $dataFrom = "trends";
                        }
                    }
                    unset($time);

                    $itemExpanded = CMacrosResolverHelper::resolveItemNames($item);
                    order_result($itemExpanded, 'name_expanded');

                    //get data
                    foreach($timeline as $time) {
                        $dataTable[] = getItemData($item[0], array(
                            'table' => $dataFrom,
                            'start' => $time['start'],
                            'end'   => $time['end']
                        ));
                    }
                    unset($time);
                } else {
                    error('wrong item key_');
                }
            }
        }

        if ($filter['mode'] == 'many') {
            if (!empty($filter['hostids'])) {
                $hosts = API::Host()->get(array(
                    'output'  => array('hostid', 'name'),
                    'hostids' => $filter['hostids']
                ));
            } elseif (!empty($filter['groupids'])) {
                $hosts = API::Host()->get(array(
                    'output'   => array('hostid', 'name'),
                    'groupids' => $filter['groupids']
                ));
            }

            $timeArr = reset($timeline);
            $items = array();
            if ($hosts) {
                foreach($hosts as $host) {
                    $items[] = getItem(array(
                        'hostid'  => $host['hostid'],
                        'itemkey' => $itemkey
                    ));
                }
                unset($host);
            }
            $now = time();
            $dataFrom = "history";
            $history = 0;

            if ($items) {
                $itemExpanded = CMacrosResolverHelper::resolveItemNames($items[0]);
                order_result($itemExpanded, 'name_expanded');
                //history max
                foreach($items as $item) {
                    if (intval($item[0]['history']) > $history) {
                        $history = intval($item[0]['history']);
                    }
                }
                unset($item);
                $historySeconds = $now - $history * SEC_PER_DAY;
                if (intval($timeArr['start']) < $historySeconds || intval($timeArr['end']) < $historySeconds) {
                    $dataFrom = "trends";
                }

                //get data
                foreach($items as $item) {
                    $dataTable[] = getItemData($item[0], array(
                        'table' => $dataFrom,
                        'start' => $timeArr['start'],
                        'end'   => $timeArr['end']
                    ));
                }
                unset($item);
            }
        }
    }

    /*
     * Display
     */
    $datadiffWidget = new CWidget();
    $datadiffWidget->addHeader(_('Items data diff'));
    $datadiffWidget->addPageHeader(_('DATA DIFF'), get_icon('fullscreen', array('fullscreen' => getRequest('fullscreen', 0))));

    $filterForm = new CForm('get');
    $filterForm->setAttribute('name', 'zbx_report_datadiff');
    $filterForm->setAttribute('id', 'zbx_report_datadiff');

    $filterTable = new CTable(null, 'filter');
    $filterTable->setCellPadding(0);
    $filterTable->setCellSpacing(0);
    $modeOneJs = <<<MODE1
        var addButton = jQuery('#addTime');
        addButton.show();
MODE1;

    $modeManyJs = <<<MODE2
        var addButton = jQuery('#addTime');
        jQuery('.newtr').remove();
        addButton.hide();
MODE2;


    $filterTable->addRow(array(
        new CCol(bold(_('Mode') . ':'), 'label'),
        new CCol(new CDiv(array(
            new CSpan('One Item'),
            new CRadioButton('filter_mode', 'one', null, null, ($filter['mode'] == 'one'), $modeOneJs),
            new CSpan(SPACE . SPACE . SPACE . SPACE),
            new CSpan('Many Items'),
            new CRadioButton('filter_mode', 'many', null, null, ($filter['mode'] == 'many'), $modeManyJs),
            new CSpan("    One Item mode: same item more times;  Many Items mode:same time more items")
        )))
    ));
    $filterTable->addRow(array(
        new CCol(bold(_('Host groups') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'groupids[]',
            'objectName' => 'hostGroup',
            'data'       => $multiSelectHostGroupData,
            'popup'      => array(
                'parameters'  => 'srctbl=host_groups&dstfrm=' . $filterForm->getName() . '&dstfld1=groupids_' . '&srcfld1=groupid&multiselect=0',
                'width'       => 450,
                'height'      => 450,
                'buttonClass' => 'input filter-multiselect-select-button'
            )
        )), 'inputcol'),
    ));

    $filterTable->addRow(array(
        new CCol(bold(_('Hosts') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'hostids[]',
            'objectName' => 'hosts',
            'data'       => $multiSelectHostData,
            'popup'      => array(
                'parameters'  => 'srctbl=hosts&dstfrm=' . $filterForm->getName() . '&dstfld1=hostids_&srcfld1=hostid' . '&real_hosts=1&multiselect=1',
                'width'       => 450,
                'height'      => 450,
                'buttonClass' => 'input filter-multiselect-select-button'
            )
        )), 'inputcol'),
    ));
    $filterTable->addRow(array(
        new CCol(bold(_('Item key') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'itemids[]',
            'objectName' => 'items',
            'data'       => $multiSelectItemData,
            'popup'      => array(
                'parameters'  => 'srctbl=item_key&dstfrm=' . $filterForm->getName() . '&dstfld1=itemids_&srcfld1=groupid&multiselect=0&from=report.datadiff',
                'width'       => 500,
                'height'      => 500,
                'buttonClass' => 'input filter-multiselect-select-button'
            )
        )), 'inputcol'),
    ));

    $comboAction = <<<COMBO
        if(jQuery(this).val() !== '-1'){
            window.period = jQuery(this).val();
            jQuery('#periodInput').val("");
            jQuery('#periodSeconds').val(window.period);
            jQuery('.inputDatePickerStart,.inputDatePickerEnd').val('');
        }
COMBO;

    $periodComboItems = array(
        '-1'                      => 'not selected',
        strval(SEC_PER_HOUR)      => '1h',
        strval(2 * SEC_PER_HOUR)  => '2h',
        strval(3 * SEC_PER_HOUR)  => '3h',
        strval(6 * SEC_PER_HOUR)  => '6h',
        strval(12 * SEC_PER_HOUR) => '12h',
        strval(SEC_PER_DAY)       => '1d',
        strval(SEC_PER_WEEK)      => '7d',
        strval(2 * SEC_PER_WEEK)  => '14d',
        strval(SEC_PER_MONTH)     => '1m',
        strval(3 * SEC_PER_MONTH) => '3m',
        strval(6 * SEC_PER_MONTH) => '6m',
        strval(SEC_PER_YEAR)      => '1y',
    );

    $filterTable->addRow(array(
        new CCol(bold(_('Period') . ':'), 'label'),
        new CCol(array(
            new CComboBox('period', $periodSeconds, $comboAction, $periodComboItems),
            new CSpan(SPACE . SPACE . 'Or' . SPACE . SPACE),
            new CInput('text', 'periodInput', $filter['periodInput']),
            new CSpan(SPACE . SPACE . SPACE . SPACE . 'Tips:1m2w3d4h means 1month 2weeks 3days 4hours'),
            new CInput('hidden', 'periodSeconds', $periodSeconds),
        ))
    ));

    $addAction = <<<Action
        var optionButtonTr = jQuery('#filter_set').parent().parent().parent();
        var tr = jQuery('<tr class="newtr"></tr>');
        tr.append('<td class="label"><strong>Time:</strong></td>');
        tr.append('<td><div><input readonly class="input text inputDatePickerStart" type="text" name="timestart[]" placeholder="start"><span>&nbsp;&nbsp;To&nbsp;&nbsp;</span><input readonly class="input text inputDatePickerEnd" type="text" name="timeend[]" placeholder="end"><span>&nbsp;&nbsp;&nbsp;&nbsp;</span><input onclick="jQuery(this).parent().parent().parent().remove();" type="button" value="-"></div></td>');
        optionButtonTr.before(tr);
        tr.find('.inputDatePickerStart').datetimepicker({
            'onClose':function(t,obj){
                if(jQuery(obj).val() !== ''){
                    var startTime = t.valueOf(),
                        timestamp = startTime + window.period*1000;
                    if(timestamp > new Date().valueOf()){
                        setTimeout(function(){
                            jQuery(obj).val('');
                            jQuery(obj).parent().find('.inputDatePickerEnd').val('');
                            alert("wrong time");
                        },20);
                        return;
                    }
                    var d = new Date(timestamp),
                        dateStr = (d.getFullYear()) + "/" +
                        ((d.getMonth() + 1)>9?"":"0")+ (d.getMonth() + 1) + "/" +
                        (d.getDate()>9?"":"0")+ d.getDate() + " " +
                        (d.getHours()>9?d.getHours():"0"+d.getHours()) + ":" +
                        (d.getMinutes()>9?d.getMinutes():"0"+d.getMinutes());
                    jQuery(obj).parent().find('.inputDatePickerEnd').val(dateStr);
                }
            }
        });

        tr.find('.inputDatePickerEnd').datetimepicker({
            'onClose':function(t,obj){
                if(jQuery(obj).val() !== ''){
                    var endTime = t.valueOf();
                    if(endTime > new Date().valueOf()){
                        setTimeout(function(){
                            jQuery(obj).val('');
                            jQuery(obj).parent().find('.inputDatePickerStart').val('');
                            alert("wrong time");
                        },20);
                        return;
                    }
                    var timestamp = endTime - window.period*1000,
                        d = new Date(timestamp),
                        dateStr = (d.getFullYear()) + "/" +
                        ((d.getMonth() + 1)>9?"":"0")+ (d.getMonth() + 1) + "/" +
                        (d.getDate()>9?"":"0")+ d.getDate() + " " +
                        (d.getHours()>9?d.getHours():"0"+d.getHours()) + ":" +
                        (d.getMinutes()>9?d.getMinutes():"0"+d.getMinutes());
                    jQuery(obj).parent().find('.inputDatePickerStart').val(dateStr);
                }
            }
        });
Action;
    $addButton = new CButton('addTime', "add", $addAction);
    if ($filter['mode'] !== 'one') {
        $addButton->setAttribute('style', 'display:none');
    }
    $tmp1 = reset($timeline);
    if (!empty($tmp1)) {
        foreach($timeline as $k => $time) {
            if ($k === 0) {
                $filterTable->addRow(array(
                    new CCol(bold(_('Time') . ':'), 'label'),
                    new CCol(new CDiv(array(
                        new CInput('text', 'timestart[]', $time['start_str'], 'inputDatePickerStart', null, array(
                            'placeholder' => 'start',
                            'readonly'    => 'readonly'
                        )),
                        new CSpan(SPACE . SPACE . "To" . SPACE . SPACE),
                        new CInput('text', 'timeend[]', $time['end_str'], 'inputDatePickerEnd', null, array(
                            'placeholder' => 'end',
                            'readonly'    => 'readonly'
                        )),
                        new CSpan(SPACE . SPACE . SPACE . SPACE),
                        $addButton
                    )))
                ));
            } else {
                $filterTable->addRow(array(
                    new CCol(bold(_('Time') . ':'), 'label'),
                    new CCol(new CDiv(array(
                        new CInput('text', 'timestart[]', $time['start_str'], 'inputDatePickerStart', null, array(
                            'placeholder' => 'start',
                            'readonly'    => 'readonly'
                        )),
                        new CSpan(SPACE . SPACE . "To" . SPACE . SPACE),
                        new CInput('text', 'timeend[]', $time['end_str'], 'inputDatePickerEnd', null, array(
                            'placeholder' => 'end',
                            'readonly'    => 'readonly'
                        )),
                        new CSpan(SPACE . SPACE . SPACE . SPACE),
                        new CButton('button', '-', "jQuery(this).parent().parent().parent().remove();")
                    )))
                ), "newtr");
            }

        }
    } else {
        $filterTable->addRow(array(
            new CCol(bold(_('Time') . ':'), 'label'),
            new CCol(new CDiv(array(
                new CInput('text', 'timestart[]', '', 'inputDatePickerStart', null, array(
                    'placeholder' => 'start',
                    'readonly'    => 'readonly'
                )),
                new CSpan(SPACE . SPACE . "To" . SPACE . SPACE),
                new CInput('text', 'timeend[]', '', 'inputDatePickerEnd', null, array(
                    'placeholder' => 'end',
                    'readonly'    => 'readonly'
                )),
                new CSpan(SPACE . SPACE . SPACE . SPACE),
                $addButton
            )))
        ));
    }


    $filterButton = new CSubmit('filter_set', _('Filter'), null);
    $filterButton->useJQueryStyle();

    $resetButton = new CSubmit('filter_rst', _('Reset'), 'chkbxRange.clearSelectedOnFilterChange();');
    $resetButton->useJQueryStyle();

    $divButtons = new CDiv(array($filterButton, SPACE, $resetButton));
    $divButtons->setAttribute('style', 'padding: 4px 0px;');

    $filterTable->addRow(new CCol($divButtons, 'controls', 4));
    $filterForm->addItem($filterTable);

    $datadiffWidget->addItem($filterForm);
    $datadiffWidget->addItem(Br());

    if (!empty($timeline) && !empty($dataTable)) {
        $amcharts = new CDiv(null, null, 'chartdiv');
        $amcharts->setAttributes(array(
            'style' => 'width:100%;height:400px;background-color: #FFFFFF;'
        ));
        $datadiffWidget->addItem($amcharts);
        if($filter['mode'] == 'one'){
            $titles = json_encode(array(
                '0' => array(
                    "text" => $itemExpanded[0]['name_expanded'],
                    "size" => "15",
                )
            ));
            foreach($timeline as $k => $time) {
                $tmp[] = array(
                    "balloonText" => "[[time-{$k}]]:[[value]]" . (!empty($itemExpanded[0]['units']) ? "({$itemExpanded[0]['units']})" : ""),
                    "bullet"      => "none",
                    "id"          => "graph_" . $k,
                    "title"       => $itemExpanded[0]['name_expanded'] . ":" . $time['start_str'] . "~" . $time['end_str'],
                    "type"        => "smoothedLine",
                    "valueField"  => "column-" . $k
                );
            }
            $graphs = json_encode($tmp);
            unset($tmp, $k, $time);

            $dataCount = 0;
            foreach($dataTable as $data) {
                if (count($data) > $dataCount) {
                    $dataCount = count($data);
                }
            }

            $dataTmp = array();
            for($i = 0; $i < $dataCount; $i++) {
                $tmp['category'] = $i + 1;
                foreach($timeline as $k => $t) {
                    if (isset($dataTable[$k][$i])) {
                        $tmp["time-" . $k] = date('y/m/d H:i', $dataTable[$k][$i]['clock']);
                        $tmp["column-" . $k] = $dataTable[$k][$i]['value'];
                    } else {
                        //$tmp["time-" . $k] = $i + 1;
                        //$tmp["column-" . $k] = 0;
                    }
                }
                $dataTmp[] = $tmp;
            }
        }else{
            $titles = json_encode(array(
                '0' => array(
                    "text" => $itemkey.":".$timeArr['start_str']."~".$timeArr['end_str'],
                    "size" => "15",
                )
            ));
            foreach($hosts as $k => $host) {
                $tmp[] = array(
                    "balloonText" => "[[time-{$k}]]:[[value]]" . (!empty($itemExpanded[0]['units']) ? "({$itemExpanded[0]['units']})" : ""),
                    "bullet"      => "none",
                    "id"          => "graph_" . $k,
                    "title"       => $host['name']." ".$itemkey,
                    "type"        => "smoothedLine",
                    "valueField"  => "column-" . $k
                );
            }
            $graphs = json_encode($tmp);
            unset($tmp, $k, $time);

            $dataCount = 0;
            foreach($dataTable as $data) {
                if (count($data) > $dataCount) {
                    $dataCount = count($data);
                }
            }

            $dataTmp = array();
            for($i = 0; $i < $dataCount; $i++) {
                for($k = 0; $k < count($dataTable); $k++){
                    if (isset($dataTable[$k][$i])) {
                        $tmp['category'] = date('m-d', $dataTable[$k][$i]['clock']);
                        $tmp["time-" . $k] = date('y/m/d H:i', $dataTable[$k][$i]['clock']);
                        $tmp["column-" . $k] = $dataTable[$k][$i]['value'];
                    } else {
                        //$tmp["time-" . $k] = $i + 1;
                        //$tmp["column-" . $k] = 0;
                    }
                    $dataTmp[] = $tmp;
                }
            }
        }

        $dataProvider = json_encode($dataTmp);
        unset($tmp, $i, $k, $dataTmp, $time);

        $valueAxes = $itemExpanded[0]['name_expanded'];
        $emchartsJs = <<<AMCHARTS
        AmCharts.makeChart("chartdiv",
                {
                    "type": "serial",
                    "categoryField": "category",
                    "categoryAxis": {
                        "gridPosition": "start"
                    },
                    "mouseWheelZoomEnabled": true,
                    "trendLines": [],
                    "graphs": {$graphs},
                    "guides": [],
                    "valueAxes": [
                        {
                            "id": "ValueAxis-1",
                            "title": "{$valueAxes}"
                        }
                    ],
                    "chartScrollbar": {
                        "autoGridCount": true,
                        "graph": "graph_0",
                        "scrollbarHeight": 40
                    },
                    "chartCursor": {
                        "limitToGraph":"graph_0"
                    },
                    "allLabels": [],
                    "balloon": {},
                    "legend": {
                        "useGraphSettings": true
                    },
                    "titles": {$titles},
                    "dataProvider": {$dataProvider}
                }
        );
AMCHARTS;
        insert_js($emchartsJs, true);
    } else {
        $table = new CTableInfo(_('No values found.'));
        $datadiffWidget->addItem($table);
    }


    $submitJs = <<<SUBMIT
        jQuery('#filter_set').on('click',function(){
            var mode = jQuery("input[name=filter_mode]").val();
            var selectedItemsLength = jQuery('#itemids_ .selected ul li').length;
            if(!selectedItemsLength){
                alert("please select one item at least");
                return false;
            }

            if(jQuery('#period').val() == '-1' && jQuery('#periodInput').val() == ''){
                alert("period must be selected");
                return false;
            }

            if(mode == 'one' && selectedItemsLength>1){
                alert("please select only one item");
                return false;
            }

            var timeStartArr = jQuery("input[name='timestart[]']");
            var timeEndArr = jQuery("input[name='timeend[]']");
            for(var i=0;i<timeStartArr.length;i++){
                if(timeStartArr[i].value == ''){
                    alert("datetime can not be null");
                    return false;
                }
            }

            for(var i=0;i<timeEndArr.length;i++){
                if(timeEndArr[i].value == ''){
                    alert("datetime can not be null");
                    return false;
                }
            }

            chkbxRange.clearSelectedOnFilterChange();
        });

SUBMIT;
    $initJs = <<<INIT
        window.period = {$filter['period']};
        jQuery('.inputDatePickerStart').datetimepicker({
            'onClose':function(t,obj){
                if(jQuery(obj).val() !== ''){
                    var startTime = t.valueOf(),
                        timestamp = startTime + window.period*1000;
                    if(timestamp > new Date().valueOf()){
                        setTimeout(function(){
                            jQuery(obj).val('');
                            jQuery(obj).parent().find('.inputDatePickerEnd').val('');
                            alert("wrong time");
                        },20);
                        return;
                    }
                    var d = new Date(timestamp),
                        dateStr = (d.getFullYear()) + "/" +
                        ((d.getMonth() + 1)>9?"":"0")+ (d.getMonth() + 1) + "/" +
                        (d.getDate()>9?"":"0")+ d.getDate() + " " +
                        (d.getHours()>9?d.getHours():"0"+d.getHours()) + ":" +
                        (d.getMinutes()>9?d.getMinutes():"0"+d.getMinutes());
                    jQuery(obj).parent().find('.inputDatePickerEnd').val(dateStr);
                }
            }
        });

        jQuery('.inputDatePickerEnd').datetimepicker({
            'onClose':function(t,obj){
                if(jQuery(obj).val() !== ''){
                    var endTime = t.valueOf();
                    if(endTime > new Date().valueOf()){
                        setTimeout(function(){
                            jQuery(obj).val('');
                            jQuery(obj).parent().find('.inputDatePickerStart').val('');
                            alert("wrong time");
                        },20);
                        return;
                    }
                    var timestamp = endTime - window.period*1000,
                        d = new Date(timestamp),
                        dateStr = (d.getFullYear()) + "/" +
                        ((d.getMonth() + 1)>9?"":"0")+ (d.getMonth() + 1) + "/" +
                        (d.getDate()>9?"":"0")+ d.getDate() + " " +
                        (d.getHours()>9?d.getHours():"0"+d.getHours()) + ":" +
                        (d.getMinutes()>9?d.getMinutes():"0"+d.getMinutes());
                    jQuery(obj).parent().find('.inputDatePickerStart').val(dateStr);
                }
            }
        });

        var onPeriodInputChange = jQuery('#periodInput').on('change',function(){
            if(jQuery(this).val()!== ''){
                jQuery.getJSON('report.datadiff.php?output=ajax',{'periodInput1':jQuery(this).val()},function(data){
                    if(data.status !== 0){
                        jQuery(this).val('');
                        alert(data.msg);
                        return false;
                    }
                    window.period = data.result;
                    jQuery('#period').val('-1');
                    jQuery('#periodSeconds').val(window.period);
                    jQuery('.inputDatePickerStart,.inputDatePickerEnd').val('');
                });
            }
        });

INIT;
    $js = <<<DOMNODE
        var cnt = 0;
        var \$ul = jQuery('#groupids_ div.selected ul');
        //DOMNodeInserted DOMNodeRemoved
        \$ul.on('DOMNodeRemoved',function(e){
            var groupids = [];
            if(e.type === 'DOMNodeRemoved'){
                setTimeout(function(){
                    var \$li = \$ul.find('li');
                    var li_len = \$li.length;
                    if(\$li.length){
                        for(var i=0;i<li_len;i++){
                            groupids.push(jQuery(\$li[i]).attr('data-id'));
                        }
                    }
                    if(groupids.length == 0){
                        jQuery.post('report.datadiff.php?output=ajax',{'datadiffgroupids':0});
                    }else{
                        jQuery.post('report.datadiff.php?output=ajax',{'datadiffgroupids[]':groupids});
                    }
                },20);
            }
        });
DOMNODE;
    $ZBX_PAGE_POST_JS[] = $js;

    insert_js($initJs, true);
    insert_js($submitJs, true);
    $datadiffWidget->show();

    require_once dirname(__FILE__) . '/include/page_footer.php';
