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

    $page['title'] = _('Data graph');
    $page['file'] = 'report.datagraph.php';
    $page['type'] = detect_page_type(PAGE_TYPE_HTML);
    $page['hist_arg'] = array('groupid');
    $page['css'] = array("cubism.css");
    $page['scripts'] = array(
        'class.calendar.js',
        'gtlc.js',
        'flickerfreescreen.js',
        'multiselect.js',
        'd3.v2.js',
        'cubism.v1.js'
    );

    require_once dirname(__FILE__) . '/include/page_header.php';

    // VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
    $fields = array(
        'hosts'             => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groups'            => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'hostids'           => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'groupids'          => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'datagraphgroupids' => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'itemids'           => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'period'            => array(T_ZBX_INT, O_OPT, P_SYS, null, null),
        'stime'             => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        //'applications'      => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'graheight'         => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'graSelectedHeight' => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
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
        'favaction'         => array(T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null),
        // sort and sortorder
        'sort'              => array(T_ZBX_STR, O_OPT, P_SYS, IN('"name","status"'), null),
        'sortorder'         => array(T_ZBX_STR, O_OPT, P_SYS, IN('"' . ZBX_SORT_DOWN . '","' . ZBX_SORT_UP . '"'), null)
    );
    check_fields($fields);

    /*
     * Permissions
     */
    if (getRequest('groupids') && !API::HostGroup()->isWritable($_REQUEST['groupids'])) {
        access_deny();
    }
    if (getRequest('hostids') && !API::Host()->isWritable($_REQUEST['hostids'])) {
        access_deny();
    }

    /*
     * Ajax
     */
    if (hasRequest('filterState')) {
        CProfile::update('web.datagraph.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
    }

    if (hasRequest('datagraphgroupids')) {
        if ($_REQUEST['datagraphgroupids']) {
            CProfile::updateArray('web.datagraph.filter.groupids', getRequest('datagraphgroupids', array()), PROFILE_TYPE_STR);
        } else {
            CProfile::deleteIdx('web.datagraph.filter.groupids');
        }
    }
    /*
     * ajax update timelinefixedperiod
     */
    if (isset($_REQUEST['favobj'])) {
        // saving fixed/dynamic setting to profile
        if ($_REQUEST['favobj'] == 'timelinefixedperiod' && isset($_REQUEST['favid'])) {
            CProfile::update('web.datagraph.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
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
            'profileIdx' => 'web.datagraph'
        ));
    } else {
        $screen = new CScreenBuilder();
        $timeline = $screen->timeline;
        CScreenBuilder::insertScreenStandardJs(array(
            'timeline'   => $timeline,
            'profileIdx' => "web.datagraph"
        ));
    }

    $cubism['size'] = 1200;
    $cubism['serverDelay'] = ($curtime - strtotime($timeline['stime']) - (int) $timeline['period'])*1000;
    $cubism['step'] = ((int) $timeline['period']*1000) / $cubism['size'];
    /*
     * Filter
     */
    if (hasRequest('filter_set')) {
        CProfile::update('web.datagraph.filter.graheight', getRequest('graheight', 0), PROFILE_TYPE_INT);
        CProfile::updateArray('web.datagraph.filter.itemids', getRequest('itemids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.datagraph.filter.groupids', getRequest('groupids', array()), PROFILE_TYPE_STR);
        CProfile::updateArray('web.datagraph.filter.hostids', getRequest('hostids', array()), PROFILE_TYPE_STR);
    } elseif (hasRequest('filter_rst')) {
        DBStart();
        CProfile::delete('web.datagraph.filter.graheight');
        CProfile::deleteIdx('web.datagraph.filter.itemids');
        CProfile::deleteIdx('web.datagraph.filter.groupids');
        CProfile::deleteIdx('web.datagraph.filter.hostids');
        DBend();
    }

    $filter = array(
        'graheight' => CProfile::get('web.datagraph.filter.graheight', 0),
        'itemids'   => CProfile::getArray('web.datagraph.filter.itemids', ''),
        'groupids'  => CProfile::getArray('web.datagraph.filter.groupids', ''),
        'hostids'   => CProfile::getArray('web.datagraph.filter.hostids', '')
    );

    //default cubism graph height
    $defaultHeight = 100;//px
    $filter['graheight'] = !empty($filter['graheight']) ? $filter['graheight'] : $defaultHeight;

    $filterSet = ($filter['itemids'] !== '' || $filter['groupids'] !== '' || $filter['hostids']);

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


    if ($filterSet) {
        $hosts = array();
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

            $items = array();
            foreach($hosts as $hostid => $host) {
                $tmp1 = array();
                foreach($itemkeys as $item) {
                    $tmp = getItem(array(
                        'hostid'  => $host['hostid'],
                        'itemkey' => $item['key_'],
                    ));
                    $tmp1 = array_merge($tmp1, $tmp);
                }
                $hosts[$host['hostid']] = $host;
                unset($hosts[$hostid]);
                $items[$host['hostid']] = $tmp1;
            }

            unset($host, $hostid, $item, $tmp, $tmp1);


            if ($items) {
                foreach($items as $hostid => $hostItems) {
                    foreach($hostItems as $item) {
                        $itemExpanded = CMacrosResolverHelper::resolveItemNames(array($item));
                        $dataTable[$hostid][$item['itemid']] = array(
                            'itemid' => $item['itemid'],
                            'name'   => $hosts[$hostid]['name'] . ":" . $itemExpanded[0]['name_expanded'],
                            'data'   => (new CHistoryData(array(
                                'itemid' => $item['itemid'],
                                'size' => $cubism['size'],
                                'stime'  => strtotime($timeline['stime']),
                                'period' => $timeline['period']
                            )))->get()
                        );
                    }
                }
                unset($item, $hostid, $hostItems, $itemExpanded);
            }
        }
    }

    $graHeightComboxItems = array(
        '-1'  => 'not selected',
        '50'  => 50,
        '100' => 100,
        '200' => 200,
        '400' => 400,
    );

    /*
     * Display
     */
    $datagraphWidget = new CWidget();
    $datagraphWidget->addHeader(_('Items'));
    $datagraphWidget->addPageHeader(_('DATA VIEW'), get_icon('fullscreen', array('fullscreen' => getRequest('fullscreen', 0))));

    $filterForm = new CForm('get');
    $filterForm->setAttribute('name', 'zbx_report_datagraph');
    $filterForm->setAttribute('id', 'zbx_report_datagraph');
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
        new CCol(bold(_('Graph height') . ':'), 'label'),
        new CCol(array(
            new CTextBox('graheight', !empty($filter['graheight']) ? $filter['graheight'] : $defaultHeight, 40),
            new CSpan('px')
        ), 'inputcol'),
    ));

    //下拉框onchange事件
    $action = "
        if(jQuery(this).val() !== '-1'){
            jQuery('#graheight').val(jQuery(this).val());
        }else{
            jQuery('#graheight').val({$defaultHeight});
        }
    ";

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
        new CCol(_(''), 'label'),
        new CCol(array(
            new CComboBox('graSelectedHeight', $defaultHeight, $action, $graHeightComboxItems),
            new CSpan('px')
        )),
    ));

    /*$filterTable->addRow(array(
        new CCol(bold(_('Item key') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'itemids[]',
            'objectName' => 'items',
            'data'       => $multiSelectItemData,
            'popup'      => array(
                'parameters'  => 'srctbl=item_key&dstfrm=' . $filterForm->getName() . '&dstfld1=itemids_&srcfld1=groupid&multiselect=1',
                'width'       => 1000,
                'height'      => 500,
                'buttonClass' => 'input filter-multiselect-select-button'
            )
        )), 'inputcol'),
    ));*/

    $filterTable->addRow(array(
        new CCol(bold(_('Item Key') . ':'), 'label'),
        new CCol(new CMultiSelect(array(
            'name'       => 'itemids[]',
            'objectName' => 'items',
            'data'       => $multiSelectItemData,
            'popup'      => array(
                'parameters'  => 'srctbl=item_key&dstfrm=' . $filterForm->getName() . '&dstfld1=itemids_&srcfld1=groupid' . '&multiselect=1&from=report.datagraph',
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
    $datagraphWidget->addFlicker($flikerDiv, CProfile::get('web.datagraph.filter.state', 0));
    $datagraphWidget->addItem(Br());
    if ($dataTable) {
        $datagraphWidget->addItem(new CDiv(null, "cubism", "cubism"));
    } else {
        $datagraphWidget->addItem(new CTableInfo("no graphs found"));
    }

    $datagraphWidget->show();
    array_shift($graHeightComboxItems);
?>

    <script type="text/javascript">
        var graHeightArr = [<?php echo implode(",", $graHeightComboxItems) ?>];
        jQuery("#graheight").on('blur', function () {
            //value check
            var max = 1000,
                min = 30,
                graheight = parseInt(jQuery("#graheight").val());
            if (isNaN(graheight) || graheight < min || graheight > max) {
                alert("请重新输入！（合理高度30~1000px）");
                jQuery("#graheight").val("").focus();
                return false;
            } else {
                jQuery("#graheight").val(graheight);
                if (graHeightArr.indexOf(graheight) !== -1) {
                    jQuery("#graSelectedHeight").val(graheight);
                }
            }
        });

        function getValues(name, itemid) {
            var valueArr = new Array();
            <?php
            foreach($dataTable as $host) {
                foreach($host as $dataItem) {
                    if ($dataItem) {
                        echo "valueArr[" . $dataItem['itemid'] . "] = [" . implode(',', $dataItem['data']) . "];";
                    } else {
                        echo "valueArr[" . $dataItem['itemid'] . "] = [];";
                    }
                }
            }
            unset($host, $dataItem);
            ?>
            var values = valueArr[itemid];

            return context.metric(function (start, stop, step, callback) {
                start = +start, stop = +stop; //转换为整型
                callback(null, values = values.slice((start - stop) / step));
            }, name);
        }

        var size = <?php echo $cubism['size']; ?>;

        var context = cubism.context()
            .serverDelay(<?php echo $cubism['serverDelay']; ?>)
            .step(<?php echo $cubism['step']; ?>)
            .size(<?php echo $cubism['size']; ?>)
            .stop();

        <?php
        foreach($dataTable as $host) {
            foreach($host as $dataItem) {
                echo "var cubism_" . $dataItem['itemid'] . "=getValues('" . $dataItem['name'] . "'," . $dataItem['itemid'] . ");";
            }
        }
        unset($host, $dataItem);
        ?>

        d3.select("#cubism").call(function (div) {
            div.append("div")
                .attr("class", "axis")
                .call(context.axis().orient("top"));

            div.selectAll(".horizon")
                .data(
                    <?php
                    $cubismItems = array();
                    foreach($dataTable as $host) {
                        foreach($host as $dataItem) {
                            $cubismItems[] = "cubism_" . $dataItem['itemid'];
                        }
                    }
                    echo str_replace('"',"",json_encode($cubismItems));
                    unset($host, $dataItem);
                    ?>
                )
                .enter().append("div")
                .attr("class", "horizon")
                .call(context.horizon().height(<?php echo $filter['graheight'] ?>).extent([0, 200000]));

            div.append("div")
                .attr("class", "rule")
                .call(context.rule());

        });

        // On mousemove, reposition the chart values to match the rule.
        context.on("focus", function (i) {
            var innerWidth = document.body.scrollWidth,
                left = Math.floor((innerWidth - size) / 2) + i;
            if (i < 50) {
                d3.selectAll(".value").style("right", i == null ? null : context.size() - i - 50 + "px");
            } else {
                d3.selectAll(".value").style("right", i == null ? null : context.size() - i + "px");
            }
            d3.selectAll(".line")
                .style("display", i == null ? "none" : null)
                .style("left", i == null ? null : left + "px");

        });
    </script>


<?php
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
                        jQuery.post('report.datagraph.php?output=ajax',{'datagraphgroupids':0});
                    }else{
                        jQuery.post('report.datagraph.php?output=ajax',{'datagraphgroupids[]':groupids});
                    }
                },20);
            }
        });
DOMNODE;
    $ZBX_PAGE_POST_JS[] = $js;
    require_once dirname(__FILE__) . '/include/page_footer.php';
?>