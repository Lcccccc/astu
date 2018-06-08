/**
 * Created by Lccccc on 2017/9/21.
 */
function get_case_list(e,page){
    $.ajax({
        url: '/home/Apidata/get_case_list',//这个就是请求地址对应sAjaxSource
        data : {
            tree_id : e,
            page:page,
            num:8
        },
        type: 'POST',
        dataType: 'json',
        async: true,
        success: function (ret) {
            var data = ret.list;
            var data1 = data.slice(0, 4);
            var data2 = data.slice(4,8);
            var html_str1 = '';
            for( var i in data1){
                html_str1 += " <div onclick='window.open(\"/home/index/contentDetail?type=article&id="+data1[i]['id']+"\")' class=\"col-xs-12 col-sm-6 col-md-6 col-lg-3 text-center\"><dl><dt> " +
                    "<img class=\"\" src=\""+data1[i]['strImgUrl']+"\"></dt><dd>" +
                    "<p>"+data1[i]['strTitle']+"</p><p class=\"public-btn\">" +
                    "<a target=\"_blank\"  class=\"btn btn-orange hidden\" href=\"/home/index/contentDetail?type=article&id="+data1[i]['id']+"\" role=\"button\">查看</a></p></dd></dl></div>";
            }
            var html_str2 = '';
            for( var j in data2){
                html_str2 += " <div onclick='window.open(\"/home/index/contentDetail?type=article&id="+data2[j]['id']+"\")'  class=\"col-xs-12 col-sm-6 col-md-6 col-lg-3 text-center\"><dl><dt> " +
                    "<img class=\"\" src=\""+data2[j]['strImgUrl']+"\"></dt><dd>" +
                    "<p>"+data2[j]['strTitle']+"</p><p class=\"public-btn\">" +
                    "<a target=\"_blank\"  class=\"btn btn-orange hidden\" href=\"/home/index/contentDetail?type=article&id="+data2[j]['id']+"\" role=\"button\">查看</a></p></dd></dl></div>";
            }
            $('#tab1 .row')[0].innerHTML = html_str1;
            $('#tab1 .row')[1].innerHTML = html_str2;
            changepage('page_info',ret.page,ret.total_page,ret.total_num,'get_case_list',e);
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

