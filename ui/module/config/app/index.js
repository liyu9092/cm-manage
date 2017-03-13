/* 
* @Author: anchen
* @Date:   2015-10-16 14:28:21
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-16 20:05:37
*/

(function(){
    $(".wrapper").on('change','.switch>input',function(e){
		$(this).siblings('input[type="hidden"]').val(this.checked?"Y":"N");
        if($(this).attr('checked')){
            $(this).removeAttr('checked')
            $("td button.edit").attr("disabled","disabled");                  
        }else{
            $(this).attr('checked','checked');
            $("td button.edit").removeAttr("disabled");   
        }
		$(this).closest("form").submit();
    });

    $(".wrapper").on('click','.edit',function(e){
        $(this).closest("tr").find("span.score").hide();
        $(this).closest("tr").find("input[type=text]").show();
        $(this).hide();
        $(this).siblings().show(); 
        bindEvent(this); 
    })

    $(".wrapper").on('click','.cancel',function(e){
        e.preventDefault();
        lib.ajat('starconf/index#domid=table&tempid=table-t').render();                
    })

    $(".wrapper").on('click','.save',function(e){
        $(this).closest("tr").find('input').trigger('blur');
        if($(this).closest("tr").find('span.show').length>0) return;  
        var input = $(this).closest("tr").find('input');   
        lib.ajax({
            type: "post",
            data : {id:$(this).data('score'),score:input.val()},
            url : "starconf/update"
        }).done(function(data, status, xhr){
            if(data.result == 1){
                $(window).trigger('hashchange');
            }
        });
               
    })

    function bindEvent (t){
       $(t).closest("tr").on('blur','input',function(){
           if($(this).val()){
               var current = $(t).closest("tr");
               var prev    = current.prev();
               var next    = current.next();
               if(prev.length>0){
                   var prevValue = prev.find('input').val(); 
                   if(parseInt(prevValue) >= parseInt($(this).val())){
                        current.find(".control-help").text("积分必须大于前一个积分，小于后一个积分");
                        current.find(".control-help").addClass('show');
                        return;
                   }                  
               }
               if(next.length>0){
                   var nextValue = next.find('input').val(); 
                   if(parseInt(nextValue) <= parseInt($(this).val())){
                        current.find(".control-help").text("积分必须大于前一个积分，小于后一个积分");
                        current.find(".control-help").addClass('show');
                        return;
                   }                 
               }
               $(this).next().removeClass('show');
           }else{
               $(this).next().text("未填写");
               $(this).next().addClass('show');
           }
       })
    }

    $(".wrapper").on('keydown',"input[pattern='number']",function(e){
        var key = e.which;
        //alert(key)
        if ((key > 95 && key < 106) || //小键盘上的0到9  
            (key > 47 && key < 58) || //大键盘上的0到9  
            key == 8 || key == 116 || key == 9 || key == 46 || key == 37 || key == 39
            //不影响正常编辑键的使用(116:f5;8:BackSpace;9:Tab;46:Delete;37:Left;39:Right;)  
        ) {
            return true;
        } else {
            return false;
        }
    });
})();