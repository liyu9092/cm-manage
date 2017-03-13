/* 
* @Author: anchen
* @Date:   2015-08-19 15:54:43
* @Last Modified by:   anchen
* @Last Modified time: 2015-09-21 15:00:38
*/

(function(){
    $(".table").delegate('button[type="button"]', 'click', function(event) {
        var arr = [];      
        var amount = 0;    
        $('tbody input[type="checkbox"]:checked').each(function(index,obj){
          arr.push($(obj).data("id"));
          amount += (+$(obj).data("amount"));
        });
        if(arr.length > 0){
            parent.lib.popup.confirm({
                text:'你正在对'+arr.length+'家店铺确认返佣,返佣总额￥'+amount+',是否继续?',
                define:function(){
                    lib.ajax({
                        type: "post",
                        data : {rebate : arr},
                        url : "rebate/confirm"
                    }).done(function(data, status, xhr){
                        if(data.result == 1){
                            parent.lib.popup.result({
                                text:"返佣成功",
                                define:function(){
                                    lib.ajat('rebate/index?<%=query._%>#domid=table&tempid=table-t').render();
                                }
                            });                            
                        }
                    });                          
                }
            });
        }
    }); 

    var uploader = lib.puploader.create({
		'loaderText':'数据正在导入中...',
		'successText':'数据导入成功',
        'browse_button' : 'import',
        'domain'        : cfg.getHost() + "rebate/upload?token="+localStorage.getItem('token'),
        'file_data_name': 'rebate',
        'auto_start'    : true,
        'filters'       : {mime_types:[{ title : "Excel", extensions : "xls" }],
                            max_file_size : '10mb', //最大只能上传10mb的文件
                            prevent_duplicates : true //不允许选取重复文件
                          }
    },function(obj){
        obj.bind('UploadComplete',function(){
            setTimeout(function(){
                $(window).trigger('hashchange')           
            }, 2000)            
        })
    });

    // var uploader = WebUploader.create({
    //     swf   : '../../js/Uploader.swf',
    //     server: cfg.getHost() + "rebate/upload?token="+localStorage.getItem('token'),
    //     pick  : '#import',
    //     resize: false,
    //     auto  : true,
    //     fileSingleSizeLimit : 10*1024*1024,
    //     fileVal:'rebate',
    //     accept :{
    //         title: 'Excel',
    //         extensions: 'xls',
    //         mimeTypes: 'application/vnd.ms-excel'                
    //     }                
    // });

    // var timer = setInterval(function(){
    //     var picker = $(".webuploader-pick:eq(0)").next();
    //     if(picker){
    //         clearInterval(timer);
    //         var map = JSON.parse(localStorage.getItem('access.map'));
    //         if(!map['rebate.upload']){
    //            $("input",picker).attr('disabled','disabled');
    //         }
    //     }
    // }, 50)     

    // uploader.on('startUpload',function(){
    //     parent.lib.popup.tips({text:'<img src="/images/oval.svg" class="loader"/>数据正在导入中...'});       
    // });

    // uploader.on('uploadError',function(file,reason){
    //     uploader.removeFile(file,true);
    //     uploader.reset();
    //     parent.lib.popup.result({
    //         bool:true,
    //         text:"数据导入出错！",
    //         time:2000
    //     });
    // })

    // uploader.on('uploadSuccess',function(file,response){
    //     uploader.removeFile(file,true);
    //     uploader.reset();
    //     parent.lib.popup.result({
    //         bool:response.result  == 1,
    //         text:(response.result == 1 ? "数据导入成功！" : file.name + "文件, 数据导入失败！"),
    //         time:2000
    //     });
    // }); 

    // uploader.on('uploadFinished',function(){
    //     setTimeout(function(){
    //         //lib.ajat('rebate/index?<%=query._%>#domid=table&tempid=table-t').render();
    //         $(window).trigger('hashchange')           
    //     }, 2000)
    // });

})();