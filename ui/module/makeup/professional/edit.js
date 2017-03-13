/* 
* @Author: anchen
* @Date:   2015-10-09 10:53:59
* @Last Modified by:   anchen
* @Last Modified time: 2016-01-12 10:26:45
*/

(function(){       
    if(lib.query.id){
        var promise = lib.ajat("artificer/show/"+lib.query.id+"#domid=form&tempid=form-t").render();
        promise.done(function(data){
            var arr1 = [];
            var arr2 = []; 
            if(data.data.photo) arr1.push(JSON.parse(data.data.photo));
            if(data.data.pageImage) arr2.push(JSON.parse(data.data.pageImage));
            initUploader(arr1,arr2);
        });
    }else{
        lib.ajat("#domid=form&tempid=form-t").template({});   
        initUploader();         
    } 

    function initUploader(arr1,arr2){
        lib.puploader.image({
            browse_button: 'personImagesUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg" },
                ]
            },
            max_file_size:'10mb',
            imageArray:arr1,
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                createThumbnails(up,response,1,'160x160');
            });
        });

        lib.puploader.image({
            browse_button: 'personHomeImage',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg" },
                ]
            },
            max_file_size:'10mb',
            imageArray:arr2,
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                createThumbnails(up,response,750/628,'750x628');                       
            });
        });        
    }

    var createThumbnails = function(up,response,ratio,name){
        lib.cropper.create({
            src:response.img,
            aspectRatio : ratio,
            thumbnails  : [name],
            define:function(data){
                if(up.createThumbnails&&!response.edit&&!response._this){
                    up.createThumbnails({
                        thumbimg:data[name],
                        img:response.img
                    });
                }else{
                    up.preview($(response._this),{thumbimg:data[name]});
                }
                $("#form .upload-image-tip").hide();
            }
        });        
    }

    $("#form").on('click','#introduceButton',function(){
       if($("#introduceTbody tr").length < 10){
           var template = $("#introduceTbody tr")[0].outerHTML; 
           $("#introduceTbody").append(template);
           var tr = $("#introduceTbody tr").last();
           $(".control-help",tr).css("dispaly","none");
           $("input",tr).val("");
           $("textarea",tr).val("");
       }
    })

    $("#form").on('click','button.del',function(){
         if($("#introduceTbody tr").length != 1){
            var self =  this;
            parent.lib.popup.confirm({
                text:'确定要删除吗？',
                define:function(){  
                    $(self).closest('tr').remove();
                }
            });
         }
    })

    function checkedImage(ele){
        var len = ele.length;
        if(len){
            $("#form .upload-image-tip").hide();
        }else{
            $("#form .upload-image-tip").show();
        }
        return len;
    }

    function getIntroduce (){
        var arr = [];
        $("#introduceTbody tr").each(function(i,item){
            var obj = {};
            obj['title'] = $(item).find('input').val();
            obj['content'] = $(item).find('textarea').val();
            arr.push(obj);
        });
        return arr;
    }
    
    lib.Form.prototype.save = function(data){
        var ele1 = $("#control-thumbnails1 img");
        if(!checkedImage(ele1)) return;
        var ele2 = $("#control-thumbnails2 img");
        if(!checkedImage(ele2)) return;       
        data.photo = JSON.stringify({thumbimg:ele1.attr('src'),img:ele1.data('original')});
        data.pageImage = JSON.stringify({thumbimg:ele2.attr('src'),img:ele2.data('original')});
        data.number = "1"+data.number;
        data.detail = JSON.stringify(getIntroduce());
        if(lib.query.id) data.id=lib.query.id;
        submit(data)               
    } 

    function submit(data){
        $('form').attr('disabled',true);        
        lib.ajax({
            type: "post",
            url : lib.query.id ? "artificer/update" :"artificer/add",
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
            setTimeout(function(){
                $('form').attr('disabled',false);
            }, 2000);           
        });
    }
})();