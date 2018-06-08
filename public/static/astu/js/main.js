//index.js
$('.intro dl').mouseenter(function () {
	$(this).find("span").addClass('orangered')
	$(this).find(".btn").removeClass('hidden');
});
$('.intro dl').mouseleave(function () {
	$(this).find("span").removeClass('orangered')
	$(this).find(".btn").addClass('hidden');
});


//case.js鼠标移入和移出的特效

$('.tab-content').delegate('.text-center', 'mouseenter', function () {
	var div = "<div class='case-bg'><div class='circle'><span class='glyphicon glyphicon-plus'></span></div></div>";
	$(this).find("dt").append(div)
	$(this).find(".btn").removeClass('hidden');
})
$('.tab-content').delegate('.text-center', 'mouseleave', function () {
	$(this).find(".case-bg").remove();
	$(this).find(".btn").addClass('hidden');
})


$('.talking dl').mouseenter(function () {
	$(this).find("h4").addClass('orangered');
});
$('.talking dl').mouseleave(function () {
	$(this).find("h4").removeClass('orangered');
});
$('.zan').click(function () {
	$(this).toggleClass('zan-tab')
});
//案例case.js
$('.case-ul span').click(function () {
	var toggle = $(this).data('target');
	$('.case-ul-span').find('b').remove();
	$(this).append('<b class="animated slideInLeft"></b>')
	$('.tab').hide();
	$('#' + toggle).show();
});
//白话
$('.talking-ul span').click(function () {
	var toggle = $(this).data('target');
	$('.talking-ul-span').find('b').remove();
	$(this).append('<b class="animated slideInLeft"></b>')
	$('.tab').hide();
	$('#' + toggle).show();
});
//动画效果
function animated(target) {
	$(target).mouseenter(function () {
		$(this).find('img').addClass('animated rotateIn')
	})
	$(target).mouseleave(function () {
		$(this).find('img').removeClass('animated rotateIn')
	})
}

animated('.we-ul dl');
//animated('.company-info dl');
animated('.recruit-padding dl');

$(".navbar-default .navbar-nav li a").on("mouseenter", function () {
	var menutext = $(this).text();
	if(menutext == '首页'){
		$(this).text('HOME');
	}else if(menutext == '服务'){
		$(this).text('SERVICE');
	}else if(menutext == '案例'){
		$(this).text('CASE');
	}else if(menutext == '我们'){
		$(this).text('WE');
	}else if(menutext == '白话'){
		$(this).text('TALKING');
	}else if(menutext == '合作'){
		$(this).text('COOPERATION');
	}else if(menutext == '招聘'){
		$(this).text('RECRUIT');
	}
});
$(".navbar-default .navbar-nav li a").on("mouseout", function () {
	var menutext = $(this).text();
	if(menutext == 'HOME'){
		$(this).text('首页');
	}else if(menutext == 'SERVICE'){
		$(this).text('服务');
	}else if(menutext == 'CASE'){
		$(this).text('案例');
	}else if(menutext == 'WE'){
		$(this).text('我们');
	}else if(menutext == 'TALKING'){
		$(this).text('白话');
	}else if(menutext == 'COOPERATION'){
		$(this).text('合作');
	}else if(menutext == 'RECRUIT'){
		$(this).text('招聘');
	}
});
$(function(){

    var nav = $('.navbar-default'),
        doc = $(document),
        win = $(window);

    win.scroll(function() {
        if (doc.scrollTop() > 100 || window.location.pathname=='/home/index/contentDetail') {
            nav.addClass('scrolled');
            $("#logoimg").attr('src',"/static/astu/images/logo1.png");
            $("#qqpng").attr('src',"/static/astu/images/qq1.png");
            $("#wepng").attr('src',"/static/astu/images/wechat1.png");
        } else {
            nav.removeClass('scrolled');
            $("#logoimg").attr('src',"/static/astu/images/logo.png");
            $("#qqpng").attr('src',"/static/astu/images/qq.png");
            $("#wepng").attr('src',"/static/astu/images/wechat.png");
        }

    });

    win.scroll();
});


//招聘.js 显示信息
$(".company-info dl").popover({
		placement: 'bottom',
		html: true
	})
	.on("mouseenter", function () {
		// 失去焦点时显示
		$(this).popover('show');
	})
	.on("mouseleave", function () {
		// 失去焦点时显示
		$(this).popover('hide');
	});
//获取焦点关闭弹窗
function closeDiv() {
	$('.alert').addClass('hidden')
}


//提交表单 cooperation.html

function submitCooperation() {
	var name = document.getElementById("name").value;
	var company = document.getElementById("company").value;
	var phone = document.getElementById("phone").value;
	var qqNumber = document.getElementById("qq-number").value;
	var summary = document.getElementById("summary").value;
	function alertDiv(text) {
		$('.alert').removeClass('hidden')
		$('.alert-content').text(text)
		$('.close').click(function () {
			$('.alert').addClass('hidden')
		})
	}
	// 正则表达式验证手机号输入是否正确
	function checkphone(str) {
		var pattern = /^1[3|4|5|7|8][0-9]{9}$/;
		if (pattern.test(str)) {
			//alert('号码可以使用');
		} else {
			alertDiv('请输入正确的手机号码！');
			return false;
		}
	}

	if (name == "") {
		alertDiv("用户名不能为空")
		return false;
	} else if (company == "") {
		alertDiv("公司不能为空");
		return false;
	} else if (phone == "") {
		alertDiv("手机不能为空");
		return false;
	} else if (qqNumber == "") {
		alertDiv("qq不能为空");
		return false;
	} else if (summary == "") {
		alertDiv("概述不能为空");
		return false;
	} else if(!checkphone(phone)){
		return false;
	}
	else {
		//alert('表单数据：' + name + '-' + company + '-' + phone + '-' + qqNumber + '-' + summary)
        $.ajax({
             url:'/home/index/submit_cooperation',
             type:'POST',
             data:{
                 name : name,
                 company : company,
                 phone :phone,
                 qq:qqNumber,
                 summary:summary
             },
             success:function(ret){
                 if(ret.code=='200'){
                     alert('需求提交成功');
                     history.go(0)
                 }else{
                     alert(ret.msg);
                 }

             },
             error:function(err){
                 console.log(err);
             }
        })
	}
}



