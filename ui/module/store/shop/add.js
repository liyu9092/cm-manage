/* 
* @Author: anchen
* @Date:   2015-07-02 14:29:33
* @Last Modified by:   anchen
* @Last Modified time: 2016-01-06 14:41:31
*/

$(function(){
    var type = lib.query.type;
    var d = new Date().format('yyyy-MM-dd');
    var timestamp = new Date().getTime();

    if(type === 'edit'){
        var data = JSON.parse(sessionStorage.getItem('edit-shop-data'));
        data.type = type;
        lib.ajat('#domid=form&tempid=form-t').template(data);
        var option = $("select[name='salonChangeGrade'] > option[value='"+data.salonChangeGrade+"']");
        if(option && option.length > 0) {
            option.attr("selected","selected");
        }
        $("#salonSn").css("display",'table');   
    }

    if(type === 'add'){
        var shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
        shopData = $.extend({},shopData,{
            "merchantId" : lib.query.merchantId,
            "name"       : lib.query.name,
            "addr"       : lib.query.addr
        });      
        lib.ajat('#domid=form&tempid=form-t').template(shopData);
        document.body.onbeforeunload=function(){
            return "确定离开当前页面吗？";
        } 
        $("input[name='contractTime']").val(d);
        $("input[name='contractEndTime']").val((new Date(d).getFullYear()+3)+ d.substring(4));
    }

    $("input[name='contractTime']").attr('max',d);
    $("input[name='contractEndTime']").attr('min',d);
    $("input[name='changeInTime']").attr('min',new Date(timestamp + 24*60*60*1000).format('yyyy-MM-dd'));
	
	
    $("#preview-btn").on('click',function(){
        var data = lib.getFormData($("#form"));
        dataFormat(data);
		if(!data.zoneName){
			data.zoneName=$('input[name="zone"]:checked').next().text();
		}
		if(!data.businessName){
			data.businessName=$('#business option:selected').text();
		}
		if(!data.provinceName){
			data.provinceName=$('#province option:selected').text();
		}
		if(!data.citiesName){
			data.citiesName=$('#city option:selected').text();
		}
		if(!data.districtName){
			data.districtName=$('#area option:selected').text();
		}
		if(!data.name){
			data.name=$('input[name="name"]').val();
		}
        if(type === 'edit') var shopData = JSON.parse(sessionStorage.getItem('edit-shop-data'));
        if(type === 'add')  var shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
        shopData = $.extend({},shopData,data);
        sessionStorage.setItem('preview-shop-data',JSON.stringify(shopData));
        window.open("detail.html?type=preview");
    })

    $(".pop-close").on('click',function(){
        $("#pop-wrapper").hide();
    });

    // $("body").on("keyup",function(e){
    //     if(e.which >= 65 &&  e.which <= 90){
    //         if($("#business").hasClass('select-focus')){
    //             var str = String.fromCharCode(e.which).toLowerCase();  
    //             var arr = $("#business option[data-py^='"+str+"']");
    //             if(arr && arr.length > 0){
    //                 //每次scroll前先重置scrollTop
    //                 $(".options").scrollTop(0);                    
    //                 //ul的top
    //                 var top = $(".options").offset().top;
    //                 //目标li
    //                 var currentLi = $(".options li[value='"+arr.eq(0).val()+"']");
    //                 //目标li的top
    //                 var currentLiTop = currentLi.offset().top;
    //                 //scrollTop的高度
    //                 $(".options").scrollTop(currentLiTop-top); 
    //             } 
    //         }
    //     }
    // })

    lib.Form.prototype.save = function(data){
        dataFormat(data);
        if(type === 'edit'){
            var shopData = JSON.parse(sessionStorage.getItem('edit-shop-data'));
            shopData = $.extend({},shopData,data);
            sessionStorage.setItem('edit-shop-data',JSON.stringify(shopData));   
        }
        if(type === 'add'){
            var shopData = JSON.parse(sessionStorage.getItem('add-shop-data'));
            shopData = $.extend({},shopData,data);
            sessionStorage.setItem('add-shop-data',JSON.stringify(shopData));            
        }
		document.body.onbeforeunload=function(){}
        location.href = "picture.html?type="+type;
    } 

    var dataFormat = function(data){      
        var arr = data.lngLat.split(",");
        data.addrlati = arr[0];
        data.addrlong = arr[1];
        delete data.lngLat;
    } 
	$(document.body).on('blur',"input[name='contractTime']",function(){
		$('input[name="contractEndTime"]').attr('min',this.value);
	})

});

