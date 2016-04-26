var zTree;

var setting = {
    view: {
        dblClickExpand: false
    },
    async: { //异步加载请求数据
        enable: true,
        url: "graphtree.left.php",
        autoParam: ["groupid=groupid", "hostid=hostid"], //请求的参数即groupid=nodeid
        otherParam: {"timestamp": new Date().getTime()},
        type: "get"
    },
    callback: {}
};


jQuery(document).ready(function () {

    jQuery.fn.zTree.init(jQuery("#graphtree"), setting);
    //右键菜单
    zTree = jQuery.fn.zTree.getZTreeObj("graphtree");

});
