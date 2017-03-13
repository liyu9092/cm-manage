'use strict'
var orderInfo = {},
    gift_num = 0,
    projectList = {},
    id = lib.query.id;
var completeDefine = function() {
    var popup = $(this);
    var form = popup.find('form');
    var newForm = new lib.Form(form[0]);
    newForm.el._getFormData = function() {
        var returnData = lib.tools.getFormData($(this));
        returnData.deduction_money = returnData.deduction_money || 0;
        return returnData;
    }
    form.on('success', function() {
        parent.lib.popup.result({
            text: '操作成功',
            define: function() {
                location.reload();
            }
        });
    })
}

function inArray(obj,array){
    for(var i =0,len= array.length;i<len;i++){
        if (obj == array[i]){
            return true;
        }
    }
    return false;
}

var completeRefund = function() {
    var popup = $(this);
    var form = popup.find('form');
    var newForm = new lib.Form(form[0]);
    newForm.el._getFormData = function() {
        var returnData = lib.tools.getFormData($(this));
        returnData.deduction_money = returnData.deduction_money || 0;
        return returnData;
    }
    lib.Form.prototype.success = function(data) {
        data = data.data || {};
        if (data.alipay && data.alipay.form_args) {
            $.each($("#alipaysubmit").serializeArray(), function(i, field) {
                $("input[name='" + field.name + "']").val(data.alipay.form_args[field.name]);
            })
            $("#alipaysubmit").submit();
        }
        var msg = "退款成功";
        if (data.wx) {
            msg = data.wx.info;
        } else if (data.balance) {
            msg = data.balance.info;
        } else if (data.yilian) {
            msg = data.yilian.info;
        }
        parent.lib.popup.result({
            text: msg,
            define: function() {
                location.reload();
            }
        });
    }
}

var ModalList = {
    'receive': function(){
        return {
            'title': '选择项目',
            'modalType': 'box',
            'url': './modal_receive',
            'complete': completeDefine
        }
    },
    'cashier': function(){
        return {
            'title': '收银',
            'modalType': 'box',
            'url': './modal_cashier',
            'complete': completeDefine
        }
    },
    'comp-color': function(){
        return {
            'title': '用户补色记录',
            'modalType': 'box',
            'url': './modal_compColor',
            'complete': completeDefine
        }
    },
    'refund': function(){
        return {
            'title': '退款',
            'modalType': 'box',
            'url': './modal_refund',
            'complete': completeRefund
        }
    },
    'receipt': function(){
        return {
            'text': '你正在给订单开发票，确认开票后不能退回，是否继续',
            'cancelText': '取消',
            'defineText': '确认',
            'modalType': 'confirm',
            'define': function() {
                lib.ajax({
                    url: 'book/bill/' + id
                }).done(function(data, status, xhr) {
                    if (data.result == 1) {
                        location.reload();
                    }
                });
            }
        }
    },
    'beautyrefund': function(){
        return {
            'text': '你正在给订单退还定金，是否继续',
            'cancelText': '取消',
            'defineText': '确认',
            'modalType': 'confirm',
            'define': function() {
                lib.ajax({
                    url: 'book/refund_booking/'+id
                }).done(function(data, status, xhr) {
                    if (data.result == 1) {
                        location.reload();
                    }
                });
            }
        }
    },
    'destroy': function(){
        return {
            'text': '你正在删除此订单',
            'cancelText': '取消',
            'defineText': '确认',
            'modalType': 'confirm',
            'define': function() {
                lib.ajax({
                    url: 'book/destroy/' + id
                }).done(function(data, status, xhr) {
                    if (data.result == 1) {
                        location.reload();
                    }
                });
            }
        }
    },
    'present': function(){
        return {
                'text': '你正在给用户赠送\‘德国SEYO无创水光针\’，此用户今日已赠送'+gift_num+'次，是否继续',
                'cancelText': '取消',
                'defineText': '继续',
                'modalType': 'confirm',
                'define': function() {
                    lib.ajax({
                        url: 'book/gift/' + id
                    }).done(function(data, status, xhr) {
                        if (data.result == 1) {
                            location.reload();
                        }
                    });
                }
            }
    },
};

var transitionToView = function($this) {
    $this.parents('.list-header').find('.displayOnEdit').hide().siblings('.displayOnView').show();
}

var transitionToEdit = function($this) {
    $this.parents('.list-header').find('.displayOnView').hide().siblings('.displayOnEdit').show();
}

var renderArea = function(url, action, data) {
    lib.ajat(url + '&action=' + action).template(data);
}

var initModal = function(options) {
    if (options.url && options.url.indexOf('./') > -1) {
        options.content = lib.ejs.render({
            url: options.url
        });
    }
    options.height = $(window).height() - 200;
    parent.lib.popup[options['modalType']](options);
}

