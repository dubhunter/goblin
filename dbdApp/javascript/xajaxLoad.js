$(function(){
	$.xajaxLoad();
});
(function ($){
	$.xajaxLoad = function (options){
		return $.xajaxLoad.impl.init(this,options);
	};
	$.xajaxLoad.show = function (){
		return $.xajaxLoad.impl.load();
	};
	$.xajaxLoad.hide = function (){
		return $.xajaxLoad.impl.doneLoad();
	};
	$.xajaxLoad.bind = function (){
		return $.xajaxLoad.impl.bind();
	};
	$.xajaxLoad.unBind = function (){
		return $.xajaxLoad.impl.unBind();
	};

	$.xajaxLoad.defaults = {
		bind: true,
		opacity: 100,
		overlayId: 'xajaxLoad',
		overlayClass: 'xajaxLoad',
		overlayBg: 'transparent',
		containerId: 'xajaxLoadBox',
		containerClass: 'xajaxLoadBox',
		imageId: 'xajaxLoadImg',
		imageClass: 'xajaxLoadImg',
		imageAlt: 'Loading...',
		imageSrc: '/images/gfx/ajax_load_indicator.gif',
		imageW: 16,
		imageH: 16,
		appendTo: '#pageAll',
		zIndex: 10000
	};

	$.xajaxLoad.impl = {
		opts: {},
		overlay: {},
		div: {},
		msg: {},
		image: {},
		init: function (data, options){
			this.opts = $.extend({}, $.xajaxLoad.defaults, options);
			this.overlay = $('<div>')
				.attr('id', this.opts.overlayId)
				.addClass(this.opts.overlayClass)
				.css({
					opacity: this.opts.opacity / 100,
					cursor: 'wait',
					backgroundColor: this.opts.overlayBg,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					display: 'none',
					zIndex: this.opts.zIndex
				})
				.appendTo(this.opts.appendTo);
			this.div = $('<div>')
				.attr('id', this.opts.containerId)
				.addClass(this.opts.containerClass)
				.css({
					position: 'fixed',
//					left: '50%',
//					top: '40%',
					right: '0',
					bottom: '0',
					textAlign: 'right',
					display: 'none',
					backgroundColor: '#c2d246',
//					border: '1px solid red',
					minHeight: this.opts.imageH,
					padding: '3px',
					paddingLeft: this.opts.imageW + 2,
//					marginLeft: '-' + (this.opts.imageW / 2) + 'px',
//					marginTop: '-' + (this.opts.imageH + 6) + 'px',
					zIndex: (this.opts.zIndex + 1)
				})
				.appendTo(this.overlay);
			this.image = $('<img>')
				.attr('id', this.opts.imageId)
				.attr('src', this.opts.imageSrc)
				.attr('alt', this.opts.imageAlt)
				.addClass(this.opts.imageClass)
				.css({
					position: 'absolute',
					left: '0%',
					top: '50%',
//					right: '80px',
//					top: '30px',
//					display: 'none',
//					marginLeft: '-' + (this.opts.imageW / 2) + 'px',
					marginTop: '-' + (this.opts.imageH / 2) + 'px'
//					zIndex: (this.opts.zIndex + 2)
				})
				.appendTo(this.div);
			this.msg = $('<span></span>')
				.text(this.opts.imageAlt)
				.css({
					color: '#fff'
				})
				.appendTo(this.div);
			if (this.opts.bind)
				this.bind();
		},
		bind: function (){
			var xLoad = this;
			if (typeof xajax !== "undefined"){
				xajax.loadingFunction = function (){xLoad.load()};
				xajax.doneLoadingFunction = function (){xLoad.doneLoad()};
			}
			this.div.ajaxStart(function (){
				xLoad.load();
			});
			this.div.ajaxStop(function (){
				xLoad.doneLoad();
			});
		},
		unBind: function (){
			var xLoad = this;
			if (typeof xajax !== "undefined"){
				xajax.loadingFunction = function (){};
				xajax.doneLoadingFunction = function (){};
			}
		},
		load: function (){
			this.overlay.show();
			this.div.show();
			this.msg.show();
			this.image.show();
		},
		doneLoad: function (){
			this.image.hide();
			this.msg.hide();
			this.overlay.hide();
		}
	};
})(jQuery);