var cfg={
	version:'2.0.0.7',
	url:'<#MANAGER_BACK_URL#>',
	env:'dev',
	dev:{
		host:'http://192.168.13.46:8200/',
		token:'http://dev-cmweb.choumei.me/v1/file/qiniu/get-token.html',
		upload:'http://qiniu-plupload.qiniudn.com/',
		previewMessage:'http://dev-user.choumei.me/'
	},
	test:{
		host:'<#MANAGER_BACK_URL#>',
		token:'http://test-cmweb.choumei.me/v1/file/qiniu/get-token.html',
		upload:'http://qiniu-plupload.qiniudn.com/',
		previewMessage:'http://test-user.choumei.me/'
	},
	uat:{
		host:'<#MANAGER_BACK_URL#>',
		token:'http://uat-cmweb.choumei.cn/v1/file/qiniu/get-token.html',
		upload:'http://qiniu-plupload.qiniudn.com/',
		previewMessage:'http://uat-user.choumei.cn/'
	},
	product:{
		host:'<#MANAGER_BACK_URL#>',
		token:'http://cmweb.choumei.cn/v1/file/qiniu/get-token.html',
		upload:'http://qiniu-plupload.qiniudn.com/',
		previewMessage:'http://user.choumei.cn/'
	},
	getHost:function(){
		return this[this.env].host;
	}
}
if(location.href.indexOf("http://test-")>-1){
	cfg.env="test";
}else if(location.href.indexOf("http://uat-")>-1){
	cfg.env="uat";
}else if(cfg.url&&cfg.url.indexOf('<#')==-1){
	cfg.env="product";
}
if(location.href=="http://manager.choumei.cn/"&&cfg.url.indexOf('<#')!=-1){
	alert('<#MANAGER_BACK_URL#>占位符没有被替换');
}
cfg.url=cfg[cfg.env];
document.writeln('<meta name="renderer" content="webkit|ie-stand">');
document.writeln('<meta http-equiv=”X-UA-Compatible” content=”IE=edge” > ');
document.writeln('<meta charset="utf-8">');
document.writeln('<meta http-equiv="Access-Control-Allow-Origin" content="*">');
document.writeln('<link rel="icon" href="http://app.choumei.cn/images/logo16.ico" type="image/x-icon">');
document.writeln('<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no">');
document.writeln('<meta name="apple-mobile-web-app-capable" content="yes">');
document.writeln('<meta name="apple-mobile-web-app-status-bar-style" content="black">');
document.writeln('<meta name="format-detection" content="telephone=no">');
document.writeln('<link rel="stylesheet" href="/awesome/css/font-awesome.min.css?v='+cfg.version+'" />');
document.writeln('<link rel="stylesheet" href="/css/reset.css?v='+cfg.version+'" />');
document.writeln('<link rel="stylesheet" href="/css/global.css?v='+cfg.version+'" />');
document.writeln('<link rel="stylesheet" href="/css/admin.css?v='+cfg.version+'" />');
document.writeln('<!--[if lt IE 9]><script type="text/javascript" src="/js/array.js"></script><![endif]-->');
document.writeln('<script type="text/javascript" src="/js/jquery.min.js?v='+cfg.version+'"></script>');
document.writeln('<!--[if IE 8]><script type="text/javascript" src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script><![endif]-->');
document.writeln('<!--[if lt IE 10]><script type="text/javascript" src="/js/jquery.placeholder.js"></script><![endif]-->');
document.writeln('<script type="text/javascript" src="/js/sea.js?v='+cfg.version+'"></script>');
document.writeln('<script type="text/javascript" src="/js/ejs.js?v='+cfg.version+'"></script>');
document.writeln('<script type="text/javascript" src="/js/lib.js?v='+cfg.version+'"></script>');
document.writeln('<script type="text/javascript" src="/js/access.js?v='+cfg.version+'"></script>');
document.writeln('<script type="text/javascript" src="/js/admin.js?v='+cfg.version+'"></script>');
document.writeln('<!--[if lt IE 10]><script type="text/javascript">var ie9=true;</script><![endif]-->');
