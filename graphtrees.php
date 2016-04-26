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

    require_once dirname(__FILE__).'/include/config.inc.php';
    require_once dirname(__FILE__).'/include/hosts.inc.php';
    require_once dirname(__FILE__).'/include/graphs.inc.php';



    $page['title'] = _('Graph Trees');
    $page['file'] = 'graphtrees.php';
    $page['hist_arg'] = array('graphid', 'groupid', 'hostid');
    $page['type'] = detect_page_type(PAGE_TYPE_HTML);

    //define('ZBX_PAGE_DO_JS_REFRESH', 1); no refresh

    ob_start();
    require_once dirname(__FILE__).'/include/page_header.php';

    $fields = array(
        'groupid' =>	array(T_ZBX_INT, O_OPT, P_SYS, DB_ID,		null),
        'hostid' =>		array(T_ZBX_INT, O_OPT, P_SYS, DB_ID,		null),
        'graphid' =>	array(T_ZBX_INT, O_OPT, P_SYS, DB_ID,		null),
        'period' =>		array(T_ZBX_INT, O_OPT, P_SYS, null,		null),
        'stime' =>		array(T_ZBX_STR, O_OPT, P_SYS, null,		null),
        'fullscreen' =>	array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'),	null),
        // ajax
        'filterState' => array(T_ZBX_INT, O_OPT, P_ACT, null,		null),
        'favobj' =>		array(T_ZBX_STR, O_OPT, P_ACT, null,		null),
        'favid' =>		array(T_ZBX_INT, O_OPT, P_ACT, null,		null),
        'favaction' =>	array(T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null)
    );
    check_fields($fields);

    $gtree_widget = new CWidget(null,"gtree_iframe");
    $gtree_widget->addPageHeader(_('Graphtrees'),get_icon('fullscreen', array('fullscreen' => $_REQUEST['fullscreen'])));

    $gtree_iframe_left = new CIFrame("graphtree.left.php?type=zTree","100%","100%","auto","zatree_iframe_left");
    $gtree_iframe_right = new CIFrame("graphtree.right.php","100%","100%","auto","zatree_iframe_right");
    $gtree_iframe_left->setAttribute("name","leftFrame");
    $gtree_iframe_left->setAttribute("id","leftFrame");
    $gtree_iframe_right->setAttribute("name","rightFrame");
    $gtree_iframe_right->setAttribute("id","rightFrame");
    $gtree_div_left = new CDiv($gtree_iframe_left,"iframe","gtree_left");
    $gtree_div_right = new CDiv($gtree_iframe_right,"iframe","gtree_right");

    $gtree_widget->addItem($gtree_div_left);
    $gtree_widget->addItem(new CDiv(new CDiv(null,"line"),"iframe","resizeDiv"));
    $gtree_widget->addItem($gtree_div_right);
    $gtree_widget->show();
    $resizeJs = "
        var \$gtree_widget = jQuery('#resizeDiv').parent();
        var \$resizeDiv = jQuery('#resizeDiv'); //jQury 对象
        var resizeDiv = jQuery('#resizeDiv')[0]; //dom 对象
        var _drag = false;
        \$resizeDiv.on('mousedown',function(e){
            _drag = true;
            var disX = e.clientX;
            var glw = jQuery('#gtree_left').width();
            var grw = jQuery('#gtree_right').width();
            \$gtree_widget.on('mousemove',function(e1){
                if(_drag){
                    var iT = e1.clientX - disX;
                    jQuery('#gtree_left').width(glw + iT);
                    jQuery('#gtree_right').width(grw - iT);
                }
                //console.log('mousemove',new Date().getTime());
                return false;
            });

            \$gtree_widget.on('mouseup',function() {
                //console.log('mouseup',new Date().getTime());
                \$gtree_widget.unbind('mousemove mouseup');
                //\$gtree_widget.unbind('mouseup');
                //resizeDiv.releaseCapture && resizeDiv.releaseCapture();
                this.releaseCapture && this.releaseCapture();
                _drag = false;
                return false;
            });

            \$gtree_widget.on('mouseleave',function() {
                //console.log('mouseleave',new Date().getTime());
                \$gtree_widget.unbind('mousemove mouseup');
                _drag = false;
                this.releaseCapture && this.releaseCapture();
                return false;
            });
            //\$gtree_widget.trigger('mouseleave');
            this.setCapture && this.setCapture();
            return false;
        });


    ";
    insert_js($resizeJs,true);

    require_once dirname(__FILE__) . '/include/page_footer.php';

