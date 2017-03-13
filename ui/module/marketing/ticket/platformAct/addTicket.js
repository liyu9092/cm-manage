/* 
* @Author: anchen
* @Date:   2015-10-19 17:28:25
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-29 15:18:24
*/

(function(){
    var type = lib.query.type;

    var selectItemType = lib.query.selectItemType; 

    var status = lib.query.status; 

    if(type == 'add'){
        var baseData = JSON.parse(sessionStorage.getItem('add-base-data'));
        lib.ajat('#domid=form&tempid=form-t').template(baseData);     
    }

    if(type == 'edit'){
        var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
        lib.ajat('#domid=form&tempid=form-t').template(editData);
    }

    if(status == '3' || status == '4'){
        $("#form input,textarea,select,radio").attr("disabled",'disabled');
        $("button.btn-primary").hide();
    }    

    /**
     * 券总数操作事件
     * @return {[type]} [description]
     */
    $("#form").on('click','input[name=checkTotalNumber]',function(){
        if($(this).val()=="1"){
           $("#ticketNumInput").removeAttr('disabled','disabled');
           $("#ticketNumHidden").attr('disabled','disabled');
        }else{
           $("#ticketNumInput").attr('disabled','disabled');
           $("#ticketNumHidden").removeAttr('disabled');
        }
    })

    $("#form").on('input','.nonzero',function(){
        if($(this).val() == "0"){
          $(this).val("");
        }
    })

    $("#form").on('change','#smsControl',function(){
        if($(this).attr('checked')){
           $(this).removeAttr('checked');
           $("#smsTextArea").attr('disabled','disabled');
        }else{
           $(this).attr('checked','checked');
           $("#smsTextArea").removeAttr('disabled');
        }
    })

    $("#form").on('change','input[name=getTimeStart]',function(){
        if($(this).val()){
            $("#avaDateStart").attr('min',$(this).val());
            var d1 = new Date($(this).val());
            var d2 = new Date($("#avaDateStart").val());
            if(d2 < d1){
                $("#avaDateStart").val($(this).val());
            }        
        }        
    });

    $("#form").on('change','#avaDateStart',function(){
        if($(this).val()){
            $("input[name=getTimeStart]").attr('max',$(this).val());
            var d1 = new Date($(this).val());
            var d2 = new Date($("input[name=getTimeStart]").val());
            if(d2 > d1){
                $("input[name=getTimeStart]").val($(this).val());
            }        
        }        
    });

    $("#form").on('change','input[name=getTimeEnd]',function(){
        if($(this).val()){
            $("#avaDateEnd").attr('min',$(this).val()); 
            var d1 = new Date($(this).val());
            var d2 = new Date($("#avaDateEnd").val());
            if(d2 < d1){
                $("#avaDateEnd").val($(this).val());
            }                      
        }         
    });

    $("#form").on('change','#avaDateEnd',function(){
        if($(this).val()){
            $("input[name=getTimeEnd]").attr('max',$(this).val()); 
            var d1 = new Date($(this).val());
            var d2 = new Date($("input[name=getTimeEnd]").val());
            if(d2 > d1){
                $("input[name=getTimeEnd]").val($(this).val());
            }                      
        }         
    });

    $("#form").on('change','input.start',function(){
        var td = $(this).parent();
        if($(this).val()){
            td.find('input.end').attr('min',$(this).val());          
        }
    })

    $("#form").on('change','input.end',function(){
        var td = $(this).parent();
        if($(this).val()){
            td.find('input.start').attr('max',$(this).val());          
        }
    })

    $("#form").on('click','.avaDateRadio',function(){
        if($(this).val()=="1"){
           $("#avaDateStart").removeAttr('disabled');
           $("#avaDateEnd").removeAttr('disabled');
           $("#avaDay").attr('disabled','disabled');
        }else{
           $("#avaDateStart").attr('disabled','disabled');
           $("#avaDateEnd").attr('disabled','disabled');
           $("#avaDay").removeAttr('disabled');
        }
    })

    $("#form").on('click','#preview-btn',function(){
        var data = lib.getFormData($("#form"));  
        if(type === 'add')  var previewData = JSON.parse(sessionStorage.getItem('add-base-data'));
        if(type === 'edit') var previewData = JSON.parse(sessionStorage.getItem('edit-base-data'));
        previewData = $.extend({},previewData,data);
        sessionStorage.setItem('preview-base-data',JSON.stringify(previewData));
        window.open("preview.html?type="+type);       
    })

    lib.Form.prototype.save = function(data){   
        if(!data.limitItemTypes){
           data.limitItemTypes = [""];            
        }
        if(!data.useLimitTypes){
           data.useLimitTypes = [""];            
        }
        if(!data.sendSms){
            data.sendSms = "";
        }
        if(type == 'add'){    
            var addData = JSON.parse(sessionStorage.getItem('add-base-data'));
            addData = $.extend({},addData,data);
            sessionStorage.setItem('add-base-data',JSON.stringify(addData));             
        }
        if(type == 'edit'){
            var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
            editData = $.extend({},editData,data);
            sessionStorage.setItem('edit-base-data',JSON.stringify(editData));
        }   
        location.href = "preview.html?type="+type+"&selectItemType="+selectItemType;
    }      
})()