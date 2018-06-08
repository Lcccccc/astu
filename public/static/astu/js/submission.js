$(function ($) {
    changeCss()
    $(window).resize(function(){
        changeCss();
    })

})


// 监听窗口大小的改变，修改样式
function changeCss(){
    var marginLeft = $('.sectionArticlePhoto')[0].clientWidth - $('.replacePhoto')[0].clientWidth - $('.margin-left.articlePhoto')[0].children[0].clientWidth;
    $('.replacePhoto').css('margin-left', marginLeft/2 );

    var marginLeft2 = ($('.sortArticleName')[0].clientWidth)/2 - $('.selectName')[0].clientWidth - $('.sortArticleName').find('label')[0].clientWidth;
    $('.selectName').css('width',($('.sortArticleName')[0].clientWidth)/2)
    

    var Left = $('.replacePhoto')[0].clientWidth - $('.replacePhoto').find('i')[0].clientWidth;
    var Height = $('.replacePhoto')[0].clientHeight - $('.replacePhoto').find('i')[0].clientHeight;
    $('.replacePhoto').css('position','relative').find('i').css({
        "position":'absolute',
        'top':Height/2,
        'left':Left/2
    });
}
 //验证输入的邮箱格式是否正确
 function checkEmail(str) {
    const pattern = /^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/;
    if (pattern.test(str)) {
        //alert('邮箱验证正确，可以提交！')

    } else {
        $('button').attr('disabled','disabled');
    }
}
$('button').click(function () {
    //正则表达式验证邮箱
    console.log('-----')
    checkEmail($('.email').val());
    $("#form-content").submit();
})
$('input.email').focus(function(){
    $('.yourEmail').css('border','1px solid #dfdfdf');
    $('button').removeAttr('disabled');
});

$('.articlePhoto').delegate('.replacePhoto','click',function(){
    $(this).siblings('input').click()
})
//选择完图片，点击打开，触发input的change事件
$('.articlePhoto').delegate('input','change',function(){
    //console.log(this)
    var file = this.files[0]
    // 判断上传的是否是图片
    if(!/image\/\w+/.test(file.type)){
        alert("文件必须为图片！");
        return false;
    }
    //修改对应img标签的src属性
    $(this).next('.replacePhoto').find('img').attr('src',URL.createObjectURL(file)).siblings('i').css('display','none')
   
})

// 上传form表单的数据
//var formdata = new FormData($('#form-content')[0]);
//$.ajax({
//     url:'/home/index/get_submission',
//     type:'POST',
//     data:formdata,
//     dataType:'json',
//     async:false,
//     cache:false,
//     contentType:false,
//     processData:false,
//     success:function(data){
//         console.log(data);
//     },
//     error:function(err){
//         console.log(err);
//     }
//})