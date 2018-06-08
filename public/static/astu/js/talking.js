//talking.js
//模拟数据
var date = new Date();
var year = date.getFullYear();
var month = date.getMonth() + 1;
var day = date.getDate();
month = month < 10 ? '0' + month : month;
day = day < 10 ? '0' + day : day;
var optionDate = year + '/' + month + '/' + day;
var data = {
	hotTalking: [{
			imgSrc: '/static/astu/images/talk_1.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_2.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_3.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_4.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_5.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_6.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_7.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_8.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		},
		{
			imgSrc: '/static/astu/images/talk_9.png',
			title: '“会呼吸”的软欧巴	，从台湾飘洋过海来见你',
			time: optionDate,
			concern: '24561'
		}
	]
}
//切换标题
$('.talking-ul span').click(function () {
	var id = $(this).data('id');
	var text = $(this).text();
	callBackPagination(text, id)
	$('.talking-ul-span').find('b').remove();
	$(this).append('<b class="animated slideInLeft"></b>');
	if (id == 7) {
		window.location.href = 'submission.html';
	} else if (id == 5) {
		window.location.href = 'talking-design.html';
	} else if (id == 2) {
		window.location.href = 'disclose.html';
	} else if (id == 3) {
		window.location.href = 'red-museum.html';
	} else if (id == 4) {
		window.location.href = 'technology.html';
	}
	// else if(id==6){
	// 	window.location.href = 'disclose.html';
	// }
})
//分页
function callBackPagination(targetTitle, targetID) {
	console.info('文章标题:' + targetTitle + '——文章id:' + targetID)
	var totalCount = Number($('#totalCount1').val()) || 252,
		showCount = $('#showCount1').val(),
		limit = Number($('#limit').val()) || 10;
	createTable(1, limit, totalCount);
	$('#callBackPager').extendPagination({
		totalCount: 99, //总数
		showCount: 9, //分页栏展示数
		limit: 8, //每页显示数据量
		callback: function (curr, limit, totalCount) {
			createTable(curr, limit, totalCount);
		}
	});
}
callBackPagination('全部文章', 1);

function createTable(currPage, limit, total) {
	var html = '',
		showNum = limit;
	if (total - (currPage * limit) < 0) showNum = total - ((currPage - 1) * limit);
	console.log('当前页:' + currPage)
}

//art-template  
var html = template('hot-talking', data);
$('#mainContent').empty();
$('#mainContent').html(html);

//移入
$('.talking dl').mouseenter(function () {
	$(this).find("h4").addClass('orangered');
});
$('.talking dl').mouseleave(function () {
	$(this).find("h4").removeClass('orangered');
});
//点赞
$('.mainContent').delegate('i.zan', 'click', function () {
	$(this).toggleClass('zan-tab');
})


//找到id名是bdshare的标签，添加data属性，
$(function($){
	var html = window.location.href ;
	$('#bdshare').attr('data',{
		url:html
	})
})