var renderPages = function(){
    lib.ajax({
        url: 'book/show/'+lib.query.id
    }).done(function(data, status, xhr) {
        if (data.result == 1) {
            orderInfo = data.data
            parent.orderInfo = orderInfo;
            setProjectList(orderInfo);
            gift_num =orderInfo.order.gift_num;
            lib.ajat('#domid=table&tempid=table-t').template(orderInfo);
            lib.ajat('#domid=breadcrumb&tempid=breadcrumb-t').template(orderInfo);
        }
    });
}


var setProjectList = function(orderInfo){
    var returnData= {};
    orderInfo.beauty_order_item.forEach(function(item,index){
        returnData[item.id] = item;
    })
    projectList = returnData;
    return returnData;
}

var bindEvent = function() {
        $('body').on('click', '[data-modal]', function() {
            var modal = $(this).attr('data-modal');
            initModal(ModalList[modal]());
        }).on('click', '.list-group .slideBtn', function(e) {
            var $this = $(this),
                $list = $this.closest('.list');
            $list.toggleClass('active').children('.list-content').slideToggle(200);
        }).on('autoinput', 'input[ajat-complete]', function(e,res) {
            var $this = $(this);
            $this.siblings('.inputId').val(res.id);
        }).on('click', '.editBtn a', function() {
            var $this = $(this),
                url = $this.attr('data-url'),
                action = $this.attr('data-action'),
                $list = $this.closest('.list'),
                type = $this.attr('data-type'),
                id = $this.attr('data-id');
            if (url){
                renderArea(url, action, {});
                transitionToEdit($this);
            }else{
                if (!$list.hasClass('active')) {
                    $list.toggleClass('active').children('.list-content').slideToggle(200);
                }
                if (action == 'edit'){
                    var insertHtml = lib.ejs.render({url:"./template/order_component/plan_desc"},{
                        'protocol':{
                            'custom':{
                                'id':id,
                                'action':'edit',
                                'type':type
                            }
                        }
                    })
                    $list.children('.list-content').html(insertHtml);
                    var $updateForm = $list.find('form[action^="book/update/"]')
                    if ($updateForm){
                        var newForm = new lib.Form($updateForm);
                        newForm.el._getFormData = function() {
                        var returnData = lib.tools.getFormData($(this));
                        var tempArray = {'guideId':1,
                                        'beauticianId':2,
                                        'deanId':3,
                                        'assistantId':4};
                        var tmpAttr = {};
                        for (var prop in returnData) {
                            if (!tempArray[prop]){
                                tmpAttr[prop]=returnData[prop];
                                delete returnData[prop];
                            }
                        }
                        returnData.attr = JSON.stringify(tmpAttr);
                            return returnData;
                        }
                    }
                    var $makeupForm = $list.find('form[action^="book/relatively/"]')
                    if ($makeupForm){
                        var newForm = new lib.Form($makeupForm);
                        newForm.el._getFormData = function() {
                        var returnData = lib.tools.getFormData($(this));
                        var tempArray = {'base_id':1};
                        var tmpAttr = {};
                        for (var prop in returnData) {
                            if (!tempArray[prop]){
                                tmpAttr[prop]=returnData[prop];
                                delete returnData[prop];
                            }
                        }
                        returnData.attr = JSON.stringify(tmpAttr);
                            return returnData;
                        }
                    }
                    transitionToEdit($this);
                }else if (action=='makeup1' || action=='makeup2'){
                    var insertHtml = lib.ejs.render({url:"./template/order_component/plan_header"},{
                        'protocol':{
                            'custom':{
                                'id':id,
                                'action':'edit',
                                'type':action
                            }
                        }
                    });
                    $this.hide()
                    if (action == 'makeup1'){
                        $list.after(insertHtml);  
                        var $newList = $list.next('.makeup1')
                    }else{
                        $list.next('.makeup1').after(insertHtml);
                        var $newList = $list.next('.makeup2')
                    }
                    var $form = $newList.find('form[action^="book/"]');
                    var newForm = new lib.Form($form);
                }else{

                }
            }
        }).on('click', '.displayOnEdit a', function() {
            var $this = $(this),
                url = $this.attr('data-url'),
                action = $this.attr('data-action'),
                $list = $this.closest('.list'),
                type = $this.attr('data-type'),
                id = $this.attr('data-id');
            if (url){
                renderArea(url, action, {});
                transitionToView($this);
            }else{
                if (action == 'save') {
                    var $form =$this.closest('.list').find('.list-content form').eq(0);
                    $form.submit().on('success',function(){
                        parent.lib.popup.result({text:'项目更新成功',define:function(){location.reload()}});
                    })
                } else if (action == 'view') {
                    if (type == 'makeup'){
                        parent.lib.popup.confirm({
                            'text':'取消补色？',
                            'define':function(){
                                location.reload();
                            }
                        })
                        return false;
                    }
                    var insertHtml = lib.ejs.render({url:"./template/order_component/plan_desc"},{
                        'protocol':{
                            'custom':{
                                'id':id,
                                'action':'view',
                                'type':type
                            }
                        }
                    })
                    $list.children('.list-content').html(insertHtml);
                    transitionToView($this);
                }
            }
        })
    }

$(function(){
    renderPages();
    bindEvent();
})