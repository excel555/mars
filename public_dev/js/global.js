/**
 * 封装异步请求
 * 包含通用的四大类请求
 * paging
 * detail
 * submit
 * custom
 */
(function() {
    var NODATA = '<div class="nodata">暂无数据。</div>',
        NOMOREDATA = '<div class="nodata">没有更多数据。</div>',
        SYSTEMERROR = '<div class="nodata">服务器异常。</div>',
        DATAERROR = '<div class="nodata">数据错误。</div>',
        csrftoken,
        loadingDom = $('#rby-loading');

    function general_sign(data,token){
        var query = '';
        var arr = new Array();
        for(var i in data){
            data[i] = data[i] === undefined ? '' : data[i];
            arr.push(i);
        }
        var data_sort = arr.sort();
        for(i=0;i<data_sort.length;i++){
            query += data_sort[i]+'='+data[data_sort[i]]+'&';
        }
        var platform = config.platformKey;
        var tmp = md5(query+platform);
        return md5(tmp.substring(0,tmp.length-1)+"q");
    }
    /**
     * 接口基类
     */
    function Api(options) {
        this.options = options || {};
        this.timeout = 15000; //请求超时时间
        this.cache = true; //是否缓存
        this.defaultListTmpl = 'rby-list-tmpl';
        this.defaultListEle = '#rby-list';
        this.defaultDetailTmpl = 'rby-detail-tmpl';
        this.defaultDetailEle = '#rby-detail';
        this.isLoading = false; //是否正在请求
        this.hasNext = true; //是否有下一页
        this.queue = {}; //请求队列
        this.tempPage = {}; //分页dom
        this.onEnd = function() {}; //当请求都完成
    }

    Api.prototype._init = function() {
        var spinnings = this.spinnings;

        return this;
    }

    /**
     * 分页查询，获取列表类型数据，自动绑定分页，当数据为空时提示无数据，当接口异常或解析错误提示服务器异常
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * renderFor 渲染模板
     * renderEle 渲染容器
     * showLoading 是否显示loading提示 true false
     * *****
     * pagingDom 分页容器
     * pagingMode 分页形式 'number'、'next'、'' 默认 number
     * key 分页数据的关键字 默认'body' '-1'时整个返回值为分页数据
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.paging = function(options, callback, callbackError) {
        var that = this,
            isFirst = options.data.page == 1, //是否第一次请求
            opt = { //默认配置
                renderFor: this.defaultListTmpl,
                renderEle: this.defaultListEle,
                pagingDom: '.pagination',
                pagingMode: 'next',
                timeKey: 'createAt',
                key: 'body',
                showLoading: true,
                logtype: 'paging'
            };

        extend(options, opt);

        if (options.pagingMode == 'number') {
            $(options.renderEle).html('正在加载中...');
            $(options.pagingDom).hide();
        } else if (options.pagingMode == 'next') {
            var np = findByKey(that.tempPage, options.url);
            var next = $('#np-' + np),
                nextStr = '<div id="np-' + np + '" class="nextpage">正在加载中...</div>';

            if (next.length == 0) {
                $(options.renderEle).after(nextStr);
                next = $('#np-' + np);
            }
            next.html('正在加载中...').addClass('disabled');

            if (isFirst) {
                //查第一页数据一定清空当前容器
                $(options.renderEle).html('');
            }
        }

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            var body = response;
            //var body = response[options.key];

            if (options.key == '-1') {
                //设置key=-1，所有返回值为分页数据
                body = response;
            }

            if (!that.isSusPagingData(body)) {
                $(options.renderEle).html(DATAERROR);
                return;
            }

            if (options.pagingMode == 'number') {
                if (!body || body.length == 0) {
                    //数据没有结果显示无数据提示
                    if (isFirst) {
                        $(options.renderEle).html(NODATA);
                    }
                } else {
                    that.render(options.renderEle, options.renderFor, body);
                }

                initPagination(response.pageInfo, options.pagingDom);
            } else if (options.pagingMode == 'next') {
                if (body.length == 0) {
                    //数据没有结果显示无数据提示
                    if (isFirst) {
                        next.hide();
                        $(options.renderEle).html(NODATA);
                    }
                } else {
                    that.hasNext = body.length == options.data.page_size;
                    next.show();
                    that.render(options.renderEle, options.renderFor, body, !isFirst);
                    if (!that.hasNext) {
                        //没有下一页显示无更多数据提示
                        next.html(NOMOREDATA);
                    } else {
                        next.html('正在加载更多').removeClass('disabled');
                        // options.nextButton && next.html(options.nextButton.text || '加载更多');
                    }
                }
            }

            if (typeof callback == 'function') {
                callback(response);
            }
        }, function(textStatus, data) {
            $(options.renderEle).html(SYSTEMERROR);
            next.hide();
            if (typeof callbackError == 'function') {
                callbackError(textStatus, data);
            }
        });
        //异步 分页导航 模板渲染 绑定分页事件 = 分页
    };

    /**
     * 详情查询
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * renderFor 渲染模板
     * renderEle 渲染容器
     * showLoading 是否显示loading提示 true false
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.detail = function(options, callback, callbackError) {
        var that = this,
            opt = { //默认配置
                renderFor: this.defaultDetailTmpl,
                renderEle: this.defaultDetailEle,
                key: '',
                showLoading: true,
                logtype: 'detail'
            };

        extend(options, opt);

        if (options.showLoading) {
            $(options.renderEle).html('<div class="loading">加载中...</div>');
        }

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            if (response.error) {
                $(options.renderEle).html(response.error);
                return;
            }
            var data = response || {};
            if (data) {
                render(options.renderEle, options.renderFor, data);
            }
            if (typeof callback == 'function') {
                callback(response);
            }
        }, callbackError);
    };

    /**
     * 表单提交
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * showLoading 是否显示loading提示 true false
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.submit = function(options, callback, callbackError) {
        var formData,
            that = this,
            isForm = !!options.data.length,
            btnSubmit,
            opt = {
                type: 'POST',
                showLoading: true,
                logtype: 'submit'
            };

        extend(options, opt);

        if (isForm) {
            formData = options.data.serializeArray();
            btnSubmit = options.data.find('[type="submit"]');
            btnSubmit.attr('disabled', true);
        } else {
            formData = options.data;
        }
        options.data = formData;

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            if (isForm) {
                btnSubmit.removeAttr('disabled');
            }
            if (typeof callback == 'function') {
                callback(response);
            }
        }, function(jqXHR, textStatus, errorThrown) {
            if (isForm) {
                btnSubmit.removeAttr('disabled');
            }
            if (typeof callbackError == 'function') {
                callbackError(jqXHR, textStatus, errorThrown);
            }
        });
    };

    /**
     * 自定义查询
     *
     * @param options-封装请求url，请求数据，请求类型
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.custom = function(options, callback, callbackError) {
        var that = this,
            opt = {
                logtype: 'custom'
            };

        extend(options, opt);

        that.ajaxSend(options, callback, callbackError);
    };

    /**
     * jquery.ajax
     */
    Api.prototype.ajaxSend = function(options, callback, callbackError) {
        var that = this;
        that.isLoading = true;
        that.queue[options.url] = true;
        //Tools.alert("token: " + token);
        if (options.showLoading) {
            // $(options.renderEle).hide();
            loadingDom.show();
        }

        //TODO 一般这里需要添加不同的请求参数
        options = options || {};

        if (typeof options.contentType == undefined) {
            options.contentType = 'application/json'
        }
        if (typeof options.processData == undefined) {
            options.processData = true;
        }
        $.ajax({
            url: options.url,
            data: options.data,
            type: options.type || 'GET',
            beforeSend: function(request) {
                request.setRequestHeader("token", Cookie.get("token"));
                request.setRequestHeader("platform", 'wap');
                request.setRequestHeader("sign", general_sign(options.data));
            },
            dataType: 'json',
            timeout: that.timeout,
            cache: that.cache,
            contentType: options.contentType,
            processData: options.processData,
            success: function(response, textStatus, jqXHR) {
                Tools.alert("success data:" + JSON.stringify(response).substring(0, 200));
                that.isLoading = false;
                delete(that.queue[options.url]);

                if (typeof callback == 'function') {
                    callback(response);
                }
                if (isEmpety(that.queue) && typeof that.onEnd == 'function') {
                    that.onEnd.call(this);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Tools.alert("error data: " + JSON.stringify(jqXHR.response));
                that.isLoading = false;
                delete(that.queue[options.url]);

                var obj;
                if(jqXHR.responseText){
                    obj = JSON.parse(jqXHR.responseText);
                }
                if (jqXHR.status == 401 || jqXHR.status == '401') {
                    //若接口提示未登录，自动登录
                    common.login();
                    return;
                }


                logged(options.logtype, textStatus, options.url);
                if (typeof callbackError == 'function') {
                    //callbackError(textStatus, {});
                    callbackError(obj, {});
                }

                if (isEmpety(that.queue) && typeof that.onEnd == 'function') {
                    that.onEnd.call(this);
                }
            },
            complete: function(xhr, status) {
                setTimeout(function() {
                    loadingDom.hide();
                }, 100)
                $(options.renderEle).show();
            }
        });
    }

    /**
     * 数据渲染到模板
     * @param renderEle-渲染容器
     * @param renderFor-渲染模版
     * @param data-数据
     * @param isAppend-是否追加
     */
    function render(renderEle, renderFor, data, isAppend) {
        if ($('#' + renderFor).length > 0 && data) {
            if (typeof data.length != 'undefined') {
                data = {
                    'list': data
                };
            }
            var result = tmpl(renderFor, data);
            if (isAppend) {
                $(renderEle).append(result);
            } else {
                $(renderEle).html(result);
            }
        }
    }

    /**
     * 使用模板
     * @param renderFor 模板名称
     * @data 数据
     */
    function tmpl(renderFor, data) {
        return template.render(renderFor, data);
    }

    /**
     * 记录接口的错误日志
     * @param type-接口请求类型
     * @param message-错误内容
     * @param url-错误地址
     */
    function logged(type, message, url) {
        log('[' + type + '] ' + message + ':' + url, 2);
    }

    /**
     * 判断对象是否为空
     * @param  {[type]}
     * @return {Boolean}
     */
    function isEmpety(obj) {
        var flag = true;
        for (var i in obj) {
            flag = false;
            break;
        }

        return flag;
    }

    /**
     * 验证key是否存在obj中
     * @param  obj 要验证的对象
     * @param  key 要验证的关键字
     */
    function findByKey(obj, key) {
        var arr = [],
            tar;
        for (var i in obj) {
            arr.push(obj[i]);
            if (key == i) {
                tar = obj[i];
            }
        }

        if (arr.length == 0) return obj[key] = 1;
        if (tar) return tar;
        arr = arr.sort();
        return obj[key] = arr[arr.length - 1] + 1;
    }

    /**
     * 初始化数字分页
     * @param  data 分页数据
     * current 当前页
     * size 每页条数
     * count 总记录数
     * @param  dom 分页的容器
     */
    function initPagination(data, dom) {
        if (!data) return; //数据错误不初始化

        var d = {
            current_page: data.current,
            per_page: data.size,
            total: data.count
        };

        d.current_page = parseInt(d.current_page);
        d.total = parseInt(d.total);
        d.per_page = parseInt(d.per_page);
        d.total = Math.ceil(d.total / d.per_page);

        d.prev_page = d.current_page == 1 ? 1 : d.current_page - 1;
        d.next_page = d.current_page == d.total ? d.current_page : d.current_page + 1;
        var start = d.current_page - 2,
            end = d.current_page + 2;

        if (d.total <= 5) {
            start = 1;
            end = d.total;
        } else {
            if (start < 1) {
                start = 1;
                end = start + 4;
            }
            if (end > d.total) {
                end = d.total;
                start = d.total - 4;
            }
        }

        var result = '';

        result += '<dl><dt' + (d.prev_page == 1 ? ' class="disabled"' : '') + '><a href="#' + d.prev_page + '"><img src="images/arrow_left.gif"></a></dt><dd>';
        for (var i = start; i <= end; i++) {
            result += '<a href="#' + i + '"' + (d.current_page == i ? ' class="active"' : '') + '>' + i + '</a>';
        }
        result += '</dd><dt class="ari' + (d.next_page >= d.total ? ' disabled' : '') + '"><a href="#' + d.next_page + '"><img src="images/arrow_left.gif"></a></dt></dl>';

        $(dom).html(result).show();
    }

    /**
     * 扩展参数
     * @param  options 被扩展参数
     * @param  opt 扩展参数
     */
    function extend(options, opt) {
        options = options || {};
        for (var i in opt) {
            options[i] = typeof options[i] == 'undefined' ? opt[i] : options[i];
        }
    }

    /**
     * 是否正确的分页数据
     * @param  data 分页数据
     * @return {Boolean}
     */
    function isSusPagingData(data) {
        return typeof data == 'object' && typeof data.length != undefined;
    }

    //抛出公用方法，保持模板调用入口唯一
    Api.prototype.render = render;
    Api.prototype.logged = logged;
    Api.prototype.isSusPagingData = isSusPagingData;

    window.Ajax = new Api();
})();; //debug
var log = function(m) {
    if (typeof console != 'undefined') {
        console.log(m);
    }
};

/**
 * 本地cookie读写
 */
(function() {
    var Cookie = {
        get: function(sname) {
            var sre = "(?:;)?" + sname + "=([^;]*);?";
            var ore = new RegExp(sre);
            if (ore.test(document.cookie)) {
                try {
                    return unescape(RegExp["$1"]); // decodeURIComponent(RegExp["$1"]);
                } catch (e) {
                    return null;
                }
            } else {
                return null;
            }
        },

        /**
         * 设置Cookie
         * @param {[String]}
         * @param {[String]}
         * @param {[Number]} 天数
         * @param {[Number]} 小时数
         * @param {[Number]} 分钟数
         * @param {[Number]} 秒数
         */
        _set: function(c_name, value, days, hours, minutes, seconds) {
            var expires = null;
            if (typeof days == 'number' && typeof hours == 'number' && typeof minutes == 'number' && typeof seconds == 'number') {
                if (days == 0 && hours == 0 && minutes == 0 && seconds == 0) {
                    expires = null;
                } else {
                    expires = this.getExpDate(days, hours, minutes, seconds);
                }
            } else {
                expires = days || this.getExpDate(7, 0, 0, 0);
            }
            document.cookie = c_name + "=" + escape(value) + ((expires == null) ? "" : ";expires=" + expires) + "; path=/";
        },

        /**
         * 设置Cookie
         * @param {[type]} name
         * @param {[type]} value
         * @param {[type]} 天数，默认7天、0不设置、-1移除
         */
        set: function(c_name, value, days) {
            if(null == days){
                this._set(c_name, value, 7, 0, 0, 0);
            }else{
                this._set(c_name, value, days, 0, 0, 0);
            }
        },

        remove: function(key) {
            this.set(key, '', -1);
        },
        //获取过期时间，d天数、h小时、m分钟、s秒
        getExpDate: function(d, h, m, s) {
            var r = new Date;
            if (typeof d == "number" && typeof h == "number" && typeof m == "number" && typeof s == 'number')
                return r.setDate(r.getDate() + parseInt(d)), r.setHours(r
                        .getHours() + parseInt(h)), r.setMinutes(r.getMinutes() + parseInt(m)), r.setSeconds(r.getSeconds() + parseInt(s)),
                    r.toGMTString()
        }
    };
    window.Cookie = Cookie;
})();;
/**
 * 自定义验证，用于简单的规格验证
 */
(function() {
    String.prototype.isSpaces = function() {
        for (var i = 0; i < this.length; i += 1) {
            var ch = this.charAt(i);
            if (ch != ' ' && ch != "\n" && ch != "\t" && ch != "\r") {
                return false;
            }
        }
        return true;
    };

    String.prototype.isValidMail = function() {
        return (new RegExp(
            /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/)
            .test(this));
    };

    String.prototype.isPhone = function() {
        return (new RegExp(/^1\d{10}?$/).test(this));
    };

    String.prototype.isEmpty = function() {
        return (/^\s*$/.test(this));
    };

    String.prototype.isValidPwd = function() {
        return (new RegExp(/^([_]|[a-zA-Z0-9@]){6,16}$/).test(this));
    };

    String.prototype.isPostCode = function() {
        return (new RegExp(/^\d{6}?$/).test(this));
    };
})();;
/**
 * 自定义弹出页，依赖jquery
 */
(function(window) {
    var tempPage = 0; //打开页面的计数，
    var SecondPage = function(options) {
        var that = this;

        if (typeof options == 'object') {
            for (var i in options) {
                that[i] = options[i];
            }
        } else if (typeof options == 'string') {
            that.targetPage = $(options);
        }
        that.coverDom = that.coverDom || $('#sidebar-bg');

        //默认点击遮罩层关闭
        that.coverDom.click(function(e) {
            e.preventDefault();
            that.closeSidebar();
        })
    }

    SecondPage.prototype = {
        targetPage: undefined, //当前页面DOM
        coverDom: undefined, //遮罩层
        beforeOpen: function() {}, //打开之前
        afterClose: function() {}, //关闭之后
        openSidebar: function(fn) {
            var container = $(window),
                w = container.width(),
                h = container.height(),
                clientH = this.targetPage.height(),
                that = this;
            that.coverDom.show();
            that.targetPage.show()
                .css({
                    'width': w
                    // 'height': h
                });
            setTimeout(function() {
                that.targetPage.addClass('open');
            }, 100)
            tempPage++;
            if (!$('body').hasClass('move')) {
                $('body').addClass('move')
                    .css({
                        'width': document.documentElement.clientWidth,
                        'height': document.documentElement.clientHeight,
                        'overflow': 'hidden'
                    });
            }
            fn && fn();
            that.beforeOpen && that.beforeOpen();
        },

        closeSidebar: function(fn) {
            var that = this;
            this.targetPage.removeClass('open');
            tempPage--;
            setTimeout(function() {
                that.coverDom.hide();
                that.targetPage.hide();
                hasOpend = false;
                if (tempPage <= 0) {
                    $('body').removeClass('move')
                        .css({
                            'width': 'auto',
                            'height': 'auto',
                            'overflow': 'inherit'
                        });
                }
                fn && fn();
                that.afterClose && that.afterClose();
            }, 220);
        }
    }

    window.SecondPage = SecondPage;
})(window);;
/**
 * 本地存储扩展
 */
(function() {
    var Storage = {
        AUTH: 'FLV-AUTH',
        ACCOUNT: 'FLV-ACCOUNT',
        REMEMBER: 'FLV-REMEMBER',
        LOGIN_HISTORY: 'LH',
        AREA: 'FLV-AREA',
        get: function(key, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            var value = this.getStorage(isSession).getItem(key);
            if (value) {
                return JSON.parse(value);
            } else {
                return undefined;
            }
        },
        set: function(key, value, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            value = JSON.stringify(value);
            this.getStorage(isSession).setItem(key, value);
        },
        remove: function(key, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            this.getStorage(isSession).removeItem(key);
        },
        getStorage: function(isSession) {
            return isSession ? sessionStorage : localStorage;
        },
        isLocalStorage: function() {
            try {
                if (!window.localStorage) {
                    log('不支持本地存储');
                    return false;
                }
                return true;
            } catch (e) {
                log('本地存储已关闭');
                return false;
            }
        }
    };

    window.Storage = Storage;
})();;
/**
 * 扩展模板帮助方法
 * 依赖artTemplate，tools
 */
(function(template) {
    if (!template) return;

    template.openTag = "<!--[";
    template.closeTag = "]-->";

    // 模板帮助方法，绝对化图片地址
    template.helper('$absImg', function(content, defaultValue) {
        return Tools.absImg(content, defaultValue);
    });

    // 模板帮助方法，转换时间戳成字符串
    template.helper('$formatDate', function(content, type, defaultValue) {
        return Tools.formatDate(content, type, defaultValue || '--');
    });

    //模板帮助方法，编码url参数
    template.helper('$encodeUrl', function(content) {
        return encodeURIComponent(content);
    });

    //模板帮助方法，格式化货币
    template.helper('$formatCurrency', function(content, defaultValue, unit) {
        return Tools.formatCurrency(content, defaultValue, unit);
    });

    //模板帮助方法，\r\n替换换行
    template.helper('$convertRN', function(content) {
        if (!content) {
            return '--';
        }
        return content.replace(/\r\n/gi, '<br/>');
    });

    //模板帮助方法，根据序列值添加样式名
    template.helper('$addClassByIdx', function(i, v, className) {
        if (i == v) {
            return className || '';
        }
    });

    //模板帮助方法，截取内容长度添加省略号
    template.helper('$ellipsis', function(content, length) {
        var v = content.replace(/[^\x00-\xff]/g, '__').length;
        if (v / 2 > length) {
            return content.substring(0, length) + '...';
        }
        return content;
    });

    //模板帮助方法， 从时间字符串中截取日期，限定字符串yyyy-MM-dd...
    template.helper('$getDateFromStr', function(content) {
        if (!content || content.length == 0) {
            return;
        }

        var len = content.length > 10 ? 10 : content.length;
        return content.substring(0, len);
    });

    //模板帮助方法，转换价格
    template.helper('$rbyFormatCurrency', function(content) {
        return Tools.rbyFormatCurrency(content);
    });

    //模板帮助方法，根据条件添加样式
    template.helper('$addClassByCondition', function(condition, className, className2) {
        if (condition) {
            return className || '';
        } else {
            return className2 || '';
        }
    });

    //模板帮助方法，获取订单状态值
    template.helper('$getOrderStatus', function(content, type) {
        return config.ORDER_STATUS[content] || '--';
    });

    //模板帮助方法，
    template.helper('$addClassForGoods', function(data, className, className2) {
        if (!data || isNaN(data.limitNum) || isNaN(data.store) || isNaN(data.quantity)) return '';
        data.limitNum = parseInt(data.limitNum);
        data.store = parseInt(data.store);
        data.quantity = parseInt(data.quantity);
        if (data.quantity >= Math.min(data.limitNum, data.store)) {
            return className || '';
        } else {
            return className2 || '';
        }
    });

    // 模板帮助方法，格式化倒计时
    template.helper('$getCountDown', function(data, other) {
        if (typeof data == 'object') {
            return Tools.getRunTime(data.serverTime, data.endTime);
        } else {
            return Tools.getRunTime(data, other);
        }
    });

    // 模板帮助方法，转换微信头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
    template.helper('$absWechatIcon', function(content) {
        if (!content || content.indexOf('http://') != 0) return '../content/images/common/headicon.png';
        //http://wx.qlogo.cn/mmopen/xxx/0
        var arr = content.split('/');
        arr[arr.length - 1] = '96';
        return arr.join('/');
    });
})(window.template);
/**
 * 工具类，包括自定义提示框、格式化日期、格式化货币、获取查询字符串、格式化表单等
 **/
(function() {
    var that = this,
        preventDefault, panel, panelBg, delay, count = 0,
        toastPanel, temp;

    //自定义提示框，依赖jquery
    var TipPanel = function(el, options) {
        var that = this;

        that.panel = el || $('#rby-panel');
        that.panelBg = panelBg || $('#rby-panel-bg');
        that.panelTitle = that.panel.find('.panel-title');
        that.panelTips = that.panel.find('.panel-tips');
        that.btnOk = that.panel.find('.btn-primary');
        that.btnCancel = that.panel.find('.btn-default');
        that.panelText = that.panel.find('.panel-text');
        that.panelTick = that.panel.find('.panel-tick');
        that.panelImg = that.panel.find('.panel-img');

        that.options = {
            type: 'error',
            tick: 0,
            okText: '确定',
            cancelText: '取消',
            showTitle: false,
            showTips: false
        };

        //关闭
        that.panel.on('click', '.btn-primary', function(e) {
            e.preventDefault();
            that.hide(true);
        });

        //取消
        that.panel.on('click', '.btn-default', function(e) {
            e.preventDefault();
            that.hide();
        });
        //提示
        that.panel.on('click', '.panel-tips', function(e) {
            e.preventDefault();
            that.hide(true);
        });

    };

    TipPanel.prototype = {
        delay: undefined,
        count: 0,
        setOptions: function(options) {
            var that = this;

            for (i in options) that.options[i] = options[i];

            if (that.options.showTitle) {
                that.panelTitle.show();
            } else {
                that.panelTitle.hide();
            }
            if (that.options.showTips) {
                that.panelTips.show();
                that.btnOk.hide();
            } else {
                that.panelTips.hide();
            }
            if (that.options.okText) {
                that.btnOk.text(that.options.okText);
            }
            if (that.options.cancelText) {
                that.btnCancel.text(that.options.cancelText);
            }
            if (that.options.tipsText) {
                that.panelTips.html(that.options.tipsText);
            }
            if (that.options.titleText) {
                that.panelTitle.text(that.options.titleText);
            }
            if(that.options.panelImg){
                that.panelImg.attr('src',that.options.panelImg);
            }
            if (that.options.type == 'confirm') {
                that.btnOk.show();
                that.btnCancel.show();
            } else if (that.options.cancelText){
                that.btnCancel.show();
            }else{
                that.btnCancel.hide();
            }
            that.panelText.html(that.options.message);
            that.panel.css('margin-top', -(that.panel.height() / 2)).show();
            that.panelBg.show();

            if (that.options.tick > 1000) {
                that.panelTick.text(that.options.tick / 1000);
                that.delay = setInterval(function() {
                    if (that.count < that.options.tick - 1000) {
                        that.count = count + 1000;
                        that.panelTick.text((that.options.tick - count) / 1000);
                    } else {
                        that._end();
                        that.count = 0;
                        clearInterval(that.delay);
                    }
                }, 1000);
            } else if (that.options.tick <= 1000 && that.options.tick > 0) {
                that.delay = setTimeout(function() {
                    that._end();
                }, that.options.tick);
            }
        },
        _end: function() {
            var that = this;

            that.panel.hide();
            that.panelBg.hide();

            if (typeof that.options.tipsCallback == 'function') {
                that.options.tipsCallback();
                that.options.tipsCallback = undefined;
            } else if (typeof that.options.yesCallback == 'function') {
                that.options.yesCallback();
                that.options.yesCallback = undefined;
            }
            if (typeof that.options.noCallback == 'function') {
                that.options.noCallback();
            }
        },
        show: function() {

        },
        hide: function(yesClick) {
            var that = this;

            if (that.delay) {
                clearTimeout(that.delay);
            }
            if (!that.panel) {
                return;
            }
            that.panel.hide();
            that.panelBg.hide();

            if (yesClick) {
                typeof that.options.yesCallback == 'function' && that.options.yesCallback();
                typeof that.options.tipsCallback == 'function' && that.options.tipsCallback();
            } else {
                typeof that.options.noCallback == 'function' && that.options.noCallback();
            }
            that.options.yesCallback = undefined;
            that.options.noCallback = undefined;
            that.options.tipsCallback = undefined;
        }
    }

    //按指定格式格式化日期
    function format(date, pattern) {
        var that = date;
        var o = {
            "M+": that.getMonth() + 1,
            "d+": that.getDate(),
            "h+": that.getHours(),
            "m+": that.getMinutes(),
            "s+": that.getSeconds(),
            "q+": Math.floor((that.getMonth() + 3) / 3),
            "S": that.getMilliseconds()
        };
        if (/(y+)/.test(pattern)) {
            pattern = pattern.replace(RegExp.$1, (that.getFullYear() + "")
                .substr(4 - RegExp.$1.length));
        }
        for (var k in o) {
            if (new RegExp("(" + k + ")").test(pattern)) {
                pattern = pattern.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));
            }
        }
        return pattern;
    };

    var Tools = {
        //绝对化图片地址
        absImg: function(content, defaultValue) {
            if (!content) {
                // 测试时使用相对
                return defaultValue || config.DEF_IMG_URL;
            }
            if (content && content.indexOf('http') == 0) {
                return content;
            }
            return config.HOST_IMAGE + content;
        },
        //时间戳格式化
        formatDate: function(content, type, defaultValue) {
            var pattern = type || "yyyy-MM-dd hh:mm";
            if (isNaN(content) || content == null) {
                return defaultValue || content;
            } else if (typeof(content) == 'object') {
                var y = dd.getFullYear(),
                    m = dd.getMonth() + 1,
                    d = dd.getDate();
                if (m < 10) {
                    m = '0' + m;
                }
                var yearMonthDay = y + "-" + m + "-" + d;
                var parts = yearMonthDay.match(/(\d+)/g);
                var date = new Date(parts[0], parts[1] - 1, parts[2]);
                return format(date, pattern);
            } else {
                if (content.length == 10)
                    content = content + '000';
                var date = new Date(parseInt(content));
                return format(date, pattern);
            }
        },
        // 货币格式化，2050.5=>2,050.5
        formatCurrency: function(content, defaultValue, unit) {
            if (!content) {
                return defaultValue || '--';
            }

            content = content + ''; //转字符串

            var prefix, subfix, idx = content.indexOf('.');
            if (idx > 0) {
                prefix = content.substring(0, idx);
                subfix = content.substring(idx, content.length);
            } else {
                prefix = content;
                subfix = '';
            }

            var mod = prefix.toString().length % 3;
            var sup = '';
            if (mod == 1) {
                sup = '00';
            } else if (mod == 2) {
                sup = '0';
            }

            prefix = sup + prefix;
            prefix = prefix.replace(/(\d{3})/g, '$1,');
            prefix = prefix.substring(0, prefix.length - 1);
            if (sup.length > 0) {
                prefix = prefix.replace(sup, '');
            }
            if (subfix) {
                if (subfix.length == 2) {
                    subfix += '0';
                } else if (subfix.length == 1) {
                    subfix += '00';
                }
                subfix = subfix.substring(0, 3);
            }
            return prefix + subfix;
        },
        strToDate: function(str) { //字符串转日期，yyyy-MM-dd hh:mm:ss
            var tempStrs = str.split(" ");
            var dateStrs = tempStrs[0].split("-");
            var year = parseInt(dateStrs[0], 10);
            var month = parseInt(dateStrs[1], 10) - 1;
            var day = parseInt(dateStrs[2], 10);

            var timeStrs = tempStrs[1].split(":");
            var hour = parseInt(timeStrs[0], 10);
            var minute = parseInt(timeStrs[1], 10) - 1;
            var second = parseInt(timeStrs[2], 10);
            var date = new Date(year, month, day, hour, minute, second);
            return date;
        },
        // 倒计时  9527
        getRunTime2: function(systemTime, endTime) {
            if (!systemTime || isNaN(systemTime) || !endTime || isNaN(endTime)) {
                return '数据错误';
            }
            var showTime = parseInt(endTime) - parseInt(systemTime);
            if (showTime <= 0) {
                return '已结束';
            }
            var nD = Math.floor(showTime / (60 * 60 * 24));
            var nH = Math.floor(showTime / (60 * 60)) % 24;
            var nM = Math.floor(showTime / 60) % 60;
            var nS = Math.floor(showTime) % 60;

            return nD + '天' + Tools.checkTime(nH) + '小时' + Tools.checkTime(nM) + '分钟' + Tools.checkTime(nS) + '秒';
        },
        getRunTime: function(systemTime, endTime) {
            if (!systemTime || isNaN(systemTime) || !endTime || isNaN(endTime)) {
                return '数据错误';
            }
            var showTime = parseInt(endTime) - parseInt(systemTime);
            if (showTime <= 0) {
                return '已结束';
                // showTime = 0;
            }
            var nD = Math.floor(showTime / (60 * 60 * 24));
            var nH = Math.floor(showTime / (60 * 60)) % 24;
            var nM = Math.floor(showTime / 60) % 60;
            var nS = Math.floor(showTime) % 60;

            return '剩余 <span><em>' + Tools.checkTime(nD) + '</em>天<em>' + Tools.checkTime(nH) + '</em>时<em>' + Tools.checkTime(nM) + '</em>分<em>' + Tools.checkTime(nS) + '</em>秒</span> 结束';
        },
        checkTime: function(i) { //时分秒为个位，用0补齐
            if (i < 10) {
                i = "0" + i;
            }
            return i;
        },

        //获取URL参数
        getQueryValue: function(key) {
            var q = location.search,
                keyValuePairs = new Array();

            if (q.length > 1) {
                var idx = q.indexOf('?');
                q = q.substring(idx + 1, q.length);
            } else {
                q = null;
            }

            if (q) {
                for (var i = 0; i < q.split("&").length; i++) {
                    keyValuePairs[i] = q.split("&")[i];
                }
            }

            for (var j = 0; j < keyValuePairs.length; j++) {
                if (keyValuePairs[j].split("=")[0] == key) {
                    // 这里需要解码，url传递中文时location.href获取的是编码后的值
                    // 但FireFox下的url编码有问题
                    return decodeURI(keyValuePairs[j].split("=")[1]);

                }
            }
            return '';
        },
        // 获取窗口尺寸，包括滚动条
        getWindow: function() {
            return {
                width: window.innerWidth,
                height: window.innerHeight
            };
        },
        // 获取文档尺寸，不包括滚动条但是高度是文档的高度
        getDocument: function() {
            var doc = document.documentElement || document.body;
            return {
                width: doc.clientWidth,
                height: doc.clientHeight
            };
        },
        // 获取屏幕尺寸
        getScreen: function() {
            return {
                width: screen.width,
                height: screen.height
            };
        },
        // 显示、禁用滚动条
        showOrHideScrollBar: function(isShow) {
            preventDefault = preventDefault || function(e) {
                    e.preventDefault();
                };
            (document.documentElement || document.body).style.overflow = isShow ? 'auto' : 'hidden';
            // 手机浏览器中滚动条禁用取消默认touchmove事件
            if (isShow) {
                // 注意这里remove的事件必须和add的是同一个
                document.removeEventListener('touchmove', preventDefault, false);
            } else {
                document.addEventListener('touchmove', preventDefault, false);
            }
        },
        // 显示对话框
        showDialog: function() {},
        // 显示着遮罩层
        showOverlay: function() {},
        // 显示确认框
        showConfirm: function(msg, yesCallback, noCallback) {
            var opt = {};
            if (typeof msg == 'object') {
                opt = msg;
            } else {
                opt.message = msg;
                opt.yesCallback = yesCallback;
                opt.noCallback = noCallback;
            }
            opt.type = 'confirm';
            opt.showTitle = true;
            opt.showTip = false;
            opt.titleText = '提示';

            panel = panel || new TipPanel();
            panel.setOptions(opt);
        },
        // 显示提示
        showAlert: function(msg, tick, callback) {
            var opt = {};
            if (typeof msg == 'object') {
                opt = msg;
            } else {
                opt = {
                    showTips:true,
                    tipsText:"我知道了",
                    showTitle:true,
                    message: msg,
                    titleText:"提示",
                    cancelText:false,
                    panelImg:'img/tip.png',
                    tipsCallback:callback,
                    tick :tick
                }
            }
            // opt.type = 'alert';
            panel = panel || new TipPanel();
            panel.setOptions(opt);
        },
        // 显示加载框
        showLoading: function() {
            $('#rby-loading').show();
        },
        hideLoading: function() {
            $('#rby-loading').hide();
        },
        hidePanel: function(yesClick) {
            panel && panel.hide(yesClick);
        },
        showToast: function(msg, tick) {
            toastPanel = toastPanel || $('#rby-toast');
            tick = tick || 1000;

            if (delay) {
                clearTimeout(delay);
            }

            toastPanel.find('span').text(msg);
            toastPanel.show();
            delay = setTimeout(function() {
                toastPanel.hide();
            }, tick);
        },
        isIPad: function() {
            return (/iPad/gi).test(navigator.appVersion);
        },
        isIos: function() {
            return (/iphone|iPad/gi).test(navigator.appVersion);
        },
        isAndroid: function() {
            return (/android/gi).test(navigator.appVersion);
        },
        isWeChatBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "micromessenger" == e.match(/MicroMessenger/i) ? !0 : !1
        },
        isRbyAppBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "rbyapp" == e.match(/rbyapp/i) ? !0 : !1
        },
        isAlipayBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "alipay" == e.match(/Alipay/i) ? !0 : !1
        },
        isFDBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "fd" == e.match(/FD/i) ? !0 : !1
        },
        returnUserAgent:function () {
            if(this.isAlipayBrowser()) {
                return "alipay";
            } else if(this.isWeChatBrowser()){
                return "wechat";
            } else if(this.isFDBrowser()) {
                return "fruitday-app";
            }else if(this.isGatApp()){
                return 'gat';
            }else if(this.isCmbApp()){
                return 'cmb';
            }else{
                return "fruitday-web";
            }
        },
        isFruitdayAppBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_iPhone" == e.match(/FD_iPhone/i) || "FD_Android" == e.match(/FD_Android/i)) ? !0 : !1
        },
        isFruitdayAndroidBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_Android" == e.match(/FD_Android/i)) ? !0 : !1
        },
        isFruitdayiOSBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_iPhone" == e.match(/FD_iPhone/i)) ? !0 : !1
        },
        isGatApp: function() {
            var e = navigator.userAgent;
            return ("GatApp" == e.match(/GatApp/i)) ? !0 : !1
        },
        isCmbApp: function() {
            var e = navigator.userAgent;
            return ("MPBank" == e.match(/MPBank/i)) ? !0 : !1
        },
        // 将form中的值转换为键值对
        formJson: function(form) {
            var o = {};
            var a = $(form).serializeArray();
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        },
        alert: function(e) {
            if(Cookie.get("DevDebug") == 1){
                alert(e);
            }else{
                console.log(e);
            }
        },
        _GET: function() {
            var e = location.search,
                o = {};
            if ("" === e || void 0 === e) return o;
            e = e.substr(1).split("&");
            for (var n in e) {
                var t = e[n].split("=");
                o[t[0]] = t[1]
            }
            return o.from && delete o.code, o
        },
        removeParamFromUrl: function(e) {
            var o = Tools._GET();
            for (var n in e) delete o[e[n]];
            return location.pathname + Tools.buildUrlParamString(o)
        },
        buildUrlParamString: function(e) {
            var o = "";
            for (var n in e) o += n + "=" + e[n] + "&";
            o = o.slice(0, o.length - 1);
            var t = "" === o || void 0 === o;
            return t ? "" : "?" + o
        },
        //格式化价格，显示两位小数，当两位小数都为0是省略
        rbyFormatCurrency: function(content) {
            if (!content || isNaN(content)) return content;

            var v = parseFloat(content),
                result = v.toFixed(2);
            if (result.indexOf('.00') >= 0) {
                result = parseFloat(content).toFixed(0);
            }
            return result;
        }
    };

    window.Tools = Tools;
})();
