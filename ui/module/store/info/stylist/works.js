/* 
* @Author: anchen
* @Date:   2015-10-12 13:59:43
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-28 17:05:42
*/

(function(){
   $("#works-wrapper").on("click",".control-thumbnails-remove",function(){
        var self = $(this);
        parent.lib.popup.confirm({
            text:"正在删除此图片，是否继续?",
            define:function(){
                var id  = self.closest('.control-thumbnails').data("id"); 
                var sib = self.parent().siblings();
                var str = "";
                var arr = []; 
                sib.each(function(i,item){
                    arr.push($(item).find("img").attr("worksId"));                        
                })         
                lib.ajax({
                    type: "post",
                    url : "works/del/"+id,
                    data: {img : arr.toString()}    
                }).done(function(data, status, xhr){
                    if(data.result == 1){
                        parent.lib.popup.result({
                            text:"操作成功！",
                            define:function(){
                                 $(window).trigger('hashchange');  
                            }
                        });                
                    }
                   
                });
            }
        });                                       
   });

   $("#works-wrapper").on("click",".del",function(){ 
        var self = this;
        parent.lib.popup.confirm({
            text:"正在删除作品，是否继续?",
            define:function(){
                var id = $(self).parent().next().data("id");
                lib.ajax({
                    type: "post",
                    url : "works/del_list/"+id 
                }).done(function(data, status, xhr){
                    if(data.result == 1){
                        parent.lib.popup.result({
                            text:"操作成功！",
                            define:function(){
                                $(window).trigger('hashchange');    
                            }
                        });                
                    }                   
                }); 
            }
        });                                       
    });

    $("#works-wrapper").on('click','.control-thumbnails-before',function(){
        var $this=$(this);
        var thumbnail=$this.closest('.control-thumbnails-item');
        var prev=thumbnail.prev('.control-thumbnails-item')
        if(prev.length==1){
            thumbnail.after(prev);
            eidt($this);
        }
    });

    $("#works-wrapper").on('click','.control-thumbnails-after',function(){
         var $this=$(this);
         var thumbnail=$this.closest('.control-thumbnails-item');
         var next=thumbnail.next('.control-thumbnails-item')
         if(next.length==1){
             thumbnail.before(next);
             eidt($this);
         }
    });                

    function eidt(self){
       var thumbnail=self.closest('.control-thumbnails');
       var arr = [];
       thumbnail.find("img").each(function(i,item){
            arr.push($(item).attr("worksId"));                        
        })
        lib.ajax({
            type: "post",
            url : "works/update/"+thumbnail.data("id"),
            data:{img:arr.toString()}
        });         
    }

    $("#add_works_btn").on('click',function(){
        lib.popup.box({
           width:$(window).width(),
           height:$(window).height(),
           title:'<h1>新增作品</h1>',
           content:$("#box").css("display","").html(),
           complete : function(){
            $('.popup #imagesUpload').attr('id',"worksUpload");
            $('.popup #submitBtn').on('click',function(){
                submit();
            });
            $('.popup #cancelBtn').on('click',function(){
                var popup = $('.popup,.popup-overlay');
                popup.fadeOut(200, function(){
                    popup.remove();
                });
            })

            $('.popup textarea').on('focus',function(){
                $('.popup #textareaTip').hide();

            })
            $('.popup textarea').on('blur',function(){
                var des = $(this).val();
                if(!des) $('.popup #textareaTip').show();
            })
            initImageUpload();
           }                  
        });
        $("#box").css("display","none");
    });

    function initImageUpload(){
        lib.puploader.image({
            browse_button: "worksUpload",
            //thumb : "w/160/h/120",
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            multi_selection:true,
            files_number:9
        },function(uploader){
            uploader.bind('FileUploaded',function(up,response){
                $('.popup #imagesUploadTip').hide();            
            });
            uploader.bind('BeforeUpload',function(){
                $('.popup input[type=file]').attr('disabled','disabled');
                $('.popup #submitBtn').attr('disabled','disabled');
            });             
            uploader.bind('UploadComplete',function(){
                $('.popup input[type=file]').removeAttr('disabled');
                $('.popup #submitBtn').removeAttr('disabled');
            });                                    
        });                    
    }

    function submit(){
        var thumbnailsArr = $('.popup .control-thumbnails-item');
        var des = $('.popup #description').val();
        if(thumbnailsArr.length == 0) {
            $('.popup #imagesUploadTip').show();
        }
        if(!des) {
            if(!des) $('.popup #textareaTip').show();
        }
        if(thumbnailsArr.length == 0 || !des) return;
        var arr = [];
        thumbnailsArr.each(function(i,item){
            arr.push($(item).find("img").data("original"));  
        });
        $('.popup #submitBtn').attr('disabled','disabled');
        parent.lib.popup.loading({text:'请求可能会比较慢，请耐心等候！',time:15000});
        lib.ajax({
            type: "post",
            url : "works/create",
            timeout : 15000,
            data:{stylistId:lib.query.id,img:JSON.stringify(arr),description:des}
        }).done(function(data, status, xhr){
            if(data.result == 1){
                parent.lib.popup.result({
                    text:"操作成功！",
                    define:function(){
                        $('.popup #submitBtn').removeAttr('disabled');
                        $('.popup #cancelBtn').trigger('click');
                        $(window).trigger('hashchange');    
                    }
                });                
            }else{
                $('.popup #submitBtn').removeAttr('disabled');
            }                  
        }).fail(function(xhr, status){
            $('.popup #submitBtn').removeAttr('disabled');
        }); 

    }
})()
