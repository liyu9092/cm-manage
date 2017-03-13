(function(){
    var type = lib.query.type;
    var selectItemType = lib.query.selectItemType || 1; 
    var status = lib.query.status;

    if(type == 'add'){
        var baseData = JSON.parse(sessionStorage.getItem('add-base-data')) || {};
        lib.ajat('#domid=form&tempid=form-t').template(baseData);    
    }

    if(type == 'edit'){
        var editData = JSON.parse(sessionStorage.getItem('edit-base-data'));
        lib.ajat('#domid=form&tempid=form-t').template(editData);
    }

    if(status == '3' || status == '4'){
        $("#form input,textarea,select").attr("disabled",'disabled');
    }

    $("#form").on('click',".flex-item a",function(e){
        e.preventDefault();
        location.href = $(this).attr('href')+"?type="+type+"&selectItemType="+selectItemType;        
    });

    lib.Form.prototype.save = function(data){
        data.manager = $("input[value="+data.managerId+"]").next().text();
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
        location.href = "addUser.html?type="+type+"&selectItemType="+selectItemType+"&status="+status;
    }    

})();