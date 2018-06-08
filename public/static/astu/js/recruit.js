$(".col-xs-12.col-sm-6.col-md-6.col-lg-6").on('mouseenter',function(){
    // console.log('鼠标移入，但是没有点击')
    $(this).find('.send-button').addClass('show');

});
$(".col-xs-12.col-sm-6.col-md-6.col-lg-6").on('mouseleave',function(){
    // console.log('鼠标移出，没有点击')
    $(this).find('.send-button').removeClass('show');
});

$('.col-xs-12.col-sm-6.col-md-6.col-lg-6').delegate('.send-button', 'click', function () {
    // console.log('鼠标点击了，此时，button应该去掉show类,还应该清除事件监听');
    $(this).removeClass('show');$(this).parents('.col-xs-12.col-sm-6.col-md-6.col-lg-6').off('mouseenter');
    $(this).parent('.form-group').find('label,div.emailInput,button.submit-button').css('display', 'block');
    var $that = $(this);
    //正则表达式验证邮箱
    function checkEmail(str) {
        const pattern = /^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/;
        if (pattern.test(str)) {
            $.ajax({
                url:'/home/index/submit_recruit',
                type:'POST',
                data:{
                    email:$that.parent('.form-group').find('input.form-control').val(),
                    station:$that.parent('.form-group').find("input[name='strStation']").val()
                },
                dataType:'json',
                success:function(ret){
                    if(ret.code == 200){
                        var html = '';
                        html += '<button type="button" class="col-md-3 button completed-button col-sm-4 col-xs-3 ">已投递</button>';
                        $that.parent('.form-group').html(html);
                        $that.parents('.col-xs-12.col-sm-6.col-md-6.col-lg-6').off('mouseleave');
                    }
                },
                error:function(err){
                    console.log(err);
                }
            });
            // console.log('清除事件监听成功！！！！')
        } else {
            $that.parent('.form-group').find('input.form-control').css('border', '1px solid red');
            $that.parent('.form-group').find('.submit-button').attr('disabled', 'disabled').css('backgroundColor', 'gray')
        }
    }
    $that.parent('.form-group').find('.submit-button').click(function () {
        checkEmail($that.parent('.form-group').find('input.form-control').val());
    });
    $that.parent('.form-group').find('input.form-control').focus(function () {
        $('.submit-button').removeAttr('disabled').css('backgroundColor', '#fa6c16');
        $('input.form-control').css('border', '1px solid #cccccc');
    })
});