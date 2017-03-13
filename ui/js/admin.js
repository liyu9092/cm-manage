(function(){
	if(location.href.indexOf('popup=')==-1){
		parent.lib.popup.close();//清除父弹出框
	}
	lib.ajatCount=0;//ajat计件数
	lib.ajat=function (_protocol) {
		lib.ajatCount++;//ajat添加计件数
        return new lib.Ajat(_protocol);
    }

	lib.Ajat.before=function(){
		lib.loadingend=function(e){//触发进度条加载完成
			lib.ajatCount--;//ajat减少计件数
			if(lib.ajatCount==0){
				parent.$('body').trigger('loadingend');//终止加载状态
				$(document.body).trigger('asynhashform');//同步hash查询条件
			}
		}
		$(document.body).on('_ready',lib.loadingend).on('exception',lib.loadingend);
	}
	document.onreadystatechange=function(){//注册document的readystatechagne事件
		if(document.readyState=='interactive'){
			parent.$('body').trigger('loading');//开启加载状态
		}
	}
	if(window.ie9){
		parent.$('body').trigger('loading');
	}
	/*子页是否全屏*/
	lib.fullpage=function(bool){
		var page=$('#page');
		if(bool){
			page.addClass('full');
		}else{
			page.removeClass('full');
		}
	}
	parent.lib.fullpage(false);//清除父全屏状态
})();

