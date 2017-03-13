/* 
* @Author: anchen
* @Date:   2015-10-20 14:51:19
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-23 18:53:12
*/

(function(){
    var type = lib.query.type;
    var flag = lib.query.itemType;

    if(!flag && type == 'add'){
        var baseData = JSON.parse(sessionStorage.getItem('add-base-data'));
        if(baseData.phoneList){
            if($.isArray(baseData.phoneList)){
                $('textarea.add').val(baseData.phoneList.join('\n'));          
            }else{
                var arr = baseData.phoneList.split(',');
                $('textarea.add').val(arr.join('\n'));     
            }     
        }
    }

    if(!flag && type == 'edit'){
        var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
        if(editData.phoneList){
            if($.isArray(editData.phoneList)){
                $('textarea.add').val(editData.phoneList.join('\n'));          
            }else{
                var arr = editData.phoneList.split(',');
                $('textarea.add').val(arr.join('\n'));     
            }     
        }
		$('textarea').attr("disabled",true);
		$('.btn-primary').hide();
    }

    if(flag){
        var previewData = "";
        if(type == 'add') previewData = JSON.parse(sessionStorage.getItem('add-base-data')); 
        if(type == 'edit') previewData = JSON.parse(sessionStorage.getItem('edit-base-data')); 
        if(previewData.phoneList){
            if($.isArray(previewData.phoneList)){
                $('textarea.add').val(previewData.phoneList.join('\n'));          
            }else{
                var arr = previewData.phoneList.split(',');
                $('textarea.add').val(arr.join('\n'));     
            }     
        }
        $('textarea.add').attr('disabled','disabled');     
        $("button.btn-primary").attr('disabled','disabled');
    }

    $(".btn-primary").on('click',function(){
        if(!$('textarea').val()) return;
        var arr = $('textarea').val().split('\n');
        var obj = {} , tempArr = [] ,lastArr = [],errArr = [];

        arr.forEach(function(item,i){
            if(!obj[item]){
                obj[item] = true;
            }
        });

        for(var s in obj){
           tempArr.push(s);
        }

        tempArr.forEach(function(item,i){
            if(/^1[0-9]{10}$/.test(item,i)){
                lastArr.push(item);
            }else{
                errArr.push(item);
            }
        });

        if(lastArr.length>0){
            if(type == 'add'){
                var baseData = JSON.parse(sessionStorage.getItem('add-base-data'));
                baseData.phoneList = lastArr;
                sessionStorage.setItem('add-base-data',JSON.stringify(baseData));  
            }

            if(type == 'edit'){
                var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
                editData.phoneList = lastArr; 
                sessionStorage.setItem('edit-base-data',JSON.stringify(editData));
            }

            parent.lib.popup.result({
                text:"手机号码添加成功",
                time:2000
            }); 
        }else{
            if(type == 'add'){
                var baseData = JSON.parse(sessionStorage.getItem('add-base-data'));
                baseData.phoneList = [];
                sessionStorage.setItem('add-base-data',JSON.stringify(baseData));    
            }

            if(type == 'edit'){
                var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
                editData.phoneList = [];
                sessionStorage.setItem('edit-base-data',JSON.stringify(editData));  
            }              
        }

        if(errArr.length > 0){
			var html = [];
            errArr.forEach(function(item,i){
                html.push(item);
            });
            $(".control-help").show().html("您的数据有"+errArr.length+"条添加失败，添加失败的手机号码如下：<br/>"+html.join("<br/>"));
			$(".control-success").hide();
        }else{
            $(".control-success").show();
			$(".control-help").hide();
			parent.lib.popup.result({text:"手机号码提交成功",define:function(){history.back()}});
        }                 
    })    
})()