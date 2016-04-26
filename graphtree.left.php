<?php

    require_once 'include/config.inc.php';

    $type = getRequest("type", "");
    if ($type === "zTree") {
        $page['scripts'] = array("jquery.ztree.core-3.5.js", "graphtree.js");
        $page['type'] = detect_page_type(PAGE_TYPE_HTML);
        $page['css'] = array("zTreeStyle.css");
        define("ZBX_PAGE_NO_MENU", 1);
        define("ZBX_PAGE_NO_HEADER", 1);

        require_once 'include/page_header.php';

        $left_tree = new CTag("ul", "yes");
        $left_tree->setAttribute("id", "graphtree");
        $left_tree->setAttribute("class", "ztree");
        $left_tree->show();
    } else {
        $groupid = getRequest("groupid", 0);
        $hostid = getRequest("hostid", 0);

        if ($groupid > 0) {
            //根据分组id查询分组下的机器
            $hosts = API::Host()->get(array(
                "output"          => array("hostid", "host", "name"),
                "monitored_hosts" => true,
                "groupids"        => array($groupid),
                "sortfield"       => array("host"),
                "sortorder"       => array("ASC")
            ));

            $new_list = $hosts;
            foreach($hosts as &$each_host) {
                $each_host['target'] = 'rightFrame';
                $each_host['isParent'] = "true";
                $app_count = API::Application()->get(array(
                    'countOutput' => "1",
                    'hostids'     => $each_host['hostid']
                ));
                $each_host['name'] = $each_host['name'] . '(' . $app_count . ')';
                $each_host['url'] = 'graphtree.right.php?hostid=' . $each_host['hostid'];
            }
            unset($each_host);
            echo json_encode(array_values($hosts));
        } else if($groupid == 0) {
            if ($hostid == 0) {
                //查询所有的分组列表
                $groups = API::HostGroup()->get(array(
                    "output"               => "extend",
                    "monitored_hosts"      => true,
                    "with_monitored_items" => 1,
                    "sortfield"            => "name"
                ));

                foreach($groups as &$each) {
                    $each['id'] = $each['groupid'];
                    $each['isParent'] = true;
                    $each['target'] = 'rightFrame';
                    $each['url'] = 'graphtree.right.php?groupid=' . $each['groupid'];

                    //查询下面有多少机器
                    $hosts = API::Host()->get(array(
                        "output"          => "extend",
                        "monitored_hosts" => true,
                        "groupids"        => array($each['groupid'])
                    ));

                    $each['name'] = $each['name'] . '(' . count($hosts) . ')';
                }
                /*$groups[] = array(
                    'groupid' => -1,
                    'name' => 'others',
                    'internal' => 0 ,
                    'flags'=> 0,
                    'id' => -1,
                    'isParent' => 1,
                    'target' => 'rightFrame',
                    'url' => 'graphtree.right.php?groupid=-1'
                );*/
                unset($each);
                echo json_encode($groups);
            } else {
                $applications = API::Application()->get(array(
                    "output"    => "extend",
                    "hostids"   => array($hostid),
                    "sortfield" => array("name"),
                    "sortorder" => array("ASC")
                ));

                if (is_array($applications)) {
                    foreach($applications as &$each) {
                        $each['target'] = 'rightFrame';
                        $each['url'] = 'graphtree.right.php?applicationid=' . $each['applicationid'];
                    }
                }
                unset($each);
                echo json_encode($applications);
            }
        }/*else{
            $other_items = array(
                'applicationid' => 10000,
                'name' => "other_001",
                'target' => 'rightFrame',
                'url' => 'graphtree.right.php?applicationid=10000'
            );
            echo json_encode($other_items);
        }*/
    }



