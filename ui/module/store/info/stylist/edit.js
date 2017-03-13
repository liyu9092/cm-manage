/* 
* @Author: anchen
* @Date:   2015-10-09 10:53:59
* @Last Modified by:   anchen
* @Last Modified time: 2015-12-28 10:27:23
*/

(function(){       
    if(lib.query.id){
        $("#title").text("编辑造型师");
        var promise = lib.ajat("stylist/show/"+lib.query.id+"#domid=form&tempid=form-t").render();
        promise.done(function(data){                
            var arr = [];
            if(data.data && data.data.img) {                    
                arr.push(JSON.parse(data.data.img));
            }else{
                arr.push({'thumbimg':data.data.stylistImg,'img':data.data.stylistImg,'type':1});
            }
            initUploader(arr);
        });
    }

    if(lib.query.salonid){
        $("#title").text("新增造型师-"+lib.query.salonname);
        lib.ajat("#domid=form&tempid=form-t").template({});   
        initUploader([]);    
    }
    
    function initUploader(arr){
        lib.puploader.image({
            browse_button: 'personImagesUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            imageArray:arr,
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                var ratio = new Number(420/492).toFixed(2);
                lib.cropper.create({
                    src:response.img,
                    aspectRatio : ratio,
                    thumbnails  : ["420x492"],
                    define:function(data){
                        if(up.createThumbnails&&!response.edit){
                            up.createThumbnails({
                                thumbimg:data["420x492"],
                                img:response.img,
                                type : 1
                            });
                            $("#form .upload-image-tip").hide();
                        }
                    }
                });            
            });
        });
    }

    $("#form").on('click','.control-thumbnails-edit',function(){
        var item  = $(this).closest('.control-thumbnails-item');
        lib.cropper.create({
            src:item.find('img').data('original'),
            aspectRatio :new Number(420/492).toFixed(2),
            thumbnails  : ["420x492"], 
            define:function(data){
                item.find('img').attr('src',data["420x492"]);
                item.find('input.thumb').val(data["420x492"]); 
            }                     
        });
    });

    $("#form").on('click','.tab td',function(){
        $(this).children().css('opacity','1');
    });

    $("#form").on('click','input[type="checkbox"]',function(){   
        if($(this).val() == "1"){
            lib.popup.alert({text:'此造型师有未完成的赏金单，请完成后再更换店铺!'});
            return false;
        }  
        if($(this).attr("checked")){
            $(this).removeAttr("checked");
            $("#search").attr("disabled",true);     
        }else{  
            $(this).attr("checked",'true');
            $("#search").removeAttr("disabled"); 
        }
    });

    $("#form").on('keydown','input[pattern=number]',function(e){   
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
   

    $('#form').on('autoinput','#search',function(e,data){
        if(data){
            $("#salonname").val($(this).val());
        }
    });

    $('#form').on('change','input.start',function(){
        var flag = false;
        var arrTd = $(this).closest("tr").children();
        arrTd.each(function(index,obj){
            if($(obj).children().val()){
                flag = true; 
            }            
        });

        if(flag){
            arrTd.each(function(index,obj){
                if($(obj).children().attr('type')=="date"){
                    $(obj).children().addClass("show");
                    $(obj).children().attr("requiredOther",true); 
                }else{
                    $(obj).children().attr("required",true);                    
                }
            })
            if($(this).attr('type')=="date" && $(this).val()){
                $(this).next() && $(this).next().remove();                 
            }
        }else{
            arrTd.each(function(index,obj){
                $(obj).children().removeAttr("required");
                $(obj).children().removeAttr("requiredOther");
                $(obj).children().next() && $(obj).children().next().remove();
            })            
        }
    });

    $("#form").on('change','input.min',function(){
        var minDate = new Date($(this).val());
        var input   = $(this).parent().next().find('input.max');
        if(input.val()){
            var maxDate = new Date(input.val());
            if(minDate > maxDate){
                input.val("");
            }
        }
        input.attr("min",$(this).val());
    });

    $("#form").on('change','input.max',function(){
        var maxDate = new Date($(this).val());
        var input   = $(this).parent().prev().find('input.min');
        if(input.val()){
            var minDate = new Date(input.val());
            if(minDate > maxDate){
                input.val("");
            }
        }
        input.attr("max",$(this).val());
    })    

    $('#form').on('blur','input[type=date]',function(){       
        if($(this).attr("requiredOther")){
            var arrTd = $(this).closest("tr").children();
            arrTd.each(function(index,obj){
                if($(obj).children().attr('type')=="date"){
                    if($(obj).children().val()){
                        $(obj).children().next() && $(obj).children().next().remove();
                    }else{
                        $(obj).children().next() && $(obj).children().next().remove();
                        $(obj).children().after('<span class="control-help" style="display:inline-block!important;">未填写</span>');
                    }
                }
            })
        }
    })

    function checkedImage(){
        var len = $("#form .control-thumbnails-item img").length;
        if(len){
            return true;
        }else{
            $("#form .upload-image-tip").show();
            return false;
        }
    }

    lib.Form.prototype.save = function(data){        
        if(!checkedImage()) return;
        var img = {}; 
        var element = $(".control-thumbnails-item img");
        img['thumbimg'] = element.attr('src');
        img['img']      = element.data('original');
        img['type']     = element.data('type');
        data.stylistImg = element.attr('src');
        data['img'] = JSON.stringify(img);
        data[data.cardType] = data.cardNum;
        delete data.cardType;
        delete data.cardNum; 
        var workExp = {};
        var educateExp = {};
        for(var i = 0; i < 6 ; i++){
            educateExp['sTime'+i]=data['sTime'+i]; 
            delete data['sTime'+i];
            educateExp['eTime'+i]=data['eTime'+i];
            delete data['eTime'+i];
            educateExp['name'+i]=data['name'+i]; 
            delete data['name'+i];

            workExp['wsTime'+i]=data['wsTime'+i]; 
            delete data['wsTime'+i];
            workExp['weTime'+i]=data['weTime'+i]; 
            delete data['weTime'+i];
            workExp['wname'+i]=data['wname'+i]; 
            delete data['wname'+i];
            workExp['wjob'+i]=data['wjob'+i]; 
            delete data['wjob'+i];
            workExp['waddress'+i]=data['waddress'+i];
            delete data['waddress'+i];
        }
        data['workExp'] = JSON.stringify(workExp);
        data['educateExp'] = JSON.stringify(educateExp);
        submit(data);
    }

    function submit(data){
        lib.ajax({
            type: "post",
            url : lib.query.id ? "stylist/update/"+lib.query.id : "stylist/create/"+lib.query.salonid,
            data: data    
        }).done(function(data, status, xhr){
            if(data.result == 1){
                if(lib.query.salonid){
                    lib.popup.confirm({
                        text : "温馨提示",
                        content : "造型师添加成功，是否继续添加？",
                        cancelText : "返回列表",
                        defineText : "继续添加",
                        cancel     :function(){history.back();},
                        define     :function(){location.reload()}
                    })
                }else{
                    parent.lib.popup.result({
                        text:"操作成功！",
                        define:function(){
                            history.back();
                        }
                    });                    
                }                
            }
           
        });
    }
})();