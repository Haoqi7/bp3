    /**
     * 消息提醒函数
     * type限定为：default/info/success/warning/error中的任一种
     */ 
    function message(text,type,autoRemove=true){
        let text_class = "";
        type = type || "default";
        switch(type){
        case "default":
            text_class = "fa fa-comments";
            break;
        case "info":
            text_class = "fa fa-info-circle text-info";
            break;
        case "success":
            text_class = "fa fa-check-square-o text-success";
            break;
        case "warning":
            text_class = "fa fa-warning text-warning";
            break;
        case "error":
            text_class = "fa fa-close text-danger";
            break;
        default:
            throw "消息type错误，请传递default/info/success/warning/error中的任一种";
        }
        let msgs = $(".appMessage");
        let len = msgs.length; 
        let end = 0;
        let baseHeight = 0;
        if(len>0){
            baseHeight =msgs.first().innerHeight()+20;
            let start = msgs.first().attr('no');
            end = +start+len;
        }
        let height = 100+end*baseHeight+"px";
        $(`<div no='${end}' id='msg-${end}' class='appMessage ${text_class}' style='top: ${height};position: fixed;left: 50%;border: 1px solid #ddd;
        background-color:rgb(220,220,220);transform: translate(-50%, -50%);font-size: 1.2em;padding: 1rem;z-index: 999;border-radius: 0.5rem;cursor:pointer;'>${text}</div>`).appendTo("body");
        //自动删除
        if(autoRemove){
            let rmScript = `$("#msg-${end}").remove();`;
            setTimeout(rmScript,1500);
        }else{
            $(`#msg-${end}`).mouseover(function(){
                $(`.appMessage`).remove();
            });
        }
    }
    //错误消息
    function error_message(text){
        message(text,"error",false);
    }
    //正确消息
    function success_message(text){
        message(text,"success");
    }
    
    /**
     * 延迟后刷新页面，单位毫秒，默认500
     */ 
    function lazy_reload(time){
        time = time || 500;
        setTimeout("location.reload()", time );
    }
    
    /**
     * 延迟后跳转到指定页面，单位毫秒，默认1500
     */ 
    function easy_load(href,time){
        time = time || 1500;
        setTimeout("location.href='"+href+"'", time );
    }

    /**
     * 判断手机端
     */
    function isMobile() {
        if (/(iPhone|iPad|iPod|iOS|Android)/i.test(navigator.userAgent)) {
            return true;
        }else {
            return false;
        };
    }

    /**
     * 显示文件大小
     */
    function showFileSize(byte){
        byte = parseFloat(byte);
        let h_str = '';
        let num = 0;
        if(byte<1024){
            h_str = byte + 'B';
        }else if(byte<1048576){
            num=byte/1024;
            h_str = num.toFixed(2) + 'kB';
        }else if(byte<1073741824){
            num=byte/1048576;
            h_str = num.toFixed(2) + 'MB';
        }else if(byte<1099511627776){
            num=byte/1073741824;
            h_str = num.toFixed(2) + "GB";
        }else if(byte<1125899906842624){
            num = byte/1099511627776;
            h_str = num.toFixed(2) + "TB";
        }else if(byte<1152921504606846976){
            num = byte/1125899906842624;
            h_str = num.toFixed(2) + "PB";
        }else{  // 最大，计算为 EB ，已知 php int 类型最大为 8.00 EB
            num = byte/1152921504606846976;
            h_str = num.toFixed(2) + "EB";
        }
        return h_str;
    }

    /**
     * 时间日期格式化
     * @param fmt
     * @returns {*}
     */
    Date.prototype.format = function (fmt) {
        var o = {
            "M+": this.getMonth() + 1,                   //月份
            "d+": this.getDate(),                        //日
            "h+": this.getHours(),                       //小时
            "m+": this.getMinutes(),                     //分
            "s+": this.getSeconds(),                     //秒
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度
            "S": this.getMilliseconds()                  //毫秒
        };

        //  获取年份
        if (/(y+)/i.test(fmt)) {
            fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        }

        for (var k in o) {
            if (new RegExp("(" + k + ")", "i").test(fmt)) {
                fmt = fmt.replace(
                    RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
            }
        }
        return fmt;
    }

    /**
     * 把当前页面url，记录到localStorage中
     */
    function setPage($limit){
        let $url = window.location.href;
        let $time = new Date().getTime();
        $limit = $limit || "max";   // 无限制
        let $obj = {"url":$url,"time":$time,"limit":$limit};
        localStorage.setItem("page",JSON.stringify($obj));
    }

    /**
     * 获取上次存储的page对象
     */
    function backPage($flag){
        let $str  = localStorage.getItem("page");
        $flag = $flag || "url";
        if($str){
            let $obj = JSON.parse($str);
            if($obj.limit==="max" || (new Date().getTime()-$obj.time)>$obj.limit){
                if($flag==="url"){
                    return $obj.url;
                }
            }
        }
    }

    /**
     * 页面加载公用函数
     */
    $(function () {
        let pageDom = document.getElementById("backPage");
        if(pageDom){
            let backUrl = backPage();
            if(backUrl){
                pageDom.innerHTML = `<a href='${backUrl}'>返回<i class="fa fa-undo"></i></a>`;
            }
        }
    })

    /**
     * 简单对象的深拷贝
     */
    function clone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    /**
     * 生成query字符串
     * arr_obj  支持数组或对象
     * base 是否使用原格式，如果是则不编码，否则默认进行url编码
     */
    function makeQuery(arr_obj,base){
        let s = ''
        for(x in arr_obj){
            if(!base){
                s += `${x}=${encodeURIComponent(arr_obj[x])}&`
            }else{
                s += `${x}=${arr_obj[x]}&`
            }
        }
        return s.substring(0,s.length-1)
    }

    /**
     * 获取url查询参数
     * base 是否使用原始格式，如果true则保留原格式，否则默认自动解码
     */
    function getQuery(base){
        let query = location.search.substring(1)
        let key_values = query.split("&")
        let obj = {}
        key_values.forEach(key_val => {
            let key_val_split = key_val.split("=")
            let obj_value;
            if(!base){
                obj_value = decodeURIComponent(key_val_split[1])
            }else{
                obj_value = key_val_split[1]
            }
            if(obj_value){  // 不为空时，尝试自动转number
                let number_value = Number(obj_value)
                if(!isNaN(number_value)){
                    obj_value = number_value
                }
            }
            obj[key_val_split[0]] = obj_value
        });
        return obj
    }

    /**
     * 分页组件
     * @param pageInfo
     */
    function navigationPage(pageInfo){
        if(!pageInfo.page){
            return;
        }
        /*
        * 已知pageInfo存在page,pageSize,totalPage,totalCount
        * 分页组件id为 navigation-page
        * */
        let hasNextPage = pageInfo.totalPage>pageInfo.page;
        let nextPage = pageInfo.page+1;
        let prePage = pageInfo.page-1;
        let hasPrePage = pageInfo.page>1;
        let isFirstPage = pageInfo.page===1;
        let isLastPage = pageInfo.page===pageInfo.totalPage;
        // 1.清空分页
        let nav_page = $("#navigation-page")
        nav_page.empty()
        let str = ""
        let base_url = location.pathname
        let query = getQuery();
        query['page'] = query['page'] || pageInfo.page;
        query['pageSize'] = query['pageSize'] || pageInfo.pageSize;
        let is_mobile = isMobile();  //是否移动端？
        // 2.是否展示分页
        if(isFirstPage && !hasNextPage){
            return ;  // 第一页，且没有下一页，不显示分页
        }
        // 3.是否有前一页
        if(hasPrePage){
            let preQuery = clone(query)
            preQuery['page'] = prePage
            let pre_str = "&laquo;";
            if(!is_mobile){
                pre_str = pre_str + "上一页";
            }
            str += `<li><a href="${base_url}?${makeQuery(preQuery)}">${pre_str}</a></li>`
        }
        // 4.中间最多展示10分页，此数据可修改
        let nav_nums = 5;
        if(!is_mobile){
            nav_nums = 7;  // PC 默认显示7个
        }
        nav_nums -= 1; //有一个页面是page本身
        // 4.1计算起始下标，前面起始标号一般是占一半页数，如果后面不足（一半-1）页，可考虑动态加上其剩余页值
        let before_num = Math.floor(nav_nums/2); //正常来说，前面应该分配几页？
        //如果分配数量大于实际可展示数量，收回
        if(pageInfo.page <= before_num+1){
            before_num -= Math.abs(pageInfo.page - (before_num+1));
        }
        let after_num = nav_nums - before_num; //正常来说后面有几页？
        //如果后续分配数量，大于实际可展示数量，丢弃
        if(after_num > pageInfo.totalPage - pageInfo.page){
            after_num = pageInfo.totalPage - pageInfo.page;
        }
        //如果总数加起来不足，说明肯定是后面不足丢弃了部分
        if(before_num+after_num < nav_nums){
            //判断最多丢弃了多少？
            let draft = nav_nums - before_num - after_num;
            //判断前面是否还能再增加？最多能增加多少？
            let beforeAdd = pageInfo.page - before_num;  // 最多能增加的页数
            before_num += beforeAdd > draft ? draft : beforeAdd;  //取两者的最小值
        }
        //如果前面有值，前面最多分成两半
        if(before_num>0){
            //判断前面的页数，如果 page 小于等于 before_num+1，全部显示
            if(pageInfo.page <= before_num+1){
                for (let i = 1; i < pageInfo.page ; i++) {
                    let query_i = clone(query);
                    query_i['page'] = i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${i}</a></li>`
                }
            }
            //前面必须分为两半来显示
            else{
                let bbefore_num = Math.floor(before_num/2); //左左可有几个？ //从1开始
                let bafter_num = before_num - bbefore_num;  //左右可有几个？
                for(let i=1;i<=bbefore_num;i++){
                    let query_i = clone(query);
                    query_i['page'] = i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${i}</a></li>`
                }
                str += `<li class="disabled"><a>...</a></li>`;
                for(let i=bafter_num;i>0;i--){
                    let query_i = clone(query);
                    query_i['page'] = query_i['page']-i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${query_i['page']}</a></li>`
                }
            }
        }
        //当前页
        str += `<li class="active"><a href="${base_url}?${makeQuery(query)}">${query['page']}</a></li>`
        //后续... （新：分为两半）
        //如果后面有数据
        if(after_num>0){
    
            //如果后面的页数，可以直接全部显示（前面计算过 after_num 兼容至最大页面，如果==则说明可以全部显示）
            if(after_num == pageInfo.totalPage - pageInfo.page){
                for(let i=pageInfo.page+1 ; i <= pageInfo.totalPage ;i++){
                    let query_i = clone(query);
                    query_i['page'] = i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${i}</a></li>`
                }
            }
            //必须分为两半
            else{
                let abefore_num = Math.ceil(after_num/2); //右左可有几个？ //从page+1开始
                let aafter_num = after_num - abefore_num;  //右右可有几个？
                for(let i=1; i<=abefore_num; i++){
                    let query_i = clone(query);
                    query_i['page'] = pageInfo.page+i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${query_i['page']}</a></li>`
                }
                str += `<li class="disabled"><a>...</a></li>`;
                for(let i=aafter_num-1; i>=0; i--){
                    let query_i = clone(query);
                    query_i['page'] = pageInfo.totalPage-i;
                    str += `<li><a href="${base_url}?${makeQuery(query_i)}">${query_i['page']}</a></li>`
                }
            }
            
            
        }
        // 5.是否有下一页
        if(hasNextPage){
            let nextQuery = clone(query);
            nextQuery['page'] = nextPage
            let next_str = "&raquo;";
            if(!is_mobile){
                next_str = "下一页"+next_str;
            }
            str += `<li><a href="${base_url}?${makeQuery(nextQuery)}">${next_str}</a></li>`
        }
        // 6.添加到dom中
        nav_page.append(str)
    }