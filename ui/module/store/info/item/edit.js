$(function(){
	$(document.body).on('change','.form-item .control-label input',function(){//是否启用对应的项
		$(this).closest('.control-label').next('.control').find('input,button').attr('disabled',!this.checked);
	}).on('click','.form-item .btn-plus',function(){//增加造型师，药水
		var name=$(this).prev().attr('name');
		$(this).before('<input type="text" class="input-small" maxlength="10" nospace name="'+name+'"/>');
		$(this).prev().focus();
	}).on('click','.price-choose',function(){//定价类型的切换
		var priceGroup=$('.price-group');
		var formatGroup=$('.format-group');
		if(this.checked&&this.value==1&&!priceGroup.is(":visible")){
			priceGroup.show();
			formatGroup.hide();
			priceGroup.find('input').val("");
			priceGroup.find('.control-help').hide();
		}
		if(this.checked&&this.value==2&&!formatGroup.is(":visible")){
			priceGroup.hide();
			formatGroup.show();
			formatGroup.find("input[type='text']").val("");
			formatGroup.find('.control-help').hide();
			formatGroup.find("input[name='hairstylist'],input[name='solution']").each(function(){
				this.disabled=true;
			})
			formatGroup.find("input[type='checkbox']").each(function(){
				this.checked=false;
				if(this.name=="sex"||this.name=="longhair"){
					this.disabled=true;
				}
			});
			$("#format-table").html("");
		}
	}).on('change','#type',function(){
		//是否显示快剪等级
		if(this.value==8){
			$('#level').show();
		}else{
			$('#level').hide();
		}
		$('#template').html(lib.ejs.render({text:$('#template-t').html()},{data:templateData['_'+this.value]}));
		$('#template a').on('click',function(){
			$('textarea[name="desc"]').val($(this).data('value'));
		})
	}).on('click','.limit',function(){//有无限制状态的切换
		$(this).closest("span").siblings('input').attr('disabled',this.checked);
		$(this).closest("span").siblings('label').children('input').attr('disabled',this.checked);
	}).on('change','#project',function(){//选择项目后渲染出对应的项目详情
		init(this.value);
	}).on('pass','input[name="timingShelves"]',function(){
		var timingAdded=$('input[name="timingAdded"]');
		var $this=$(this);
		if(timingAdded.val()&&$this.val()&&new Date(timingAdded.val()).getTime()>new Date($this.val()).getTime()){
			$this.trigger("error",{type:"error",errormsg:"下架时间必须大于上架时间"})
		}
	});
	$('#type1').on('change',function(){
		$('#project').html("");
	})
	var init=function(id){
		id=id||lib.query.id;
		var ajat="";
		if(id){
			ajat="item/show/";
			if(lib.query.p=="warehouse"){
				if(lib.query.type=="item"){
					ajat="warehouse/show/"
				}
				if(lib.query.type=="special"){
					ajat="warehouse/detail/"
				}
			}
			if(lib.query.p=="special"){
				ajat="onsale/show/"
			}
			ajat+=id;
		}
		lib.ajat(ajat+"#domid=form&tempid=form-t").render();
	
		$('#form').one('_ready',function(e,data){
			if(!lib.query.id){
				$('#_form')[0].goback=function(){
					var ln=location;
					parent.lib.popup.confirm({
						text:"添加项目成功!是否继续添加?",
						define:function(){
							ln.reload();
						},
						cancel:function(){
							history.back();
						}
					})
				}
			}
			//图片上传
			lib.puploader.image({
				browse_button: 'imageUpload',
				auto_start:true,
				filters: {
					mime_types : [
						{ title : "Image files", extensions : "jpg,png,jpeg" }
					]
				},
				crop:true
			},function(uploader){
				 uploader.bind('ImageUploaded',function(up,response){
					lib.cropper.create({
						src:response.img,
						thumbnails  : ["300x300"],
						define:function(data){
							up.preview($('.control-single-image'),{img:data['300x300']});
						}
					});                
				});
			});
			//反解析有规格的数据
			if(data.response&&!data.norms_cat_id==1&&data.response.userId!=0&&data.response.prices){
				var parseData={
					sex:[],
					hairstylist:[],
					longhair:[],
					solution:[]
				}
				var normMenu=[];
				var normarr=[];
				var tmpArray=[];
				data.response.prices.forEach(function(item){
					if(item.formats){
						var map={
							"发长":"longhair",
							"性别":"sex",
							"造型师":"hairstylist",
							"药水":"solution"
						}
						item.type={};
						item.formats.forEach(function(item2){
							if(map[item2.formats_name]){
								var tmp=parseData[map[item2.formats_name]];
								if(tmp&&tmp.indexOf&&tmp.indexOf(item2.format_name)==-1){
									parseData[map[item2.formats_name]].push(item2.format_name);
								}
								if(normMenu.indexOf(map[item2.formats_name])==-1){
									normMenu.push(map[item2.formats_name]);
								}
								item.type[map[item2.formats_name]]=item2.format_name;
							}
						});
						item.price=item.price&&parseInt(item.price)>0?parseInt(item.price):"";
						item.priceDis=item.price_dis&&parseInt(item.price_dis)>0?parseInt(item.price_dis):"";
						delete item.price_dis;
						item.priceGroup=item.price_group&&parseInt(item.price_group)>0?parseInt(item.price_group):"";
						delete item.price_group;
						delete item.formats;
						delete item.salon_item_format_id;
					}
					if(tmpArray.indexOf(JSON.stringify(item))==-1){
						normarr.push(item);
						tmpArray.push(JSON.stringify(item));
					}
				});
				//动态还原输入数据
				for(var name in parseData){
					if(parseData[name].length>0){
						var checkbox=$(".item-checkbox[value='"+name+"']");
						checkbox[0].checked=true;
						checkbox.closest('.control-label').next().find('input').attr('disabled',false);
						var btn=checkbox.closest('.control-label').next().children('button').attr('disabled',false);
						btn.siblings('input').remove();
						parseData[name].forEach(function(item){
							if(name=='sex'||name=='longhair'){
								$('input[type="checkbox"][value="'+item+'"]').attr({'checked':true});
							}else{
								btn.before('<input type="text" class="input-small" maxlength="10" nospace name="'+name+'" value="'+item+'">');
							}
						});
					}
				}
				//生成价格表
				$('#format-table').html(lib.ejs.render({text:$('#format-table-t').html()},{data:normarr,normMenu:normMenu}));
				$('input[name="normMenu"]').val(JSON.stringify(normMenu));
				$('input[name="normarr"]').val(JSON.stringify(normarr));
			}
			
			//原价与其他折扣价限制
			var $price=$('input[name="price"]');
			$price.on('pass',function(){
				$('input[name="priceDis"]').attr('max',this.value);
			});
			$('input[name="priceDis"]').on('pass',function(){
				$('input[name="priceGroup"]').attr('max',this.value);
			})
			//计算臭美价格
			$('.choumei-discount').on('blur',function(){
				var val=$price.val();
				if(val&&!isNaN(val)&&this.value&&!isNaN(this.value)){
					var num=Math.round(parseInt(val)*parseInt(this.value)/100);
					$('input[name="priceDis"]').attr('max',val).val(num>0?num:"");
				}
			});
			//计算集团价格
			$('.group-discount').on('blur',function(){
				var val=$('input[name="priceDis"]').val();
				if(val&&!isNaN(val)&&this.value&&!isNaN(this.value)){
					var num=Math.round(parseInt(val)*parseInt(this.value)/100);
					$('input[name="priceGroup"]').attr('max',val).val(num>0?num:"");
				}
			});
			var $format=$('#format-form');
			//生成价格表
			var generate=function(){
				$('input[name="normarr"]').val('');
				var itemCheckbox=$('.item-checkbox:checked');
				if(itemCheckbox.length==0){
					parent.lib.popup.alert({text:'至少要选择一个规格项'})
				}else{
					$format.find('input[type="text"],input[type="checkbox"]').blur();
					if($format.find('.control-help:visible').length==0){
						var sexArr=[];
						$('input[name="sex"]:checked').each(function(){
							if(!this.disabled){
								sexArr.push({sex:this.value});
							}
						});
						var longhairArr=[];
						$('input[name="longhair"]:checked').each(function(){
							if(!this.disabled){
								longhairArr.push({longhair:this.value});
							}
						});
						var hairstyArr=[];
						$('input[name="hairstylist"]').each(function(){
							if(this.value&&!this.disabled){
								hairstyArr.push({hairstylist:this.value});
							}
						});
						var solutionArr=[];
						$('input[name="solution"]').each(function(){
							if(this.value&&!this.disabled){
								solutionArr.push({solution:this.value});
							}
						});
						var array=[];
						if(sexArr.length>0){
							array.push(sexArr);
						}
						if(hairstyArr.length>0){
							array.push(hairstyArr);
						}
						if(longhairArr.length>0){
							array.push(longhairArr);
						}
						if(solutionArr.length>0){
							array.push(solutionArr);
						}
						var data=[];
						if(array.length>0){
							array[0].forEach(function(item1){
								if(array[1]){
									array[1].forEach(function(item2){
										if(array[2]){
											array[2].forEach(function(item3){
												if(array[3]){
													array[3].forEach(function(item4){
														data.push({type:$.extend({},item1,item2,item3,item4)});
													});
												}else{
													data.push({type:$.extend({},item1,item2,item3)});
												}
											});
										}else{
											data.push({type:$.extend({},item1,item2)});
										}
									});
								}else{
									data.push({type:$.extend({},item1)});
								}
							});
						}
						var normMenu=[];
						itemCheckbox.each(function(){
							normMenu.push(this.value);
						});
						$('input[name="normMenu"]').val(JSON.stringify(normMenu));
						$('#format-table').html(lib.ejs.render({text:$('#format-table-t').html()},{data:data,normMenu:normMenu}));
					}
				}
			}
			$('#btn-price').on('click',function(){
				if($('#format-table table').length>0){
					parent.lib.popup.confirm({
						text:"您正在重新生成价格表，当前数据将失效，是否继续",
						define:function(){
							generate();
						}
					})
				}else{
					generate();
				}
			});
			//校验性别，发长字段
			$format.on('blur','.input-small',function(e){
				if(this.disabled) return;
				var $this=$(this);
				var arr=[];
				$this.parent().children('input').each(function(){
					if(this.value){
						arr.push(this.value);
					}
				})
				if(arr.length==0){
					$this.siblings('.control-help').show();
				}else{
					$this.siblings('.control-help').hide();
				}
				e.stopPropagation();
			});
			//如果输入臭美，集团价折扣则到输入原价后生成臭美，集团价
			$('#format-table').on('blur','.format-price',function(){
				var $this=$(this);
				var value=$this.val();
				if(value&&!isNaN(value)){
					//生成臭美价
					var choumeiPrecent=$('#choumei-precent').val();
					if(choumeiPrecent&&!isNaN(choumeiPrecent)){
						var price=Math.round(parseInt(choumeiPrecent)*parseInt(value)/100);
						if(price>0){
							$this.closest('tr').find(".format-price-dis").attr('max',value).val(price>0?price:"");
						}
						//生成集团价
						var groupPrecent=$('#group-precent').val();
						if(groupPrecent&&!isNaN(groupPrecent)){
							var num=Math.round(parseInt(groupPrecent)*price/100);
							$this.closest('tr').find('.format-price-group').attr('max',price).val(num>0?num:"");
						}
					}
					
				}
			}).on('blur','input',function(){
				$(this).removeClass("err");
				var normarr=[];
				var tmp=0;
				var trs=$('#format-table tbody tr');
				trs.each(function(){
					var $tr=$(this);
					var obj={type:{}};
					$tr.children().each(function(){
						var $td=$(this);
						var input=$td.children('input');
						if(input.length==0){
							obj.type[$td.data('name')]=$td.text();
						}else{
							var price=input.val();
							obj[$td.data('name')]=(!isNaN(price)?price:"");
						}
					});
					normarr.push(obj);
					if(obj.price&&obj.priceDis){
						tmp++;
					}
				});
				if(tmp>=2){
					$('input[name="normarr"]').val(JSON.stringify(normarr)).data("length",tmp);
				}else{
					$('input[name="normarr"]').val('').data("length",2-tmp);
					
				}
			}).on('pass','input',function(){
				var $this=$(this)
				$this.parent().next().find('input').attr('max',$this.val());
				
			}).on('error','input[name="normarr"]',function(){
				var length=$(this).data("length")?parseInt($(this).data("length")):0;
				$('#format-table tbody tr').each(function(i){
					var $tr=$(this);
					var input=$tr.find('input');
					if(length!=0&&(!input.eq(0).val()||!input.eq(1).val())){
						if(!input.eq(0).val()){
							input.eq(0).addClass('err')
						}
						if(!input.eq(1).val()){
							input.eq(1).addClass('err')
						}
						length--;
					}
				})
			});
			$('.choumei-discount,.group-discount,#choumei-precent,#group-precent').on('keyup',function(){
				this.value=this.value.replace(/\D/g,"").replace(/^0/g,"");
				if(this.value&&parseInt(this.value)>100){
					this.value=100;
				}
			})
		});
	}
	init();
});