function renderMap (){
    $("#addCoordinate").on('click',function(){
		parent.lib.fullpage(true);
		var container=$('.map-container').show();
        var address = $("#address");
		var geoCoor = $("#address-text");
        address.val() && geoCoor.text(address.val());
        var addrPonit = {};
        //在指定的容器内创建地图实例  
        var map = new BMap.Map(container.find(".map-inner")[0]);
        //默认经纬度point
		var latlng=$('#coorMarkerInput').val();
		var latlng={
			lat:latlng.split(',')[0],
			lng:latlng.split(',')[1]
		}
		var point = new BMap.Point("113.941893","22.535644");
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
        //根据point获取address实例
        var geocoder = new BMap.Geocoder();

        function drawMarker(point){
            var marker = new BMap.Marker(point,{icon:markerIcon}); 
            map.addOverlay(marker);
            marker.setAnimation(BMAP_ANIMATION_BOUNCE); //跳动的动画  
            map.panTo(point);                                                
        }

        if(latlng.lng && latlng.lat){
            point = new BMap.Point(latlng.lng,latlng.lat);
            drawMarker(point);
            geocoder.getLocation(point, function(addr) {
                addrPonit['coor'] = {lng:point.lng,lat:point.lat};
            });                        
        }else{
            var addr = "";
            var prov = $("#province option:selected");
            var city = $("#city option:selected");
            var area = $("#area option:selected");
            if(prov.val()) addr = addr + prov.text()+"省";
            if(city.val()) addr = addr + city.text()+"市";
            if(area.val()) addr = addr + area.text()+"区";
            addr = addr + address.val();
            if(addr){
                geocoder.getPoint(addr, function(p) {
                    point = p ? p : point;
                    drawMarker(point);
					addrPonit.coor={lng:point.lng,lat:point.lat};
                })                                                                            
            }else{
                drawMarker(point);                             
            }
        }

        //点击地图选择地理位置
        map.addEventListener("click", function(event) {
            point = event.point;
            map.clearOverlays();
            drawMarker(point); 
            geocoder.getLocation(point, function(addr) {
                addrPonit['coor'] = {lng:point.lng,lat:point.lat};
                geoCoor.text(addr.address);
            });
        });

        //建立一个自动完成的查询对象
        var search = new BMap.Autocomplete({"input": "map-search","location": map});
        search.addEventListener("onconfirm", function(event) {
            var area = event.item.value;
            var addr = area.province+area.city+area.district+area.street+area.business;
            geocoder.getPoint(addr, function(point) {
                if (point) {
                    map.centerAndZoom(point, 16);
                    map.clearOverlays();
                    drawMarker(point);
                } else {
                    parent.lib.popup.alert({text:'暂时无法获取您的位置'})
                }
            })
        });
		container.find(".btn").on('click',function(){
            container.hide();
			parent.lib.fullpage(false);
        });
		container.find(".btn-primary").on('click',function(){
            if(addrPonit.coor){
                $("#coorMarker").text("已标记");               
                $("#coorMarkerInput").val(addrPonit.coor.lat + ","+addrPonit.coor.lng);
                $("#coorMarkerInput").blur();
                container.hide();
				parent.lib.fullpage(false);
            }else{
                parent.lib.popup.tips({
                    text:'<i class="fa fa-exclamation-circle"></i>地图未标记,请先标记地图',
                    time:2000
                })
            }
        });
		
    });
}