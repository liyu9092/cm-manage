/* 
* @Author: anchen
* @Date:   2015-12-02 09:30:19
* @Last Modified by:   anchen
* @Last Modified time: 2015-12-10 10:37:50
*/

(function(){     
    if(lib.query.id){
        var promise = lib.ajat("assistant/show/"+lib.query.id+"#domid=form&tempid=form-t").render();
        promise.done(function(data){                
            var arr = [];
            if(data.data && data.data.photo) {                    
                arr.push(JSON.parse(data.data.photo));
            }
            initUploader(arr);
        });
    }else{
        lib.ajat("#domid=form&tempid=form-t").template({}); 
        initUploader();
    }
    
    function initUploader(arr){
        lib.puploader.image({
            browse_button: 'personImageUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg" },
                ]
            },
            max_file_size:'10mb',
            multi_selection:true,
            files_number:1,
            imageArray:arr,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                lib.cropper.create({
                    src:response.img,
                    aspectRatio : 750/500,
                    thumbnails  : ["750x500"],
                    define:function(data){
                        if(up.createThumbnails&&!response.edit&&!response._this){
                            up.createThumbnails({
                                thumbimg:data["750x500"],
                                img:response.img
                            });
                        }else{
                            up.preview($(response._this),{thumbimg:data["750x500"]});
                        }
                        $("#form .upload-image-tip").hide();
                    }
                });              
            });
        });
    }

    function checkedImage(){
        var len = $("#form .control-thumbnails-item img").length;
        if(len){
            $("#form .upload-image-tip").hide();
        }else{
            $("#form .upload-image-tip").show();
        }
        return len;
    }

    function checkProfssional(data){
        if(data.pid && data.pid.length > 3){
            $(".pid").show();
            return false;
        }else{
            $(".pid").hide();
            return true;
        }
    }   
      
    lib.Form.prototype.save = function(data){
        if(!checkedImage()) return;
        if(!checkProfssional(data)) return;
        var element = $(".control-thumbnails-item img");
        var img = {};
        img['thumbimg'] = element.attr('src');
        img['img']      = element.data('original');
        data.photo      = JSON.stringify(img);
        data.number     = "M"+data.number;
        if(data.pid) data.pid=data.pid.toString();
        if(lib.query.id) data.id=lib.query.id;
        submit(data)               
    } 

    function submit(data){
        lib.ajax({
            type: "post",
            url : lib.query.id ? "assistant/update" :"assistant/add",
            data: data    
        }).done(function(data, status, xhr){
            if(data.result == 1){
                parent.lib.popup.result({
                    text:"操作成功！",
                    define:function(){
                        history.back();
                    }
                });                                   
            }           
        });
    }    
})()