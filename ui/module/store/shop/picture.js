/* 
* @Author: anchen
* @Date:   2015-09-28 11:17:09
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-23 15:20:28
*/

(function(){
    var type = lib.query.type;
    var currentData = {};

    var init = function(){
        initPage();
        initEvent();
    }

    var initPage = function(){
        //新增
        if(type === 'add'){
            currentData = JSON.parse(sessionStorage.getItem('add-shop-data')); 
            document.body.onbeforeunload=function(){return "确定离开当前页面吗？";}
        }
        //编辑
        if(type === 'edit'){
            currentData = JSON.parse(sessionStorage.getItem('edit-shop-data')); 
        }

        if(currentData.salonLogo && typeof currentData.salonLogo == 'string'){
            currentData.salonLogo = JSON.parse(currentData.salonLogo);
        }

        if(currentData.salonImg && typeof currentData.salonImg == 'string'){
            currentData.salonImg = JSON.parse(currentData.salonImg);
        }

        if(currentData.workImg && typeof currentData.workImg == 'string'){
            currentData.workImg = JSON.parse(currentData.workImg);
        } 
        initUploader();       
    }

    var initUploader = function(){
        lib.puploader.image({
            browse_button: 'logoUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            imageArray:currentData.salonLogo,
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                createThumbnails(up,response,1,'300x300');                
            });
            uploader.thumbnails.bind('itemchange',function(){
                saveImagesUrl();
            });
        });

        lib.puploader.image({
            browse_button: 'shopUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            imageArray:currentData.salonImg,
            multi_selection:true,
            files_number:4,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                var ratio = new Number(1125/405).toFixed(2);
                createThumbnails(up,response,ratio,'1125x405');                
            });

            uploader.thumbnails.bind('itemchange',function(){
                saveImagesUrl();
            });
        });

        lib.puploader.image({
            browse_button: 'teamUpload',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            imageArray:currentData.workImg,
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            uploader.bind('ImageUploaded',function(up,response){
                var ratio = new Number(1125/405).toFixed(2);
                createThumbnails(up,response,ratio,'1125x405');                
            });

            uploader.thumbnails.bind('itemchange',function(){
                saveImagesUrl();
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
                        img:response.img,
                        type : 1
                    },function(){
                        saveImagesUrl();                               
                    });
                }else{
					up.preview($(response._this),{thumbimg:data[name]});
					$(response._this).parent().trigger('itemchange');
				}
            }
        });        
    }

    var saveImagesUrl=function(){
        currentData.salonLogo = [];
        $('#control-thumbnails1 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.salonLogo.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val(),
                type  : 1
            });
        });
        currentData.salonImg = [];
        $('#control-thumbnails2 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.salonImg.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val(),
                type  : 1                
            });
        });
        currentData.workImg = [];
        $('#control-thumbnails3 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.workImg.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val(),
                type  : 1                
            });
        });
        if(type === 'add')  sessionStorage.setItem('add-shop-data',JSON.stringify(currentData));
        if(type === 'edit') sessionStorage.setItem('edit-shop-data',JSON.stringify(currentData));        
    }

    var initEvent = function(){
        $(".flex-item a").on('click',function(e){
            e.preventDefault();
            location.href = $(this).attr('href') + "?type="+type;
        });

        $(".preview").on('click',function(){
            sessionStorage.setItem("preview-shop-data",JSON.stringify(currentData));
            window.open("detail.html?type=preview");
        })                        

        $(".submit").on('click',function(){
            document.body.onbeforeunload=function(){}
            location.href="bank.html?type="+type;
        });  
    }

    init();
})();