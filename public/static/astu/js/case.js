$.ajax({
    url: '/home/Apidata/get_index_case',//这个就是请求地址对应sAjaxSource
    data : {
        tree : 'cases'
    },
    type: 'POST',
    dataType: 'json',
    //async: true,
    success: function (result) {
        var data = result.ret;
        var data1 = Object.assign({}, {
            eachCase: [data.slice(0, 4)]
        });
        var data2 = Object.assign({}, {
            eachCase: [data.slice(4,7)]
        });
        var html = template('tab-container', data1),
            html2 = template('tab-container', data2);
        $('#tab1 .row')[0].innerHTML = html;
        $('#tab1 .row')[1].innerHTML = html2;
    },
    error:function(XMLHttpRequest, textStatus, errorThrown) {
        alert("status:"+XMLHttpRequest.status+",readyState:"+XMLHttpRequest.readyState+",textStatus:"+textStatus);
    }
});