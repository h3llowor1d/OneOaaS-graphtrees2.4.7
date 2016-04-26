<script type="text/javascript">

    var zTree;

    var setting = {
        view: {
            dblClickExpand: false
        },
        async: {
            enable: true,
            url:"zabbix_ajax.php",
            autoParam:["id=groupid"], //请求的参数即groupid=nodeid
            otherParam:{"otherParam":"zTreeAsyncTest"},
            type: "get"
        },
        callback: {

        }
    };

    $(document).ready(function(){

        $.fn.zTree.init($("#graphtree"), setting);
        //右键菜单
        zTree = $.fn.zTree.getZTreeObj("graphtree");


        /*页面刷新注释
         var iTime = setInterval(function() {
         //获取选择的节点
         var nodes=zTree.getSelectedNodes();
         var selectNode=nodes[0];

         if(selectNode){
         //obj="#"+selectNode.tId+"_a";
         //$(obj).click();
         window.parent.frames["rightFrame"].location.reload();
         }

         }, 60000*3);
         */


    });

</script>