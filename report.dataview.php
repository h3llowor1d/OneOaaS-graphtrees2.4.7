<?php
    /*
    ** Zabbix
    ** Copyright (C) 2001-2015 Zabbix SIA
    **
    ** This program is free software; you can redistribute it and/or modify
    ** it under the terms of the GNU General Public License as published by
    ** the Free Software Foundation; either version 2 of the License, or
    ** (at your option) any later version.
    **
    ** This program is distributed in the hope that it will be useful,
    ** but WITHOUT ANY WARRANTY; without even the implied warranty of
    ** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    ** GNU General Public License for more details.
    **
    ** You should have received a copy of the GNU General Public License
    ** along with this program; if not, write to the Free Software
    ** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
    **/


    require_once dirname(__FILE__) . '/include/config.inc.php';
    require_once dirname(__FILE__) . '/include/forms.inc.php';

    if (hasRequest('action') && getRequest('action') == 'host.export' && hasRequest('hosts')) {
        $page['file'] = 'zbx_export_hosts.xml';
        $page['type'] = detect_page_type(PAGE_TYPE_XML);

        $exportData = true;
    } else {
        $page['title'] = _('Data view');
        $page['file'] = 'report.dataview.php';
        $page['type'] = detect_page_type(PAGE_TYPE_HTML);
        $page['hist_arg'] = array('groupid');
        $page['scripts'] = array('class.calendar.js', 'gtlc.js', 'flickerfreescreen.js', 'multiselect.js');

        $exportData = false;
    }

    require_once dirname(__FILE__) . '/include/page_header.php';
    // VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
    $fields = array(
        'hosts'             => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groups'            => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'hostids'           => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groupids'          => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'itemids'           => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'period'            => array(T_ZBX_INT, O_OPT, P_SYS, null, null),
        'stime'             => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        //'applications'      => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groupid'           => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'hostid'            => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, 'isset({form}) && {form} == "update"'),
        'host'              => array(
            T_ZBX_STR,
            O_OPT,
            null,
            NOT_EMPTY,
            'isset({add}) || isset({update})',
            _('Host name')
        ),
        'visiblename'       => array(T_ZBX_STR, O_OPT, null, null, 'isset({add}) || isset({update})'),
        'description'       => array(T_ZBX_STR, O_OPT, null, null, null),
        'proxy_hostid'      => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'status'            => array(
            T_ZBX_INT,
            O_OPT,
            null,
            IN(array(HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED)),
            null
        ),
        'newgroup'          => array(T_ZBX_STR, O_OPT, null, null, null),
        'interfaces'        => array(
            T_ZBX_STR,
            O_OPT,
            null,
            NOT_EMPTY,
            'isset({add}) || isset({update})',
            _('Agent or SNMP or JMX or IPMI interface')
        ),
        'mainInterfaces'    => array(T_ZBX_INT, O_OPT, null, DB_ID, null),
        'templates'         => array(T_ZBX_INT, O_OPT, null, DB_ID, null),
        'add_template'      => array(T_ZBX_STR, O_OPT, null, null, null),
        'add_templates'     => array(T_ZBX_INT, O_OPT, null, DB_ID, null),
        'templates_rem'     => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'clear_templates'   => array(T_ZBX_INT, O_OPT, null, DB_ID, null),
        'ipmi_authtype'     => array(T_ZBX_INT, O_OPT, null, BETWEEN(-1, 6), null),
        'ipmi_privilege'    => array(T_ZBX_INT, O_OPT, null, BETWEEN(0, 5), null),
        'ipmi_username'     => array(T_ZBX_STR, O_OPT, null, null, null),
        'ipmi_password'     => array(T_ZBX_STR, O_OPT, null, null, null),
        'mass_replace_tpls' => array(T_ZBX_STR, O_OPT, null, null, null),
        'mass_clear_tpls'   => array(T_ZBX_STR, O_OPT, null, null, null),
        'inventory_mode'    => array(
            T_ZBX_INT,
            O_OPT,
            null,
            IN(HOST_INVENTORY_DISABLED . ',' . HOST_INVENTORY_MANUAL . ',' . HOST_INVENTORY_AUTOMATIC),
            null
        ),
        'host_inventory'    => array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, null, null),
        'macros_rem'        => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'macros'            => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'macro_new'         => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, 'isset({macro_add})'),
        'value_new'         => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, 'isset({macro_add})'),
        'macro_add'         => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'visible'           => array(T_ZBX_STR, O_OPT, null, null, null),
        // actions
        'action'            => array(
            T_ZBX_STR,
            O_OPT,
            P_SYS | P_ACT,
            IN('"host.export","host.massdelete","host.massdisable","host.massenable"' . ',"host.massupdateform"'),
            null
        ),
        'add_to_group'      => array(T_ZBX_INT, O_OPT, P_SYS | P_ACT, DB_ID, null),
        'delete_from_group' => array(T_ZBX_INT, O_OPT, P_SYS | P_ACT, DB_ID, null),
        'unlink'            => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'unlink_and_clear'  => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'add'               => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'update'            => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'masssave'          => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'clone'             => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'full_clone'        => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'delete'            => array(T_ZBX_STR, O_OPT, P_SYS | P_ACT, null, null),
        'cancel'            => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'form'              => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'form_refresh'      => array(T_ZBX_INT, O_OPT, null, null, null),
        // filter
        'filter_set'        => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'filter_rst'        => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'filter_host'       => array(T_ZBX_STR, O_OPT, null, null, null),
        'filter_ip'         => array(T_ZBX_STR, O_OPT, null, null, null),
        'filter_dns'        => array(T_ZBX_STR, O_OPT, null, null, null),
        'filter_port'       => array(T_ZBX_STR, O_OPT, null, null, null),
        // ajax
        'filterState'       => array(T_ZBX_INT, O_OPT, P_ACT, null, null),
        'favobj'            => array(T_ZBX_STR, O_OPT, P_ACT, null, null),
        'favid'             => array(T_ZBX_INT, O_OPT, P_ACT, null, null),
        'dataviewgroupids'  => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'favaction'         => array(T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null),
        // sort and sortorder
        'sort'              => array(T_ZBX_STR, O_OPT, P_SYS, IN('"name","status"'), null),
        'sortorder'         => array(T_ZBX_STR, O_OPT, P_SYS, IN('"' . ZBX_SORT_DOWN . '","' . ZBX_SORT_UP . '"'), null)
    );

    check_fields($fields);


    /*
     * Permissions
     */
    if (getRequest('groupid') && !API::HostGroup()->isWritable(array($_REQUEST['groupid']))) {
        access_deny();
    }
    if (getRequest('hostid') && !API::Host()->isWritable(array($_REQUEST['hostid']))) {
        access_deny();
    }

    /*
     * Ajax
     */
    if (hasRequest('filterState')) {
        CProfile::update('web.dataview.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
    }

    /*
     * ajax update timelinefixedperiod
     */
    if (isset($_REQUEST['favobj'])) {
        // saving fixed/dynamic setting to profile
        if ($_REQUEST['favobj'] == 'timelinefixedperiod' && isset($_REQUEST['favid'])) {
            CProfile::update('web.dataview.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
        }
    }


    if (hasRequest('dataviewgroupids')) {
        if ($_REQUEST['dataviewgroupids']) {
            CProfile::updateArray('web.dataview.filter.groupids', getRequest('dataviewgroupids', array()), PROFILE_TYPE_STR);
        } else {
            CProfile::deleteIdx('web.dataview.filter.groupids');
        }
    }

    if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
        require_once dirname(__FILE__) . '/include/page_footer.php';
        exit;
    }

    $curtime = time(); //当前时间
    $timeline = array();

    if (!empty($_REQUEST['period']) || !empty($_REQUEST['stime'])) {
        $timeline = CScreenBase::calculateTime(array(
            'period' => getRequest('period'),
            'stime'  => getRequest('stime')
        ));

        $screen = new CScreenBuilder();
        CScreenBuilder::insertScreenStandardJs(array(
            'timeline'   => $timeline,
            'profileIdx' => 'web.dataview'
        ));
    } else {
        $screen = new CScreenBuilder();
        $timeline = $screen->timeline;
        CScreenBuilder::insertScreenStandardJs(array(
            'timeline'   => $timeline,
            'profileIdx' => "web.dataview"
        ));
    }


    /*
     * Filter
     */
    if (hasRequest('filter_set')) {
        CProfile::update('web.dataview.filter.select', getRequest('select', ''), PROFILE_TYPE_STR);
        CProfile::updateArray('web.dataview.filter.itemids', getRequest('itemids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.dataview.filter.groupids', getRequest('groupids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.dataview.filter.hostids', getRequest('hostids', array()), PROFILE_TYPE_STR);
    } elseif (hasRequest('filter_rst')) {
        DBStart();
        CProfile::delete('web.dataview.filter.select');
        CProfile::deleteIdx('web.dataview.filter.itemids');
        CProfile::deleteIdx('web.dataview.filter.groupids');
        CProfile::deleteIdx('web.dataview.filter.hostids');
        DBend();
    }

    $filter = array(
        'select'   => CProfile::get('web.dataview.filter.select', ''),
        'itemids'  => CProfile::getArray('web.dataview.filter.itemids', ''),
        'groupids' => CProfile::getArray('web.dataview.filter.groupids', ''),
        'hostids'  => CProfile::getArray('web.dataview.filter.hostids', '')
    );

    $filterSet = ($filter['select'] !== '' || $filter['itemids'] !== '' || $filter['groupids'] !== '' || $filter['hostids']);

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
                'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_PROTOTYPE, ZBX_FLAG_DISCOVERY_CREATED)
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
    if ($filterSet) {
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

        if ($hosts) {
            $itemkeys = API::Item()->get(array(
                'output'  => array('key_', 'flags'),
                'itemids' => $filter['itemids'],
                'filter'  => array(
                    'flags' => array(
                        ZBX_FLAG_DISCOVERY_NORMAL,
                        ZBX_FLAG_DISCOVERY_PROTOTYPE,
                        ZBX_FLAG_DISCOVERY_CREATED
                    )
                )
            ));
            foreach($hosts as $host) {
                $tmp1 = array();
                foreach($itemkeys as $item) {
                    $tmp = getDataFromHistory(array(
                        'hostid'  => $host['hostid'],
                        'itemkey' => $item['key_'],
                        'flags'   => $item['flags'],
                        'stime'   => $timeline['stime'],
                        'period'  => $timeline['period'],
                    ));
                    $tmp1 = array_merge($tmp1, $tmp);
                }
                $dataTable[$host['name']] = $tmp1;
            }
            unset($item, $host);
        }
    }

    $sortField = getRequest('sort', CProfile::get('web.' . $page['file'] . '.sort', 'name'));
    $sortOrder = getRequest('sortorder', CProfile::get('web.' . $page['file'] . '.sortorder', ZBX_SORT_UP));
    CProfile::update('web.' . $page['file'] . '.sort', $sortField, PROFILE_TYPE_STR);
    CProfile::update('web.' . $page['file'] . '.sortorder', $sortOrder, PROFILE_TYPE_STR);


    /*
     * Display
     */
    $dataviewWidget = new CWidget(null, "dataview");
    $dataviewWidget->addHeader(_('Items'));
    $dataviewWidget->addPageHeader(_('DATA VIEW'), get_icon('fullscreen', array('fullscreen' => getRequest('fullscreen', 0))));

    $filterForm = new CForm('get');
    $filterForm->setAttribute('name', 'zbx_report_dataview');
    $filterForm->setAttribute('id', 'zbx_report_dataview');

    $filterTable = new CTable(null, 'filter');
    $filterTable->setCellPadding(0);
    $filterTable->setCellSpacing(0);

    $filterTable->addRow(array(
        new CCol(bold(_('Host groups') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'groupids[]',
            'objectName' => 'hostGroup',
            'data'       => $multiSelectHostGroupData,
            'popup'      => array(
                'parameters'  => 'srctbl=host_groups&dstfrm=' . $filterForm->getName() . '&dstfld1=groupids_' . '&srcfld1=groupid&multiselect=1',
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
        new CCol(bold(_('Item Key') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'itemids[]',
            'objectName' => 'items',
            'data'       => $multiSelectItemData,
            'popup'      => array(
                'parameters'  => 'srctbl=item_key&dstfrm=' . $filterForm->getName() . '&dstfld1=itemids_&srcfld1=groupid' . '&multiselect=1&from=report.dataview',
                'width'       => 450,
                'height'      => 450,
                'buttonClass' => 'input filter-multiselect-select-button'
            )
        )), 'inputcol'),
    ));

    $filterButton = new CSubmit('filter_set', _('Filter'), 'chkbxRange.clearSelectedOnFilterChange();');
    $filterButton->useJQueryStyle();

    $resetButton = new CSubmit('filter_rst', _('Reset'), 'chkbxRange.clearSelectedOnFilterChange();');
    $resetButton->useJQueryStyle();

    $divButtons = new CDiv(array($filterButton, SPACE, $resetButton));
    $divButtons->setAttribute('style', 'padding: 4px 0px;');

    $filterTable->addRow(new CCol($divButtons, 'controls', 4));
    $filterForm->addItem($filterTable);

    $flikerDiv = new CDiv(array($filterForm, new CDiv(null, null, 'scrollbar_cntr')), null, "flick_div");
    $dataviewWidget->addFlicker($flikerDiv, CProfile::get('web.dataview.filter.state', 0));
    $dataviewWidget->addItem(Br());

    // table
    $table = new CTableInfo(($filterSet) ? _('No values found.') : _('Specify some filter condition to see the values.'));

    $keys = array();
    foreach($dataTable as $data) {
        foreach($data as $k => $v) {
            $keys[] = $k;
        }
    }
    unset($data, $k, $v);
    $keys = array_unique($keys);

    if ($keys) {
        $itemsHeader = array(new CCol(bold(_('Items')), 'label', 1));
        $firstRowItem = array(new CCol(bold(_('Hosts')), "label"));

        foreach($keys as $item) {
            $itemsHeader[] = new CCol(bold(_($item)), 'label', 3);
            $firstRowItem[] = new CCol(bold(_('max')), "label");
            $firstRowItem[] = new CCol(bold(_('min')), "label");
            $firstRowItem[] = new CCol(bold(_('avg')), "label");
        }
        unset($item);
        $table->setHeader($itemsHeader);

        $firstRow = new CRow($firstRowItem, "header");

        $table->addRow($firstRow);
    }

    if ($dataTable) {
        foreach($dataTable as $key => $row) {
            $dataRow = array(
                new CCol($key)
            );
            foreach($row as $col) {
                if ($col) {
                    $dataRow[] = new CCol($col['max']);
                    $dataRow[] = new CCol($col['min']);
                    $dataRow[] = new CCol($col['avg']);
                } else {
                    $dataRow[] = new CCol("-");
                    $dataRow[] = new CCol("-");
                    $dataRow[] = new CCol("-");
                }
            }

            $table->addRow($dataRow);
        }
    }

    $dataviewWidget->addItem($table);
    $dataviewWidget->show();

    $js = <<<EOT
        var $ = jQuery;
        var cnt = 0;
        var \$ul = $('#groupids_ div.selected ul');

        //DOMNodeInserted DOMNodeRemoved
        \$ul.on('DOMNodeRemoved',function(e){
            var groupids = [];

            if(e.type === 'DOMNodeRemoved'){
                setTimeout(function(){
                    var \$li = \$ul.find('li');
                    var li_len = \$li.length;
                    if(\$li.length){
                        for(var i=0;i<li_len;i++){
                            groupids.push(\$(\$li[i]).attr('data-id'));
                        }
                    }
                    if(groupids.length == 0){
                        $.post('report.dataview.php?output=ajax',{'dataviewgroupids':0});
                    }else{
                        $.post('report.dataview.php?output=ajax',{'dataviewgroupids[]':groupids});
                    }
                },20);
            }
        });
EOT;

    //insert_js($js,true);
    $ZBX_PAGE_POST_JS[] = $js;

    require_once dirname(__FILE__) . '/include/page_footer.php';
?>