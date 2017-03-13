/* 
* @Author: anchen
* @Date:   2015-12-03 09:50:37
* @Last Modified by:   anchen
* @Last Modified time: 2015-12-31 10:04:32
*/

$(function(){
    var moveTarget = {};
    var tempHtml   = "";
    $("#warpper").on('click','#editBtn',function(){
        $(".copyWriter").find('textarea').removeAttr('disabled');
        $(".copyWriter").find('input[type=text]').removeAttr('disabled');
        $(this).addClass('hidden');
        $(".copyWriter").find('#saveBtn').removeClass('hidden');
        $(".copyWriter").find('#cancelBtn').removeClass('hidden');
    });

    $("#warpper").on('click','#cancelBtn',function(){
        $(this).addClass('hidden');
        $(".copyWriter").find('.control-help').css('display','none');
        $(".copyWriter").find('#saveBtn').addClass('hidden');
        $(".copyWriter").find('#editBtn').removeClass('hidden');        
        $(".copyWriter").find('textarea').attr('disabled','disabled');
        $(".copyWriter").find('input[type=text]').attr('disabled','disabled');        
    });

    $("#warpper").on('_ready',function(e){
        if(!$(e.target).hasClass('box-warpper')) return;
        //初始化服务器上已经存在的banner
        var uploadBtnArr = $(this).find('button[id^=uploader]');        
        uploadBtnArr.each(function(i,item){
            uploader($(item).attr('id'));
        });
        //初始化banner的item
        lib.ajax({
            type: "get",
            url : 'beautyItem/itemList',
            async : true,
        }).done(function(data, status, xhr){
            if(data.result == "1"){
                var itemText = {'1':'SPM','2':'FFA'}
                var arr = [];
                data.data.forEach(function(item,i){
                    arr.push('<option value="'+itemText[item.type]+'_'+item.item_id+'">'+item.name+'</option>');
                })               
                $("form.banner select.itemSelect").each(function(i,item){
                    $(this).append(arr.join(""));
                    if($(this).attr('url')){
                        var obj = JSON.parse($(this).attr('url'));
                        $(this).find('option[value="'+obj.type+'_'+obj.itemId+'"]').attr('selected',true);
                    }
                })
                sessionStorage.setItem('bannerItemList',JSON.stringify(data.data));
            }
        })         
    });

    $("#warpper").on('click','.plus-button button',function(){
        $(this).attr('disabled',true);
        $("form.banner").find('button.edit').attr('disabled',true);
        $("form.banner[id]").removeClass('move'); 
        var list = JSON.parse(sessionStorage.getItem('bannerItemList'));               
        var $form = $(lib.ejs.render({url:'./form'},{data:{type:lib.query.type||1,list:list}}));
        var len = $('form.banner').length + 1;
        $form.find('input[type=radio]').attr('name','behavior'+len);
        $form.find('.uploader').attr('id','uploader'+len);
        $form.find("strong").text("Banner"+len); 
        var complete = $form.find('.search').attr('ajat-complete');
        complete = complete.replace('complete-position','complete-position'+len);
        $form.find('.search').attr('ajat-complete',complete);
        $form.find('.complete-position').attr('id','complete-position'+len);
        tempHtml = $form.html();     
        $('.plus-button').before($form);
        new lib.Form($form);
        if($('form.banner').length >= 10){
            $(".plus-button").hide();
        }
        uploader('uploader'+len);         
    })

    $("#warpper").on('click','form.banner .del',function(){
        var self = this;
        parent.lib.popup.confirm({
            text:'确定要删除吗？',
            define:function(){                            
                var id = $(self).attr('id');
                if(id){
                    lib.ajax({
                        type: "post",
                        url : "banner/destroy/"+id
                    }).done(function(data, status, xhr){
                        if(data.result == 1){
                            parent.lib.popup.result({
                                bool : true,
                                text : "操作成功",
                                define:function(){
                                    location.reload();
                                }
                            });                 
                        }
                    })
                }else{
                    $(self).closest('form.banner').remove();
                    location.reload();
                    // var len = $("form[draggable='false']").find('button.edit.hidden').length;
                    // if(len == 0){
                    //     $("form.banner").find('button.edit').removeAttr('disabled');  
                    //     $(".plus-button button").removeAttr('disabled');
                    //     $("form.banner[id]").addClass('move');                  
                    // }          
                }
            }
        })

    });

    $("#warpper").on('click','form.banner .edit',function(){
        tempHtml = $(this).closest('form.banner').html();

        var parent = $(this).closest('.fr');
        parent.find('.save').removeClass('hidden');
        parent.find('.canncel').removeClass('hidden');
        parent.find('.edit').addClass('hidden');

        var $from = $(this).closest('.banner');
        $from.find('.operation').removeClass('hidden');
        $from.find('button.btn-primary').removeAttr('disabled');
        $from.find('input[type=radio]').removeAttr('disabled');
        $from.find('#title').removeAttr('disabled');
        var val = $from.find('input:checked').val();
        if(val == '1'){
            $from.find('#h5url').removeAttr('disabled');
        }
        if(val == '2'){
            $from.find('select[name=url]').removeAttr('disabled')           
            var selectValue = $from.find('select[name=url]').val();   
            if(selectValue == "salon"){
                $from.find(".search").removeClass('hidden').removeAttr('disabled');
                $from.find(".salonId").removeAttr('disabled');
            }
            if(selectValue == "artificers"){
                $from.find('select.artificersSelect').removeAttr('disabled');
            }
            if(selectValue == "item"){
                $from.find('select.itemSelect').removeAttr('disabled');
            }          
        }
        $("form.banner").find('button.edit').attr('disabled',true);
        $(".plus-button button").attr('disabled',true);
        $("form.banner[id]").removeClass('move');  
    });

    $("#warpper").on('click','form.banner .canncel',function(){
        // var parent = $(this).closest('.fr');
        // parent.find('.save').addClass('hidden');
        // parent.find('.canncel').addClass('hidden');
        // parent.find('.edit').removeClass('hidden');
        // var $from = $(this).closest('.banner');
        // $("form.banner[id]").addClass('move');  
        // $from.find('.operation').addClass('hidden');
        // $from.find('.control-help').hide();
        // $from.find('input[type=radio]').attr('disabled',true);
        // $from.find('#title').attr('disabled',true);
        // $from.find('#h5url').attr('disabled',true);
        // $from.find('select[name=url]').attr('disabled',true);
        // $from.find('.search').attr('disabled',true);
        // $from.find('.salonId').attr('disabled',true);
        // $from.find('button.btn-primary').attr('disabled',true);   
        // var url = $from.attr('url');
        // if(url){
        //     if($from.find('.thumbnails-item-img').length > 0){
        //         $from.find('.thumbnails-item-img img').attr('src',url);
        //         $from.find('.thumbnails-item-img input').attr('value',url);                
        //     }else{
        //         $from.find('.thumbnails-item-btn').css('display','none');
        //         appendImgItem($from.find('.thumbnails-item'),url);                
        //     }
        // }else{
        //     $from.find('.thumbnails-item-btn').css('display','inline-block');
        //     $from.find('.thumbnails-item-img').remove();          
        // }   
        var $form = $(this).closest('form');
        $form.html(tempHtml);
        if($form.attr('draggable')=="false"){
            $form.find('.save').addClass('hidden');
            $form.find('.canncel').addClass('hidden');
            $form.find('.edit').removeClass('hidden');
            $form.find('.inputBox').find('input').attr('disabled','disabled');
            $form.find('.inputBox').find('select').attr('disabled','disabled');
            $form.find('button[id^=uploader]').attr('disabled','disabled');
        }else{
            $form.addClass('move');
        }           
        uploader($form.find('button[id^=uploader]').attr('id'));
        $("form.banner").find('button.edit').removeAttr('disabled');
        $(".plus-button button").removeAttr('disabled');
    });

    $("#warpper").on('click','form.banner input[type=radio]',function(){ 
        var box = $(this).closest('.inputBox');
        box.find('#h5url').attr('disabled',true);
        box.find('select').attr('disabled',true);
        box.find('.search').attr('disabled',true); 
        box.find('.salonId').attr('disabled',true); 
        box.find('.control-help').hide();
        var radios = $(this).closest('.radios');
        radios.find('input[disabled]').removeAttr('disabled'); 
        radios.find('select[disabled]').removeAttr('disabled');        
    });

    $("#warpper").on('change','form.banner select[name=url]',function(e){
        $(this).find('option[selected=selected]').removeAttr('selected');
        $(this).find('option[value='+$(this).val()+']').attr('selected','selected');
        var radios = $(this).closest('.radios');
        radios.find('.control-help').hide();
        if($(this).val()=="salon"){
            radios.find('select.itemSelect').addClass('hidden').attr('disabled',true);
            radios.find('select.artificersSelect').addClass('hidden').attr('disabled',true);
            radios.find('input[type=text]').removeClass('hidden').removeAttr('disabled');
            radios.find('input[type=hidden]').removeClass('hidden').removeAttr('disabled');
        }
        if($(this).val()=="artificers"){
            radios.find('input[type=text]').addClass('hidden').attr('disabled',true);
            radios.find('input[type=hidden]').addClass('hidden').attr('disabled',true);
            radios.find('select.itemSelect').addClass('hidden').attr('disabled',true);
            radios.find('select.artificersSelect').removeClass('hidden').removeAttr('disabled');
        }
        if($(this).val()=="item"){
            radios.find('input[type=text]').addClass('hidden').attr('disabled',true);
            radios.find('input[type=hidden]').addClass('hidden').attr('disabled',true);
            radios.find('select.artificersSelect').addClass('hidden').attr('disabled',true);
            radios.find('select.itemSelect').removeClass('hidden').removeAttr('disabled');
        }
    });


    $("#warpper").on('autoinput','form.banner .search',function(e,data){
        var banner = $(this).closest('.banner');
        if(data){
            banner.find('.salonId').val(data.salon_id);
        }
    }).on('input','form.banner .search',function(){
        var banner = $(this).closest('.banner')
        banner.find('.salonId').val("");
    });     
    
    $("#warpper").on('dragover','form.banner[id]',function(ev){
        ev.preventDefault();
    });

    $("#warpper").on('drop','form.banner[id]',function(ev){
        ev.preventDefault();
        if($(ev.currentTarget).attr('id') == moveTarget.attr('id')){
            return;
        }        
        var targetId = $(ev.currentTarget).attr('id');
        var prevId = moveTarget.prev().attr('id');
        if(targetId==prevId){
            $(ev.currentTarget).before(moveTarget.clone());
        }else{
            $(ev.currentTarget).after(moveTarget.clone());           
        }
        moveTarget.remove();
        var arr = [];
        $('form[id]').each(function(i,item){
            arr.push({id:$(item).attr('id'),sort:i+1});
        });

        lib.ajax({
            type: "post",
            url : 'banner/sort',
            data: {sort:JSON.stringify(arr)}
        }).complete(function(xhr, status){
            var data = JSON.parse(xhr.responseText);
            if(data.result == 0){
                parent.lib.popup.result({
                    bool : false,
                    text : data.msg || "移动排序失败",
                    define:function(){
                        location.reload();
                    }
                });                 
            }
        });                    
    }); 

    $("#warpper").on('dragstart','form.banner[id]',function(ev){       
        moveTarget = $(ev.currentTarget);    
        if($('#warpper').find('form.banner[url]').find('button.edit[disabled]').length>0){
            return false;
        }
    });

    function uploader(id){
        lib.puploader.image({
            browse_button: id,
            auto_start:true,
            filters: {
            mime_types : [
            { title : "Image files", extensions : "jpg,png,jpeg" },
            ]
            },
            max_file_size:'10mb',
            multi_selection:true,
            files_number:1,
            crop:true
        },function(uploader){
            var item = $('#'+id).closest('.thumbnails-item');
            uploader.bind('ImageUploaded',function(up,response){
                var ratio = new Number(1080/600);
                createThumbnails(up,response,ratio,'1080x600',item);
            });
            item.on('click','.fa-pencil-square-o',function(){                
                var src = item.find('img').attr('src');
                src = src.split('?')[0]+"?imageView2/0/w/720/h/1280";
                uploader.trigger('ImageUploaded',{img:src});
            })
            item.on('click','.fa-times-circle-o',function(){                
                item.find('.thumbnails-item-img').remove();
                item.find('.thumbnails-item-btn').show();
            })  
        });
    } 

    function createThumbnails(up,response,ratio,name,item){
        lib.cropper.create({
            src:response.img,
            aspectRatio : 1080/600,
            thumbnails  : [name],
            define:function(data){
                item.closest('.banner').find(".imageTip").hide();
                item.find('.thumbnails-item-btn').css('display','none');
                item.find('.thumbnails-item-img').remove();
                item.removeClass('dashed');
                appendImgItem(item,data[name]);
            }
        });        
    }

    function appendImgItem(item,url){
        item.append('<div class="thumbnails-item-img"><img src='+url+'><input type="hidden" name="image" value='+url+' required><div class="operation"><i class="fa fa-pencil-square-o"></i>&nbsp;&nbsp;<i class="fa fa-times-circle-o"></i></div><div>');        
    }

    lib.Form.prototype.save = function(data){
       if(data.copyWriter){
            submitCopyWriter(data);
       }else{
            if(!data.image){
                $(this.el).find('.imageTip').show();
                return;
            }          
            data.behavior = $(this.el).find('input:checked').val();
            submitBanner(data);
       }
    }

    function submitCopyWriter(data){
        delete data.copyWriter;
        lib.ajax({
            type: "post",
            url : "banner/createOrSave",
            data:data
        }).done(function(data, status, xhr){
            if(data.result == 1){
                parent.lib.popup.result({
                    text:"操作成功！",
                    define:function(){
                        location.reload('homeBanner.html?type='+data.type);
                    }
                });                  
            }
        })            
    }

    function submitBanner(data){ 
        if(data.behavior=="2"){
            if(data.url=="salon"){
                data.url = {'type':data.url};
            }else if(data.url=="artificers"){
                data.url = {'type':data.url,'itemId':data.itemId};
            }else{
                var arr = data.itemId.split("_");
                data.url = {'type':arr[0],'itemId':arr[1]};                
            }            
            delete data.itemId;
            data.url = JSON.stringify(data.url);
        }     
        lib.ajax({
            type: "post",
            url : data.id ? "banner/edit/"+data.id : "banner/create",
            data:data
        }).done(function(data, status, xhr){
            if(data.result == 1){
                parent.lib.popup.result({
                    text:"操作成功！",
                    define:function(){
                        location.reload();
                    }
                });                  
            }
        })  
    }                         
});