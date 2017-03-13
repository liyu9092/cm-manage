/* 
* @Author: anchen
* @Date:   2015-07-03 11:12:48
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-22 13:46:34
*/

$(document).ready(function(){
    $("body").on("keydown","input[pattern='float']",function(e){
        var key = e.which;
        if ((key > 95 && key < 106) || //小键盘上的0到9  
            (key > 47 && key < 58) || //大键盘上的0到9  
            key == 8 || key == 116 || key == 9 || key == 46 || key == 37 || key == 39 || key == 190 || key == 110
            //不影响正常编辑键的使用(116:f5;8:BackSpace;9:Tab;46:Delete;37:Left;39:Right;190:大键盘.;110:小键盘.)  
        ) {
            return true;
        } else {
            return false;
        }   
    }).on('keypress',function(e){
        var len = $(this).attr('len') || 12;
        var decimal = $(this).attr('decimal') || 2;
        var str = $(this).val();
        if(str.length > len*1 + decimal*1){
            return false;
        }
    }).on('keyup',function(e){
        var len = $(this).attr('len') || 12;
        var decimal = $(this).attr('decimal') || 2;        
        var val = $(this).val();
        var index = val.indexOf(".");
        if(index == 0){
           $(this).val("");
        }
        if(index != val.lastIndexOf(".")){
           $(this).val(val.substring(0,val.length-1));
        }
        if(index > 0){
            var str = val.substring(index+1,val.length); 
            if(str.length > decimal){
               $(this).val(val.substring(0,val.length-1)); 
            }
        }
        if(index == -1){
            if(val.length > len){                     
                var last = val.substring(len,val.length);
                if(last != "."){
                   $(this).val(val.substring(0,len));
                }
            }
        }
    });

    $("body").on("keydown","input[pattern='number']",function(e){
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
});