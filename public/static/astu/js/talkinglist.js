/**
 * Created by Lccccc on 2017/9/22.
 */
function get_talking_list(e,page){
    $.ajax({
        url: '/home/Apidata/get_case_list',//这个就是请求地址对应sAjaxSource
        data : {
            strTitle: 'talking',
            tree_id : e,
            page:page,
            num:9
        },
        type: 'POST',
        dataType: 'json',
        async: true,
        success: function (ret) {
            var data = ret.list;
            var html_str = '';
            for( var i in data){
                html_str += "<div class=\"col-xs-12 col-sm-6 col-md-4 col-lg-4 hot-talking-content\"><dl><dt>" +
                    "<img onclick='window.open(\"/home/index/contentDetail?type=article&id="+data[i]['id']+"\")' src=\""+data[i]['strImgUrl']+ "\"width='100%' height='175px' onerror=\"this.src='/static/astu/images/zanwu.png'\" alt=\"\"></dt><dd>" +
                    "<a style='text-decoration:none' target='_blank' href=\"/home/index/contentDetail?type=article&id="+data[i]['id']+"\"><h4>"+data[i]['strTitle']+"</h4></a><p><span class=\"pull-left date\">"+data[i]['datCreateTime']+"</span>" +
                    "<span class=\"pull-left concern\">"+data[i]['intClick']+"</span><i class=\"pull-right share\"></i>" +
                    "<i class=\"pull-right zan\"></i></p></dd></dl>" +
                    "<div id=\"bdshare\" class=\"bdshare_t bds_tools get-codes-bdshare\" style=\"display:none\">" +
                    "<a class=\"bds_mshare\" data-cmd=\"mshare\"></a>" +
                    "<a class=\"bds_qzone\" data-cmd=\"qzone\" href=\"#\"></a>" +
                    "<a class=\"bds_tsina\" data-cmd=\"tsina\"></a>" +
                    "<a class=\"bds_baidu\" data-cmd=\"baidu\"></a>" +
                    "<a class=\"bds_renren\" data-cmd=\"renren\"></a>" +
                    "<a class=\"bds_tqq\" data-cmd=\"tqq\"></a></div></div>";

            }
            $("#mainContent").html(html_str);
            changepage('page_info',ret.page,ret.total_page,ret.total_num,'get_talking_list',e);
            //移入
            $('.talking dl').mouseenter(function () {
                $(this).find("h4").addClass('orangered');
            });
            $('.talking dl').mouseleave(function () {
                $(this).find("h4").removeClass('orangered');
            });
//点赞
            $('#mainContent').delegate('i.zan', 'click', function () {
                $(this).toggleClass('zan-tab');
            });
            //获取每个简介最大高度 并赋值给所有
            var h_max = 0;
            //求最大高度
            $(".hot-talking-content").each(function() {
                var h = $(this).innerHeight();
                h_max = h > h_max ? h : h_max;
            });
            //将class的高度赋值为最大高度，
            //最大高度innerheight=padding+内容高度height
            $(".hot-talking-content").each(function() {
                //求padding的值
                var h_pad = $(this).innerHeight() - $(this).height();
                $(this).height(h_max - h_pad);
            });

            //获取每个标题最大高度 并赋值给所有
            var t_max = 0;
            //求最大高度
            $(".hot-talking-content h4").each(function() {
                var th = $(this).innerHeight();console.log(th);
                t_max = th > t_max ? th : t_max;
            });
            //将class的高度赋值为最大高度，
            //最大高度innerheight=padding+内容高度height
            $(".hot-talking-content h4").each(function() {
                //求padding的值
                var t_pad = $(this).innerHeight() - $(this).height();
                $(this).height(t_max - t_pad);
            });

//找到id名是bdshare的标签，添加data属性，
            $(function($){
                var html = window.location.href ;
                $('#bdshare').attr('data',{
                    url:html
                })
            })
        },
        error:function(XMLHttpRequest, textStatus, errorThrown) {
            alert("status:"+XMLHttpRequest.status+",readyState:"+XMLHttpRequest.readyState+",textStatus:"+textStatus);
        }
    });
}

function changepage(page_div,page,total_page,total_num,func,e){
    $("#"+page_div).html('');
    page=parseInt(page);
    if(total_page<=5){
        var page_str = "<li onclick=\""+func+"("+e+",1)\"><a>&laquo;</a></li>";
        page_str += "<li onclick=\""+func+"("+e+","+(page-1)+")\"><a>上一页</a></li>";
        for(var i=1;i<=total_page;i++){
            if(page == i){
                page_str += "<li class='active' onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
            }else{
                page_str += "<li onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
            }
        }
        page_str += "<li onclick=\""+func+"("+e+","+(page+1)+")\"><a>下一页</a></li>";
        page_str += "<li onclick=\""+func+"("+e+","+total_page+")\"><a>&raquo;</a></li>";
    }else{
        if(page<=3){
            var page_str = "<li onclick=\""+func+"("+e+",1)\"><a>&laquo;</a></li>";
//                page_str += "<li onclick=\""+func+"("+(page-1)+")\"><a>上一页</a></li>";
            for(var i=1;i<=5;i++){
                if(page == i){
                    page_str += "<li class='active' onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }else{
                    page_str += "<li onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }
            }
//                page_str += "<li onclick=\""+func+"("+(page+1)+")\"><a>下一页</a></li>";
            page_str += "<li onclick=\""+func+"("+e+","+total_page+")\"><a>&raquo;</a></li>";
        }else if(page>3 && page<= total_page-2){
            var page_str = "<li onclick=\""+func+"("+e+",1)\"><a>&laquo;</a></li>";
            for(var i=page-2;i<=(page+2);i++){
                if(page == i){
                    page_str += "<li class='active' onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }else{
                    page_str += "<li onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }
            }
            page_str += "<li onclick=\""+func+"("+e+","+total_page+")\"><a>&raquo;</a></li>";
        }else{
            var page_str = "<li onclick=\""+func+"("+e+",1)\"><a>&laquo;</a></li>";
            for(var i=total_page-4;i<=total_page;i++){
                if(page == i){
                    page_str += "<li class='active' onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }else{
                    page_str += "<li onclick=\""+func+"("+e+","+i+")\"><a>"+i+"</a></li>"
                }
            }
            page_str += "<li onclick=\""+func+"("+e+","+total_page+")\"><a>&raquo;</a></li>";
        }
    }
    $("#"+page_div).append(page_str);
}

