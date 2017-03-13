/* 
* @Author: anchen
* @Date:   2015-09-21 17:44:57
* @Last Modified by:   anchen
* @Last Modified time: 2015-10-13 20:08:13
*/

  $(document).ready(function(){
      $("#table").delegate('tbody input[type="checkbox"]', 'change', function(event) {
          var button=$('.table-bottom button');
          if($('tbody label input:checked').length>0){
            button.attr('disabled',false);
          }else{
            button.attr('disabled',true);
          }
      });

      $("#table").delegate(".reject",'click',function(){
          var type = $(this).data('type');
          var arr  = [];          
          if(type == "0") arr.push($(this).data('id'));
          if(type == "1") {
            $('tbody input[type="checkbox"]:checked').each(function(index,obj){
              arr.push($(obj).data('id'));
            })
          }

          parent.lib.popup.prompt({
             text   : '拒绝原因： ',
             define : function(str){
                lib.ajax({
                  url  : 'refund/reject',
                  type : "post",
                  data :  {ids:arr.join(","),reason:str}
                }).done(function(data, status, xhr){
                  if(data.result == "1"){
                    parent.lib.popup.result({
                      text:"操作成功",
                      define:function(){
                        lib.ajat('refund/index?<%=query._%>#domid=table&tempid=table-t').render();
                      }
                    });                    
                  }
                });  
             }
          });        
      });

     $("#table").delegate('.pass', 'click', function(event) {
          var type = $(this).data('type');
          var arr = [];
          var message = "你确定要执行同意操作？";
          if(type == "0") {
            arr.push($(this).data('id'));
          }
          if(type == "1") {
            arr.push($(this).data('id'));
            message = "你确定要执行重新退款操作？";
          }
          if(type == "2") {
            $('tbody input[type="checkbox"]:checked').each(function(index,obj){
              arr.push($(obj).data('id'));
            })
          }    
          if(arr.length > 20){
            parent.lib.popup.alert({
              text : "一次最多只能操作20条数据！"
            });
            return;
          }               
          parent.lib.popup.confirm({
              text:message,
              define:function(){
                lib.ajax({
                  url  : 'refund/accept',
                  type : "post",
                  data :  {ids:arr.join(",")}
                }).done(function(data, status, xhr){
                  var arr = [];
                  if(data.result == "1"){
                    data = data.data;
                    if(data.alipay && data.alipay.info){
                       arr.push(data.alipay.info);
                    } 
                    if(data.wx && data.wx.info){
                      arr.push(data.wx.info);
                    } 
                    if(data.yilian && data.yilian.info){
                      arr.push(data.yilian.info);
                    }
                    if(data.balance && data.balance.info){
                      arr.push(data.balance.info);
                    }
                    if(arr.length > 0) tip(arr);
                    if(data.alipay && data.alipay.form_args){
                      $.each($("#alipaysubmit").serializeArray(),function(i,field){
                        $("input[name='"+field.name+"']").val(data.alipay.form_args[field.name]);
                      })
                      $("#alipaysubmit").submit();
                    }
                    lib.ajat('refund/index?<%=query._%>#domid=table&tempid=table-t').render();
                  }                                   
                });                        
              }
          });

          function tip (arr) {
            var str = "";
            arr.forEach(function(s,i){
              str += s + "<br>";
            })
            parent.lib.popup.box({
                width:700,
                height:$(window).height()-100,
                title:'<h1>操作结果</h1>',
                content:str   
            });              
          }          
     });
  });