/* 
* @Author: anchen
* @Date:   2015-07-06 16:48:38
* @Last Modified by:   anchen
* @Last Modified time: 2015-09-30 14:02:23
*/

(function(){
    var type = lib.query.type;
    var upload = lib.query.upload;
    var salonId = lib.query.salonid;
    var currentData = {}; 

    var selectSalonType = function(data){
        if(data.salonType){
            var arr = data.salonType.split("_");
            arr.forEach(function(value,index){
               $(":checkbox[value='"+value+"']").attr('checked',true).parent().show(); 
            })       
        }
    }

    var createScript = function(){
        var el = document.createElement("script");
        el.setAttribute('src','http://api.map.baidu.com/api?v=2.0&ak=F360d7e9ea3c3ecb5b9b9f2b530be8f4&callback=renderMap()');
        document.getElementsByTagName("head")[0].appendChild(el);
    }

    //查看详情
    if(type === 'detail'){
        var promise = lib.ajat('salon/getSalon?salonid='+salonId+'#domid=table-wrapper&tempid=table-t').render();
        promise.done(function(data){
            var str = JSON.stringify(data.data);
            sessionStorage.setItem('edit-shop-data',str);    
            selectSalonType(data.data);
            currentData = data.data;
			createScript();
        });
        
    }

    //预览
    if(type === "preview"){
        $("#branchTitle").attr("href","#");
        $("#leafTitle").text("预览");
        var data = JSON.parse(sessionStorage.getItem("preview-shop-data"));
        if($.isArray(data.contractPicUrl)) data.contractPicUrl = JSON.stringify(data.contractPicUrl);
        if($.isArray(data.licensePicUrl)) data.licensePicUrl = JSON.stringify(data.licensePicUrl);
        if($.isArray(data.corporatePicUrl)) data.corporatePicUrl = JSON.stringify(data.corporatePicUrl);
        if($.isArray(data.salonLogo)) data.salonLogo = JSON.stringify(data.salonLogo);
        if($.isArray(data.salonImg)) data.salonImg = JSON.stringify(data.salonImg);
        if($.isArray(data.workImg)) data.workImg = JSON.stringify(data.workImg);  
        currentData = data;
        lib.ajat('#domid=table-wrapper&tempid=table-t').template(data);
        createScript();
        selectSalonType(data);         
    }

    $("#table-wrapper").delegate('#stop_cooperation_btn','click',function(){
        var status = $(this).attr("status");
        var data   = {salonid : $(this).attr("salonid")};
        var msg    = "";
        if(status == "0"){
            data.type = 2;
            msg = "终止合作";
        }else{
            data.type = 1;
            msg = "恢复合作";
        }
        lib.ajax({
            type: "post",
            data : data,
            url : "salon/endCooperation"
        }).done(function(data, status, xhr){
			parent.lib.popup.result({
				bool:data.result == 1,
				text:(data.result == 1?"操作成功":data.msg),
				time:2000
			});
            if(data.result == 1){
                var btn = $("#stop_cooperation_btn");
                if(btn.attr('status') == "0"){
                     btn.attr("status","1");
                }else{
                     btn.attr("status","0");
                }
                btn.text(msg);
				if(msg=="恢复合作"){
					$('#remove_stop_btn').removeAttr('disabled');
				}else{
					$('#remove_stop_btn').attr('disabled',true);
				}
            }
			
        });
    })

    $("#table-wrapper").delegate('#remove_stop_btn','click',function(){
		if($(this).is(':disabled')) return;
        var data   = {salonid : $(this).attr("salonid")};
		parent.lib.popup.confirm({text:'确认删除此店铺',define:function(){
			lib.ajax({
				type: "post",
				data : data,
				url : "salon/del"
			}).done(function(data, status, xhr){
				parent.lib.popup.result({
					bool:data.result == 1,
					text:(data.result == 1?"删除成功":data.msg),
					time:2000,
					define:function(){
						if(data.result == 1){
							history.back();
						}
					}
				});
			});
		}});
    });
})();

    function renderMap(){
        //在指定的容器内创建地图实例  
        var map = new BMap.Map("mapContent");
        //创建一个地理坐标类 
		var $map=$('.map');
		var point = new BMap.Point($map.data('lng')||113.941893,$map.data('lat')||22.535644);
        //初始化地图位置（未进行初始化的地图将不能进行任何操作）
        map.centerAndZoom(point,12);
        //将平移缩放控件添加到地图上
        map.addControl(new BMap.NavigationControl());
        //启用滚轮放大缩小功能
        map.enableScrollWheelZoom(true);
        //此处是一个补丁，由于未知的原因直接new Marker对象添加的标注
        //标注的图标很小，所以这里直接用自己的图标            
        var markerIcon = new BMap.Icon("/images/marker.png", new BMap.Size(39,25),{
            anchor : new BMap.Size(10,25) 
        });
        //根据point创建一个图像标注覆盖物实例
        var marker = new BMap.Marker(point,{icon:markerIcon}); 
        //将覆盖物添加到地图中
        map.addOverlay(marker);
        //跳动的动画   
        marker.setAnimation(BMAP_ANIMATION_BOUNCE);

        var addr = $map.data('addr') || '南山科技中一路华强高新发展大楼';
                
        $("#map-open").on('click',function(){
			window.open("http://map.baidu.com/?latlng="+($map.data('lat')||22.535644)+","+($map.data('lng')||113.941893)+"&title="+addr+"&autoOpen=true");

		})           
    }