//关于分享的
//点击share分享详情页
$('#mainContent').delegate('i.share', 'click', function () {
	//console.log($(this).parents('#mainContent').children($('.row')).children($('.hot-talking-content')));
	$(this).parents('#mainContent').children($('.row')).children($('.hot-talking-content')).find('#bdshare').css('display', 'none');
	$('i.share').css('backgroundImage', 'url(/static/astu/images/share_ico.png)');
	$(this).css('backgroundImage', 'url(/static/astu/images/share_ico1.png)');
	// console.log($(this))
	$(this).parents('.hot-talking-content').css('position', 'relative');
	$(this).parents('.hot-talking-content').children('#bdshare').css({
		display: 'flex',
		justifyContent: 'flex-end',
		position: 'absolute',
		top: $(this)['0'].offsetTop - 25,
		right: 10
	});
})

//百度分享 share.baidu.com

// var url = window.location.href;
// console.log(url)
// window._bd_share_config = {
// 	common : {
// 		bdText : '自定义分享内容',	
// 		bdDesc : '自定义分享摘要',	
// 		bdUrl : url, 	
// 		bdPic : '自定义分享图片'
// 	},
// 	share : [{
// 		"bdSize" : 16
// 	}],
// 	slide : [{	   
// 		bdImg : 0,
// 		bdPos : "right",
// 		bdTop : 100
// 	}]
// }
// with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?cdnversion='+~(-new Date()/36e5)];



//判断屏幕的高度控制轮播图的高度的控制
function getHeight() {
	var h = $('html')[0].clientHeight,bodyWidth = $('body').width();
    //console.log(h);
	if(window.location.pathname=='/home/index/index' || window.location.pathname=='/'){
		$('.carousel').css({
			'height': h
		});
		$('.carousel .item').css('height', h);
		$('.carousel-inner > .item > img').css('height', h);
	}
	if(bodyWidth < 750){
		$('.navbar-header').css('width',bodyWidth);
	}else{
		$('.navbar-header').css('width','auto');
	}
}
$(function ($) {
	getHeight();
	resetCSS();
	$(window).resize(function () {
		getHeight();
		resetCSS();
	})
})

//nav 的样式

function resetCSS(){
	var h = $('.navbar-default').height();
	$('.navbar-header').css('height',h);
	$('ul.navbar-nav').css('height',h);
	$('.navbar-brand').css('height',h);
	$('.nav-qq').css('height',h);
	// $('.navbar-nav').find('li').eq(7).css('height',h);
	var clientH = $('.navbar-toggle')[0].clientHeight ;
	$('.navbar-toggle').css('margin-top',(h - clientH)/2);
}

$(document).ready(function(){
	//e 事件对象，可以通过该事件对象获取事件的参数 e.pageX - X轴，距浏览器的左边的距离 e.pageY - y轴，距浏览器的顶端的距离
	$("#weilogo").mouseover(function(e){
		//鼠标移上去 向body追加大图元素
		//大图的路径：当前连接的href属性值为大图的路径
		var $imgSrc = $(this).attr("href");
		var $maxImg ="<div id='maxpic'><img width='100%' src='"+$imgSrc+"'></div>";console.log($maxImg);
		//在body中添加元素
		//$("body").append($maxImg);
		$("#bs-example-navbar-collapse-1").append($maxImg);
		//设置层的top和left坐标，并动画显示层
		$("#maxpic").css("top", e.pageY+20).css("left",e.pageX-200).show();
	}).mouseout(function(){
		//鼠标移开删除大图所在的层
		$("#maxpic").remove();
	}).mousemove(function(e){
		//鼠标移动时改变大图所在的层的坐标
		$("#maxpic").css("top", e.pageY+20).css("left",e.pageX-200);
	});
});


//轮播图当前显示图片设置动画效果
$(".carousel .item").each(function(){
	if(window.location.pathname=='/home/index/index' || window.location.pathname=='/'){
		var that=$(this);
		//that.find("img").removeClass("moveAniAct");
		//if(that.hasClass("active")){
			that.find("img").addClass("moveAniAct");
		//}
	}
});

//获取每个标题最大高度 并赋值给所有
var t_max = 0;
//求最大高度
$(".hot-talking-content h4").each(function() {
    var th = $(this).innerHeight();
    t_max = th > t_max ? th : t_max;
});
//将class的高度赋值为最大高度，
//最大高度innerheight=padding+内容高度height
$(".hot-talking-content h4").each(function() {
    //求padding的值
    var t_pad = $(this).innerHeight() - $(this).height();
    $(this).height(t_max - t_pad);
});

var c_max = 0;
//求最大高度
$(".team-content p").each(function() {
    var ch = $(this).innerHeight();
    c_max = ch > c_max ? ch : c_max;
});
//将class的高度赋值为最大高度，
//最大高度innerheight=padding+内容高度height
$(".team-content p").each(function() {
    //求padding的值
    var c_pad = $(this).innerHeight() - $(this).height();
    $(this).height(c_max - c_pad);
});


