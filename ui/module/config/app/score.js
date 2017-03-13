/* 
* @Author: anchen
* @Date:   2015-10-16 17:46:09
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-16 20:06:03
*/

(function(){
    $(".wrapper").on('click','.edit',function(e){
        $(this).closest("tr").find("span.num").hide();
        $(this).closest("tr").find("input").show();
        $(this).hide();
        $(this).siblings().show(); 
        bindEvent(this);                
    })

    $(".wrapper").on('click','.cancel',function(e){
        e.preventDefault();
        lib.ajat('scoreconf/index#domid=table&tempid=table-t').render();
    });

    $(".wrapper").on('click','.save',function(e){
        $(this).closest("tr").find("input").trigger('blur');
        if($(this).closest("tr").find("span.show").length>0) return;
        lib.ajax({
            type: "post",
            data : {
                'id':$(this).data('id'),
                'verySatisfy':$(this).closest("tr").find('input.vSatisfy').val(),
                'satisfy'    :$(this).closest("tr").find('input.satisfy').val(),
                'unsatisfy'  :$(this).closest("tr").find('input.unsatisfy').val()
            },
            url : "scoreconf/update"
        }).done(function(data, status, xhr){
            if(data.result == 1){
                $(window).trigger('hashchange');
            }
        });        
    })

    function bindEvent (t){
       $(t).closest("tr").on('blur','input',function(){
           if($(this).val()){
               $(this).next().removeClass('show');
           }else{
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