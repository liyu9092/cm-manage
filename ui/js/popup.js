define(function(require,exports,module){
	var path='/js/popup';
    var popup= {
        alert: function (options) {
            var self=this;
            options.type = 'alert';
            var popup = $(new EJS({url: path}).render({options: options}));
            popup.on('click', '.popup-alert-define', function () {
				self.close();
                options.define && options.define.call(this);
            });
            this.overlay();
            this.append(popup);
			options.complete && options.complete.call(popup[0],options);
        },
        confirm: function (options) {
            var self=this;
            options.type = 'confirm';
            var popup = $(new EJS({url: path}).render({options: options}));
            popup.on('click', '.popup-alert-define', function () {
				self.close();
                options.define && options.define.call(this,popup.find('.popup-prompt-input').val());
            });
            popup.on('click', '.popup-alert-cancel', function () {
				self.close();
				options.cancel && options.cancel();
            });
            this.overlay();
            this.append(popup);
			options.complete && options.complete.call(popup[0],options);
        },
        prompt: function (options) {
            options.inputType = options.inputType || 'text';
            this.confirm(options);
        },
        sheet: function (options) {
            var self=this;
            options.type = 'sheet';
            options.direction = 'bottom';
            var popup = $(new EJS({url: path}).render({options: options}));
            popup.on('click', '.popup-sheet-item', function () {
                var $this = $(this);
				self.close();
                options.define && options.define.call(this,{id: $this.data('id'), name: $this.text()});
            });
            popup.on('click', '.popup-sheet-cancel', function () {
                self.close();
            });
            this.overlay();
            this.append(popup);
			options.complete && options.complete.call(popup[0],options);
        },
        menu: function (options) {
            options.type = 'menu';
            var popup = $(new EJS({url: path}).render({options: options}));
            var self=this;
            popup.on('click', '.popup-menu-item', function () {
                var $this = $(this);
                if(options.active){
                    $this.addClass('popup-menu-active').siblings().removeClass('popup-menu-active');
                }
                options.define && options.define.call(this,{id: $this.data('id'), name: $this.text()});
                self.close();
            });
            popup.find('.popup-menu-list').css('maxHeight', $(window).height() - 150);
            this.overlay().on('click',function(){
                self.close();
            });
            this.append(popup);
			options.complete && options.complete.call(popup[0],options);
        },
		swiper:function(options){
			var self=this;
			seajs.use([location.origin+'/js/swiper.min.js',location.origin+'/css/swiper.css'],function(){
				options.type='swiper';
				var popup = $(new EJS({url: path}).render({options: options}));
				popup.on('click', '.swiper-close', function () {
					self.close();
				});
				popup.on('click', '.swiper-slide', function (e) {
					if(e.target==this){
						self.close();
					}
				});
				$(document.body).append(popup);
				popup.css({
					top:"0",
					left:"0",
					width:"100%",
					height:"100%"
				});
				popup.find('.swiper-container').height($(window).height());
				self.overlay().css('background','rgba(0,0,0,0.85)');
				var swiper = new Swiper(popup.find('.swiper-container')[0], {
					loop: false,
					initialSlide : options.index,
					lazyLoading : true,            
					pagination: '.swiper-pagination',
					nextButton: '.swiper-button-next',
					prevButton: '.swiper-button-prev',
					slidesPerView: 1,
					paginationClickable: true,
					preloadImages:true,
					spaceBetween: 0
				});
				options.complete && options.complete.call(popup[0],options);
			});
		},
		box:function(options){
			var self=this;
			options=$.extend({width:800,height:500,type:"box"},options);
			var $window=$(window);
			if(options.width>$window.width()){
				options.width=$window.width();
			}
			if(options.height>$window.height()){
				options.height=$window.height();
			}
			var self=this;
            var popup = $(new EJS({url: path}).render({options: options}));
			popup.on('click', '.popup-box-close', function () {
				self.close();
			});
			popup.on('click', '.popup-alert-define', function () {
				self.close();
                options.define && options.define.call(this,popup.find('.popup-prompt-input').val());
            });
            popup.on('click', '.popup-alert-cancel', function () {
				self.close();
				options.cancel && options.cancel();
            });
			popup.css({
				left:"50%",
				top:"50%",
				width:options.width,
				minHeight:options.height,
				marginTop:-options.height/2,
				marginLeft:-options.width/2
			});
			$('body').append(popup);
			var minHeight=options.height-popup.find('.popup-box-title').outerHeight()-popup.find('.popup-footer').outerHeight();
			popup.find('.popup-box-body').css({height:minHeight});
            this.overlay();
			options.complete && options.complete.call(popup[0],options);
		},
        overlay: function () {
            var $dom=$('<div class="popup-overlay"></div>');
            $(document.body).append($dom);
            return $dom;
        },
        close: function () {
            var popup = $('.popup,.popup-overlay');
            popup.fadeOut(200, function () {
				popup.remove();
            });
        },
        append:function(popup){
			if(!lib.tools.browser.mobile){
				popup.css({
					left:($(window).width()-300)/2,
					width:300
				});
			}
            $(document.body).append(popup);
            setTimeout(function(){
                popup.addClass('popup-trans');
            },100);
        }
    }
    module.exports=popup;
})