$(function(){
	/**渲染面包屑**/
	var breadcrumb=$('.breadcrumb');
	if(breadcrumb.length==1){
		breadcrumb.html(lib.ejs.render({text:breadcrumb.html().replace(/%&gt;/g,'%>').replace(/&lt;%/g,'<%')},{}));
	}

	/**hash和加载进度条**/
	var $body=$(document.body);
	lib.tools.hashchange=function(obj){//更改浏览器hash值
		var temphash=location.hash;
		var query=$.extend({},lib.query,obj);
		delete query._;
		for(var name in query){
			if(!query[name]){
				delete query[name];
			}
		}
		location.hash='#'+decodeURIComponent($.param(query).replace(/\+/g,' '));
		return temphash==location.hash;
	}
	$(window).on('hashchange',function(){
		lib.ajatCount=0;
		parent.$('body').trigger('loading');//开启加载状态
		lib.init();//更新query参数
		lib.Ajat.run();//重新执行ajat渲染
		$('html,body').animate({scrollTop:0},200);
	});
	if($('[ajat]').length==0){
		parent.$('body').trigger('loadingend');//终止加载状态
	}

	/**hash表单同步hash查询条件**/
	$body.one('asynhashform',function(){
		var hashForm=$('form[data-role="hash"]');
		var filterShow=false;
		for(var name in lib.query){
			hashForm.find().val(lib.query[name]);
			hashForm.find('input[name="'+name+'"],select[name="'+name+'"]').each(function(){
				var $this=$(this);
				if($this.attr('type')=="checkbox"||$this.attr('type')=="radio"){
					if($this.val()==lib.query[name]){
						this.checked=true;
					}
				}else{
					$this.val(lib.query[name]);
					//同步组合框input-switch/placeholder-switch
					var parent=$this.parent('.input-switch');
					if(parent.length==1){
						parent.children('select option').eq($this.index()-1).attr('selected',true);
						$this.show().siblings('input').hide();
					}
					var parent=$this.parent('.placeholder-switch');
					if($this.is('select')&&parent.length==1){
						$this.next('input').attr('placeholder',$this.children('option:selected').data('placeholder'));
					}
				}
			});
		}
	});

	/**提交hash地址**/
	$body.on('submit','form[data-role="hash"]',function(e){//hash表单提交到hash地址查询
		$(this).trigger('hash');
		e.stopPropagation();
		e.preventDefault();
	}).on('hash','form[data-role="hash"]',function(e){//表单自定义hash提交
		var data={};
		if(this._getFormData){
			data=this._getFormData();
		}else{
			data=lib.tools.getFormData($(this));
		}
		if(data.page===undefined){
			data.page=1;
			//清除排序条件
			if(lib.query.sort_key){
				data.sort_key="";
			}
			if(lib.query.sort_type){
				data.sort_type="";
			}
		}
		if(lib.tools.hashchange(data)){
			$(window).trigger('hashchange');
		}
	}).on('click','a[data-role="hash"]',function(e){//链接hash提交
		var query=lib.tools.parseQuery($(this).attr('href').replace('#',''));
		if(lib.tools.hashchange(query)){
			$(window).trigger('hashchange');
		}
		e.preventDefault();
	}).on('click','label[data-role="hash"]',function(e){//标签hash提交
		if($(e.target).is('input')){
			$(this).closest('form[data-role="hash"]').submit();
		}
	}).on('submit','form[data-role="export"]',function(e){//导出功能
		e.preventDefault();
		var params=lib.tools.getFormData($(this));
		console.log(cfg.getHost()+$(this).attr('action')+"?"+location.hash.replace('#','')+($.param(params)?"&":"")+$.param(params));
		window.open(cfg.getHost()+$(this).attr('action')+"?"+location.hash.replace('#','')+($.param(params)?"&":"")+$.param(params)+'&token='+localStorage.getItem('token'));
	});

	/**普通表单提交**/
	$body.on('submit','form[data-role="normal"]',function(e){//一般的数据提交,提交成功后会触发表单reset事件
		e.preventDefault();
		var $this=$(this);
		if($this.attr('disabled')) return;
		$this.attr('disabled',true);
		var confirm=$this.data('confirm');
		var url=$this.attr('action');
		if(document.activeElement){
			var $active=$(document.activeElement);
			if($active.attr('formaction')){
				url=$active.attr('formaction');
			}
			if($active.data('confirm')){
				confirm=$active.data('confirm');
			}
		}
		if(!confirm&&this.confirm&&typeof this.confirm=='function'){
			confirm=this.confirm();
		}
		var data=lib.tools.getFormData($this);
		if(this._getFormData){
			data=this._getFormData();
		}
		var request=function(){
			lib.ajax({
				url:url,
				data:data,
				type:'POST',
				success:function(data){
					setTimeout(function(){
						$this.attr('disabled',false);
					},1100);
					if(data.result==1){
						var successEvent=$this.attr('onsuccess');
						if(successEvent=="remove"){
							parent.lib.popup.result({
								text:"删除成功",
								define:function(){
									$this.closest('tr').remove();
								}
							});
						}else{
							if(successEvent){
								var fn=eval("(function(){"+successEvent+"})");
								fn.call($this[0]);
							}
							$this.trigger('success',data);//成功后会触发success事件
						}
					}else{
						var failEvent=$this.attr('onfail');
						if(failEvent){
							var fn=eval("(function(){"+failEvent+"})");
							fn.call($this[0]);
						}
						$this.trigger('fail',data);
					}
				},
				error:function(){
					$this.attr('disabled',false);
				}
			});
		}
		if(confirm){
			parent.lib.popup.confirm({
				text:confirm,
				define:function(){
					request();
				},
				cancel:function(){
					$this.attr('disabled',false);
				}
			});
		}else{
			request();
		}
	});

	/**input-switch/placeholder-switch切换**/
	$body.on('change','.input-switch select',function(){//input-switch切换输入框
		var $this=$(this);
		$this.parent().find('input').eq($this.children("option:selected").index()).show().siblings('input').hide().val('');
		if($.placeholder){
			$.placeholder($this.parent().find('input'));
		}
	}).on('change','.placeholder-switch select',function(){//placeholder-switch切换placeholder
		var $this=$(this);
		var placeholder=$this.children('option:selected').data('placeholder');
		$this.next('input').attr('placeholder',placeholder).val('');
		if($.placeholder){
			$.placeholder($this.next('input'));
		}
	});

	/**常见**/
	$body.on('click','.drop-menu-toggle',function(){//下拉菜单
		var $this=$(this);
		$this.parent().toggleClass('open');
	}).on('click','.drop-menu-item',function(){
		$(this).closest('.open').removeClass('open');
	}).on('click',function(e){
		if($(e.target).closest('.open').length==0){
			$('.open').removeClass('open');
			parent.$('.open').removeClass('open');
		}
	}).on('click','.tab li',function(){//选项卡切换
		$(this).addClass('active').siblings().removeClass('active');
	}).on('blur','input[data-role="start"]',function(){//日期区间
		var $this=$(this);
		$this.siblings('input[data-role="end"]').attr('min',$this.val());
	}).on('focus','input[type="date"]',function(e){//日期输入时间限制
		var $this=$(this);
		if(!$this.attr('max')){
			$this.attr('max','9999-12-30');
		}
	});
	/**键盘输入自动补全**/
	$body.on('input','input[ajat-complete]',function(e){//自动补全输入事件
		var $this=$(this);
		var val=$.trim($this.val());
		if(val){
			$this.data('old',val);
			clearTimeout(lib.completeTimer);
			lib.completeTimer=setTimeout(function(){
				var ajat=$this.attr('ajat-complete').replace('${value}',val);
				$this.addClass('complete-loader');
				if(window.ajaxComplete&&window.ajaxComplete.abort){
					window.ajaxComplete.abort();
				}
				var completePosition=$this.closest('.complete').find('.complete-position');
				completePosition.one('exception',function(e){
					e.stopPropagation();
				});
				window.ajaxComplete=lib.ajat(ajat).render().done(function(){
					completePosition.show();
					$this.removeClass('complete-loader');
					delete window.ajaxComplete;
				});
			},200);
		}else{
			$this.closest('.complete').find('.complete-position').hide();
		}
	}).on('keyup','input[ajat-complete]',function(e){//自动补全键盘事件
		if(e.keyCode==13||e.keyCode==38||e.keyCode==40){
			var $this=$(this);
			var complete=$this.closest('.complete');
			if(complete.find('.complete-item').length==0){
				return ;
			}
			if(e.keyCode==40||e.keyCode==38){
				var active=complete.find('.complete-item.active');
				if(active.length==0){
					active=complete.find('.complete-item')[e.keyCode==40?'first':'last']();
				}else{
					if(active[e.keyCode==40?'next':'prev']().length==1){
						active=active[e.keyCode==40?'next':'prev']();
					}
				}
				active.addClass('active').siblings().removeClass('active');
				$this.val(active.text()).trigger('autoinput',active.data());
			}
			if(e.keyCode==13){
				var active=complete.find('.complete-item.active');
				if(active.length>0){
					$this.trigger('autoinput',active.data());
				}
				$this.closest('.complete').find('.complete-position').hide();
			}
			e.preventDefault();
		}
	}).on('keydown','input[ajat-complete]',function(e){
		if(e.keyCode==13){
			e.preventDefault();
		}
	}).on('blur','input[ajat-complete]',function(){//自动补全失去焦点事件
		$(this).closest('.complete').find('.complete-position').hide();
	}).on('mousedown','.complete-item',function(){//自动补全单击事件
		var $this=$(this);
		var complete=$this.closest('.complete');
		complete.find('input[ajat-complete]').val($this.text()).trigger('autoinput',$this.data());
		complete.find('.complete-position').hide();
	}).on('focus','input[ajat-complete]',function(){
		var res=$(this).attr('ajat-resources');
		if(res){
			seajs.use([res]);
		}
	});
	if(document.createElement('input').oninput===undefined){
		$body.on('keyup','input[ajat-complete]',function(e){
			if(e.keyCode==13||e.keyCode==38||e.keyCode==40){
				return;
			}
			$(this).trigger('input');
		})
	}

	/**全局分页**/
	$body.on('_ready',function(e,data){
		var $target=$(e.target);
		var $pager=$target.find('.pager');
		data=data.response;
		if(data&&data.total > 0) {
			seajs.use('/js/jquery.pagination.js',function (){
				var query=$.extend({},lib.query);
				delete query._;
				var currentID = query.page;
				query.page='__id__';
                $pager.pagination(data.total, {
					current_page : currentID == data.current_page ? data.current_page-1 :currentID,
					items_per_page : data.per_page,
					next_text:'>>',
					prev_text:'<<',
					num_display_entries: 7,
					num_edge_entries: 1,
					link_to:location.pathname+'#'+decodeURIComponent($.param(query).replace(/\+/g,' ')),
					callback:function(data){
						$pager.find('.pagination a').off('click').addClass('link');
					}
				});
				$pager.append(lib.ejs.render({url:'/module/public/template/pager'},{data:data}));
			});
		}
		if(data&&data.total==0){
			$pager.html('<div class="data-empty"><i class="fa fa-frown-o"></i>'+($target.attr('data-empty-alert')||"没有查找到相关数据")+'</div>');
		}
		if(data.current_page&&data.total===undefined){
			$pager.append(lib.ejs.render({url:'/module/public/template/pager'},{data:data}));
		}
	});

	/**权限控制**/
	if(parent.access){
		access.control(document.body);
	}
	$body.on('_ready',function(e){
		access.control(e.target);
	});

	/**列表复选框**/
	$body.on('change','.table .select-all input',function(){//全选
		var bool=this.checked;
		var _this=this;
		$(this).closest('.table').find('tbody label input[type="checkbox"]').each(function(){
			if(!this.disabled){
				this.checked=bool;
				$(this).trigger('change');
			}
		});
		$('.select-all input').each(function(){
			if(_this!=this){
				this.checked=bool;
			}
		})
	});
	$body.on('change','.table tbody label input[type="checkbox"]',function(){//表行复选框
		var $this=$(this);
		var tr=$this.closest('tr');
		if(this.checked){
			tr.addClass('tr-selected');
		}else{
			tr.removeClass('tr-selected');
		}
		if($this.closest('.table').find('tbody input[type="checkbox"]:checked').length==0){//取消全选状态
			$('.select-all input').each(function(){
				this.checked=false;
			})
		}
	});

	/**filter-box的展示切换**/
	$body.on('click','#filter-toggle-btn',function(){//注册#filter-toggle-btn事件，控制.filter-box显示与隐藏
		var $this=$(this);
		var box=$(".filter-box").slideToggle(250);
		var icon=$this.children('i');
		var bool=false;
		if(icon.hasClass('fa-angle-down')){
			icon.removeClass('fa-angle-down').addClass('fa-angle-up');
			bool=true;
		}else{
		    icon.removeClass('fa-angle-up').addClass('fa-angle-down');
		}
		localStorage.setItem("filter-toggle",location.pathname+"#"+bool);//记录filter-toggle状态
	});
	//是否触发#filter-toggle-btn单击事件
	var filterBtn=$('#filter-toggle-btn');
	if(filterBtn.length>0){
		var filterToggle=localStorage.getItem("filter-toggle");
		if(filterToggle&&filterToggle.indexOf(location.pathname)>-1){
			if(filterToggle.indexOf("true")>-1){
				filterBtn.click();
			}
		}else{
			localStorage.removeItem('filter-toggle');
		}
	}

	$body.on('click','.breadcrumb a,.menu-category a',function(){//注册事件清除filter-toggle信息
		localStorage.removeItem('filter-toggle');
	});

	/**日期控件修正**/
	seajs.use(['/laydate/laydate.js']);
	if(!lib.tools.browser().webkit){
		$body.on('click','input[type=date]',function(e){
			var options={
				format:($(this).attr('format')||'YYYY-MM-DD'),
				min:this.min,
				max:this.max,
				zIndex:1000,
				choose:function(){
					e.target.focus();
					$(e.target).removeClass('placeholder').trigger("choose");
				}
			};
			laydate(options);
		});
		$body.on('click','input[type=month]',function(e){
			var options={
				format:($(this).attr('format')||'YYYY-MM'),
				min:this.min,
				max:this.max,
				zIndex:1000,
				choose:function(){
					e.target.focus();
					$(e.target).removeClass('placeholder').trigger("choose");
				}
			};
			laydate(options);
		});
	}
	$body.on('click','input[type=_datetime]',function(e){
		var options={
			format:($(this).attr('format')||'YYYY-MM-DD hh:mm:ss'),
			min:this.min,
			max:this.max,
			zIndex:1000,
			istime: true,
			choose:function(){
				$(e.target).blur().focus().removeClass('placeholder').trigger("choose");
			}
		};
		laydate(options);
	});

	/**修正IE9**/
	if(window.ie9){
		$(document.body).addClass("ie9");
		if($.placeholder){//输入框placeholder修正
			$.placeholder();
			$body.on('_ready',function(e){
				$.placeholder($(e.target).find('input[type="text"],textarea'));
			});
		}
	}
	/**缩略图预览**/
	$body.on('click','.control-thumbnails-item img',function(e){
		var item=$(this).closest('.control-thumbnails-item');
		if(item.closest('.popup').length==1) return;
		var list=[];
		item.parent().children('.control-thumbnails-item').each(function(){
			var $this=$(this).find('img');
			var src="";
			if($this.data("type") == "1"){
				src=$this.attr('src')||$this.data('original');
			}else{
				src=$this.data('original')||$this.attr('src');
			}
			if(src.indexOf("?")==-1){
				if(src.indexOf("#")==-1){
					src+="?imageView2/1/w/2000/h/2000"
				}else{
					src=src.replace("#","?imageView2/1/w/2000/h/2000#");
				}
			}
			list.push(src);
		});
		parent.lib.popup.swiper({list:list,index:item.index()});
	});


	$body.on('click','.control-single-image img,.image-preview',function(){
		var $this=$(this);
		if($this.closest('.popup').length==1) return;
		var src=$this.data('original')||$this.attr('src');
		if(src){
			parent.lib.popup.swiper({list:[src],index:0});
		}
	}).on('click','.control-image-single-remove',function(){
		$(this).hide().siblings('img').attr('src','').siblings('input').val("");
	});
	/**实例化封装表单**/
	$('form[data-role="form"]').each(function(){
		if(!this.instance){
			this.instance="instance";
			new lib.Form(this);
		}
	});
	$('form[data-role="hash"]').attr('novalidate','novalidate');
	$body.on('_ready',function(e){
		$(e.target).find('form[data-role="form"]').each(function(){
			if(!this.instance){
				this.instance="instance";
				new lib.Form(this);
			}
		}).find('form[data-role="hash"]').attr('novalidate','novalidate');
	});
	/**btn-cancel操作处理**/
	$body.on('click','.btn-cancel',function(){
		history.back();
	});
	$body.on('exception',function(e,data){
		if(data&&data.errorLevel=='xhr'){
			$(e.target).html('<div class="data-empty tc"><i class="fa fa-frown-o"></i>请求服务异常，<a class="link" onclick="location.reload()">重试</a></div>')
		}
	});
	/**F5刷新**/
	if(parent!=window){
		$(window).on('keydown',function(e){
			if(e.keyCode==116){
				location.reload();
				e.preventDefault();
			}
		});
	}
	/**文本域输入文字提示**/
	$body.on('focus','.keypress textarea',function(){
		var $this=$(this);
		var maxlength=$this.parent().attr('maxlength');
		var value=$this.val();
		if(maxlength){
			$this.parent().append('<span class="keypress-help">还可以输入<em>'+(parseInt(maxlength)-$.trim(value.length))+'</em>个字</span>');
		}
		document.oncontextmenu=function(e){return false;}
	});
	$body.on('blur','.keypress textarea',function(){
		$(this).siblings('.keypress-help').remove();
		document.oncontextmenu=function(e){return true;}
	});
	$body.on('keyup','.keypress textarea',function(){
		var $this=$(this);
		var maxlength=$this.parent().attr('maxlength');
		var value=$this.val();
		if(value.length>maxlength){
			$this.val(value.substring(0,maxlength));
			value=value.substring(0,maxlength);
		}
		if(maxlength){
			$this.siblings('.keypress-help').children('em').text(parseInt(maxlength)-$.trim(value).length);
		}
	});
	/**实例化封装表单**/
	$('form[data-role="form"]').each(function(){
		if(!this.instance){
			this.instance="instance";
			new lib.Form(this);
		}
	});
	$('form[data-role="hash"]').attr('novalidate','novalidate');
	$body.on('_ready',function(e){
		$(e.target).find('form[data-role="form"]').each(function(){
			if(!this.instance){
				this.instance="instance";
				new lib.Form(this);
			}
		}).find('form[data-role="hash"]').attr('novalidate','novalidate');
	});
	$body.on('exception',function(e,data){
		if(data&&data.errorLevel=='xhr'){
			$(e.target).html('<div class="data-empty tc"><i class="fa fa-frown-o"></i>请求服务异常，<a class="link" onclick="location.reload()">重试</a></div>')
		}
	});
	/**F5刷新**/
	if(parent!=window){
		$(window).on('keydown',function(e){
			if(e.keyCode==116){
				location.reload();
				e.preventDefault();
			}
		});
	}
	$body.on("click",'.tab-switch li',function(){
		$(this).addClass("active").siblings().removeClass("active");
	})
}); 

Date.prototype.format = function(format){
    var t = this;
    var o = {
        "M+": t.getMonth() + 1, //month
        "d+": t.getDate(), //day
        "h+": t.getHours(), //hour
        "m+": t.getMinutes(), //minute
        "s+": t.getSeconds(), //second
        "q+": Math.floor((t.getMonth() + 3) / 3), //quarter
        "S": t.getMilliseconds() //millisecond
    };
    if (/(y+)/.test(format)) {
        format = format.replace(RegExp.$1, (t.getFullYear() + "").substr(4 - RegExp.$1.length));
    }

    for (var k in o) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));
        }
    }
    return format;
};
