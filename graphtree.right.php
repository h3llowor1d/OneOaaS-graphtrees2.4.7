<?php
    require_once dirname(__FILE__) . '/include/config.inc.php';
    require_once dirname(__FILE__) . '/include/hosts.inc.php';
    include_once dirname(__FILE__) . '/include/items.inc.php';
    require_once dirname(__FILE__) . '/include/graphs.inc.php';

    $page['title'] = _('Graph trees');
    $page['file'] = 'graphtree.right.php';
    $page['hist_arg'] = array('hostid', 'groupid', 'graphid');
    $page['scripts'] = array('class.calendar.js', 'gtlc.js', 'flickerfreescreen.js');
    $page['type'] = detect_page_type(PAGE_TYPE_HTML);

    define("ZBX_PAGE_NO_MENU", 1);
    define("ZBX_PAGE_NO_HEADER", 1);
    require_once dirname(__FILE__) . '/include/page_header.php';

    // VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
    $fields = array(
        'groupid'       => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'hostid'        => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'graphid'       => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'applicationid' => array(T_ZBX_INT, O_OPT, P_SYS, DB_ID, null),
        'period'        => array(T_ZBX_INT, O_OPT, P_SYS, null, null),
        'stime'         => array(T_ZBX_STR, O_OPT, P_SYS, null, null),
        'fullscreen'    => array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null),
        // ajax
        'filterState'   => array(T_ZBX_INT, O_OPT, P_ACT, null, null),
        'favobj'        => array(T_ZBX_STR, O_OPT, P_ACT, null, null),
        'favid'         => array(T_ZBX_INT, O_OPT, P_ACT, null, null),
        'favaction'     => array(T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null)
    );
    check_fields($fields);

    /*
     * Filter
     */
    if (hasRequest('filterState')) {
        CProfile::update('web.graphtree.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
    }

    /*
     * ajax update timelinefixedperiod
     */
    if (isset($_REQUEST['favobj'])) {
        // saving fixed/dynamic setting to profile
        if ($_REQUEST['favobj'] == 'timelinefixedperiod' && isset($_REQUEST['favid'])) {
            CProfile::update('web.graphtree.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
        }
    }

    if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
        require_once dirname(__FILE__) . '/include/page_footer.php';
        exit;
    }

    $hostid = getRequest("hostid", -1);
    $groupid = getRequest("groupid", -1);
    $applicationid = getRequest("applicationid", -1);

    $curtime = time(); //当前时间
    $timeline = array();

    if (!empty($_REQUEST['period']) || !empty($_REQUEST['stime'])) {
        $timeline = CScreenBase::calculateTime(array(
            'period' => getRequest('period'),
            'stime'  => getRequest('stime')
        ));


        $screen = new CScreenBuilder();
        CScreenBuilder::insertScreenStandardJs(array(
            'timeline' => $timeline,
            'profileIdx' => 'web.graphtree'
        ));
    } else {
        $timeline = array(
            'period' => 3600,
            'stime'  => $curtime - 3600
        );

        $screen = new CScreenBuilder();
        CScreenBuilder::insertScreenStandardJs(array(
            'timeline' => $screen->timeline,
            'profileIdx' => "web.graphtree"
        ));
    }


    $graphWidth = 520; //图形宽度
    $displayWidth = "50%";//"50%"; //显示宽度
    $height = 260;

    $chartsWidget = new CWidget("all_items","graphtree");
    $chartsWidget->addFlicker(new CDiv(null, null, 'scrollbar_cntr'), CProfile::get('web.graphtree.filter.state', 1));

    $graph_list = array(); //记录结果信息数
    $value_list = array();
    $item_list = array();
    $graphids = null;

    if ($groupid > 0 || $hostid > 0 || $applicationid > 0) {
        $chartsWidget->addHeaderRowNumber();

        if ($groupid !== -1 && $groupid > 0) {
            $graph_list = getGraphsByGroupid($groupid);
        } elseif ($hostid !== -1 && $hostid > 0) {
            $graph_list = getGraphsByHostid($hostid);
        } elseif ($applicationid !== -1 && $applicationid > 0) {
            //graph list
            $graph_list = getGraphsByApplicationid($applicationid);
            //no graphid
            $item_list = getItemsByApplicationid($applicationid);

            /*
             * 0 float
             * 1 character
             * 2 log
             * 3 unsign float
             * 4 text
             */
            foreach($item_list as $k => $item){
                switch($item['value_type']){
                    case 1:
                    case 2:
                    case 4:
                        $value_list[$item['itemid']] = $item;
                        unset($item_list[$k]);
                        break;
                    default:
                        break;
                }
            }

            $graph_list = zbx_array_merge($graph_list,$item_list);

            if ($value_list) {
                // get history
                if($history = Manager::History()->getLast($value_list, 1, ZBX_HISTORY_PERIOD)){
                    foreach($value_list as &$item){
                        if(isset($history[$item['itemid']])){
                            $item = zbx_array_merge($item,$history[$item['itemid']][0]);
                        }else{
                            $item = zbx_array_merge($item,array('clock'=>null,'value'=>null,'ns'=>null));
                        }
                    }
                }else{
                    $value_list = array();
                }
                unset($item);
            }
        }

        $pageTable = getPagingLine($graph_list, ZBX_SORT_UP);

        if (!is_null($pageTable)) {
            $chartsWidget->addItem($pageTable);
        }

        if(is_array($graph_list) && !empty($graph_list)){
            //graphid --> graph
            foreach($graph_list as $key => $item) {
                if(strpos($key,"graph_") === 0){
                    $small_graph = "chart2.php?graphid=" . $item['graphid'] . "&width=" . $graphWidth . "&height=" . $height . "&stime=" . $timeline['stime'] . "&period=" . $timeline['period'];
                    $big_graph = "biggraph.php?graphid=" . $item['graphid'] . "&stime=" . $timeline['stime'] . "&period=" . $timeline['period'];
                    $div_tmp = new CDiv(null, null, "div_" . $item['graphid']);
                }else if(strpos($key,"item_") === 0){
                    $small_graph = "chart.php?itemids=" . $item['itemid'] . "&width=" . $graphWidth . "&height=" . $height . "&stime=" . $timeline['stime'] . "&period=" . $timeline['period'];
                    $big_graph = "history.php?action=showgraph&itemids[]=".$item['itemid'];
                    $div_tmp = new CDiv(null, null, "div_" . $item['itemid']);
                }
                $div_tmp->attr("style", "width:{$displayWidth};float: left;text-align: center;padding: 10px 6px 0px 0px;");

                $img_tmp = new CImg($small_graph, null, null, $height, "");
                $img_tmp->setAttribute('style',"width:100%");
                $a_tmp = new CTag("a", "yes", $img_tmp);
                $a_tmp->attr("href", $big_graph);

                if(strpos($key,"item_") === 0){
                    $a_tmp->attr("target", "_blank");
                }

                $div_tmp->addItem($a_tmp);
                $chartsWidget->addItem($div_tmp);
            }
            unset($item);

            if (!is_null($pageTable)) {
                $pageTable->addStyle("clear:both");
                $chartsWidget->addItem($pageTable);
            }
            $chartsWidget->addItem(BR());

            //latest value
            if($value_list){
                $valueTable = new CTable(null,"tableinfo");
                $valueTable->setCellPadding(0);
                $valueTable->setCellSpacing(0);

                $valueTable->addRow(array(
                    new CCol(bold(_('Name') ), 'label'),
                    new CCol(bold(_('Last check')), 'label'),
                    new CCol(bold(_('Last value')), 'label'),
                    new CCol(bold(_('Option')), 'label')
                ),
                    "header");

                foreach($value_list as $item){
                    $history_link = new CLink(new CSpan("history"),"history.php?action=showvalues&itemids[]=".$item['itemid']);
                    $history_link->setTarget("_blank");
                    $valueTable->addRow(array(
                        new CCol(new CSpan($item['name'],"item")),
                        new CCol(new CSpan(zbx_date2str(DATE_TIME_FORMAT_SECONDS,$item['clock']),"item")),
                        new CCol(new CSpan(($item['value'] !== null)?$item['value']:_("

                        No Value"),"item")),
                        new CCol($history_link)
                    ));
                    unset($history_link);
                }

                $valueTable->addStyle("clear:both");
                $chartsWidget->addItem($valueTable);
                unset($item);
            }

        }else{
            $chartsWidget->addItem(new CTableInfo(_('No graphs found.')));
        }
    }else{
        $chartsWidget->addItem(new CTableInfo(_('No graphs found.')));
    }
    $chartsWidget->show();

    require_once dirname(__FILE__) . '/include/page_footer.php';