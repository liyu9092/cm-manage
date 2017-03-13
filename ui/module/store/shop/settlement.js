(function(){
    var type = lib.query.type;

    if(type === 'edit'){
        var data = JSON.parse(sessionStorage.getItem('edit-shop-data'));       
        lib.ajat('#domid=form&tempid=form-t').template(data);     
    }
    
    if(type === 'add'){
        var shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
        lib.ajat('#domid=form&tempid=form-t').template(shopData);
        document.body.onbeforeunload=function(){
            return "确定离开当前页面吗？";
        }       
    }   

    $(".flex-item a").on('click',function(e){
        e.preventDefault();
        location.href = $(this).attr('href') + "?type="+type;
    });

    $("#preview-btn").on('click',function(){
        var data = lib.getFormData($("#form"));
        if(type === 'edit') var shopData = JSON.parse(sessionStorage.getItem('edit-shop-data'));
        if(type === 'add')  var shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
        shopData = $.extend({},shopData,data);
        sessionStorage.setItem('preview-shop-data',JSON.stringify(shopData));
        window.open("detail.html?type=preview");
    })

    lib.Form.prototype.save = function(data){
        var shopData = "";
        if(type === 'edit'){
            shopData = JSON.parse(sessionStorage.getItem('edit-shop-data'));
            shopData = $.extend({},shopData,data); 
        }
        if(type === 'add'){
            shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
            shopData = $.extend({},shopData,data);               
        }
        submit(shopData);
        document.body.onbeforeunload=function(){}
    }

    var submit = function(shopData){
        if($.isArray(shopData.contractPicUrl)) 
            shopData.contractPicUrl = JSON.stringify(shopData.contractPicUrl);
        if($.isArray(shopData.licensePicUrl)) 
            shopData.licensePicUrl = JSON.stringify(shopData.licensePicUrl);
        if($.isArray(shopData.corporatePicUrl)) 
            shopData.corporatePicUrl = JSON.stringify(shopData.corporatePicUrl);
        if($.isArray(shopData.salonLogo)){
            if(shopData.salonLogo.length > 0){
                shopData.logo = shopData.salonLogo[0].thumbimg; 
                shopData.salonLogo = JSON.stringify(shopData.salonLogo);                           
            }else{
                shopData.logo = ""; 
                shopData.salonLogo = "";                  
            }
        }
        if($.isArray(shopData.salonImg)){
            if(shopData.salonImg.length > 0){
                shopData.salonImg = JSON.stringify(shopData.salonImg);               
            }else{
                shopData.salonImg = "";
            }
        } 
        if($.isArray(shopData.workImg)){
            if(shopData.workImg.length > 0){
                shopData.workImg = JSON.stringify(shopData.workImg);                  
            }else{
                shopData.workImg = "";
            }
        } 
        $('form').attr('disabled',true);
        lib.ajax({
            type: "post",
            url : (type=="add"?"salon/save":"salon/update"),
            data: shopData    
        }).done(function(data, status, xhr){
            parent.lib.popup.result({
                bool:data.result == 1,
                text:(data.result == 1?"店铺信息提交成功":data.msg),
                define:function(){
                    if(data.result == 1){
                        sessionStorage.removeItem('add-shop-data');
                        sessionStorage.removeItem('edit-shop-data');
                        sessionStorage.removeItem("preview-shop-data");   
                        document.body.onbeforeunload=function(){}
                        if(type === "edit") 
                            location.href="/module/store/shop/detail.html?type=detail&salonid="+shopData.salonid;
                        if(type === "add") 
                            location.href="/module/store/merchant/index.html";
                    }
                }
            });
           setTimeout(function(){
            $('form').attr('disabled',false);
           }, 2000);
        });
    } 
    
})()