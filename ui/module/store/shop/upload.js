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

		if(currentData.contractPicUrl&&typeof currentData.contractPicUrl=='string'){
			currentData.contractPicUrl=JSON.parse(currentData.contractPicUrl);
		}

		if(currentData.licensePicUrl&&typeof currentData.licensePicUrl=='string'){
			currentData.licensePicUrl=JSON.parse(currentData.licensePicUrl);
		}

		if(currentData.corporatePicUrl&&typeof currentData.corporatePicUrl=='string'){
			currentData.corporatePicUrl=JSON.parse(currentData.corporatePicUrl);
		}

        initUploader();
    }

    var initUploader = function(){
        //合同上传
        lib.puploader.image({
            browse_button: 'imageUpload1',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            imageArray:currentData.contractPicUrl,
            multi_selection:true,
            files_number:10
        },function(obj){
            obj.thumbnails.bind('itemchange',function(){
                setURLData();
            });
        });

        //营业执照上传
        lib.puploader.image({
            browse_button: 'imageUpload2',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            multi_selection:true,
            files_number:3,
            imageArray:currentData.licensePicUrl
        },function(obj){
            obj.thumbnails.bind('itemchange',function(){
                setURLData();
            });            
        });

        //法人执照上传
        lib.puploader.image({
            browse_button: 'imageUpload3',
            auto_start:true,
            filters: {
                mime_types : [
                    { title : "Image files", extensions : "jpg,png,jpeg,gif" },
                ]
            },
            max_file_size:'10mb',
            multi_selection:true,
            files_number:3,
            imageArray:currentData.corporatePicUrl
        },function(obj){
           obj.thumbnails.bind('itemchange',function(){
                setURLData();
            });            
        });
    }

    var setURLData=function(){
        currentData.contractPicUrl=[];
        $('#control-thumbnails1 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.contractPicUrl.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val()
            });
        });
        currentData.licensePicUrl=[];
        $('#control-thumbnails2 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.licensePicUrl.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val()
            });
        });
        currentData.corporatePicUrl=[];
        $('#control-thumbnails3 .control-thumbnails-item').each(function(){
            var $this=$(this);
            currentData.corporatePicUrl.push({
                thumbimg:$this.find('input[name="thumb"]').val(),
                img:$this.find('input[name="original"]').val()
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

        $("#preview_btn").on('click',function(){
            sessionStorage.setItem("preview-shop-data",JSON.stringify(currentData));
            window.open("detail.html?type=preview");        
        })

        $(".submit").on('click',function(){
            document.body.onbeforeunload=function(){}
			location.href="settlement.html?type="+type;
        })
    }

    init();
})();
