/* 
* @Author: anchen
* @Date:   2015-07-06 09:57:40
* @Last Modified by:   anchen
* @Last Modified time: 2015-07-06 11:15:47
*/

var utils = {

    getSearchString : function(name){
        var searchStr = location.search;
        var searchArr = searchStr.split("?");
        if(searchArr.length > 1) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var arr = searchArr[1].match(reg);
            if(arr) return decodeURI(arr[2]);
        }

    }

}