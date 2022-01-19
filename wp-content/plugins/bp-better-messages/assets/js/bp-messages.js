/* global BP_Messages */
(function ($) {
    var checkerTimer, // Timer variable
        thread,  // Current thread_id or false
        socket,
        threads, // True if we are on thread list screen or false
        preventSound,
        openThreads = {},
        miniChats = {},
        miniMessages = false,
        bpMessagesWrap,
        online = [],
        loadingMore = {},
        unread = {},
        reIniting = false,
        blockScroll = false,
        isRtl = $('html[dir="rtl"]').length !== 0,
        RTCPeerConnection = window.RTCPeerConnection || window.webkitRTCPeerConnection,
        RTCSessionDescription = window.mozRTCSessionDescription || window.RTCSessionDescription,
        isInCall = false,
        loadingHtml = '<div class="loading-messages"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';

    ifvisible.setIdleDuration(3);


    $.fn.BPBMremoveAttributes = function() {
        return this.each(function() {
            var attributes = $.map(this.attributes, function(item) {
                return item.name;
            });
            var img = $(this);
            $.each(attributes, function(i, item) {
                img.removeAttr(item);
            });
        });
    }

    function BPBMformatTextArea(textarea){
        if ( typeof textarea[0].BPemojioneArea !== 'undefined' ){
            textarea[0].BPemojioneArea.trigger('change');
        }

        var message = textarea.val();

        if( $(textarea).next('.bp-emojionearea').length === 0 ) return message;

        var new_html = BPBMformatMessage(message);

        textarea.val(new_html);

        return new_html;
    }

    function BPBMformatMessage(message){
        message = message.replace(/<p><\/p>/g, '');

        if( message.substring(0, 3) !== '<p>' ) {
            message = '<p>' + message;
        }

        if( message.substring(message.length - 4) !== '</p>' ) {
            message = message + '</p>';
        }

        var message_html = $.parseHTML( message );

        $.each( message_html, function( i, el ) {
            var element = $(this);

            $.each(element.find('img.emojioneemoji,img.emojione'), function () {
                var emojiicon = $(this);
                emojiicon.replaceWith(emojiicon.attr('alt'));
            });

            element.BPBMremoveAttributes();
            element.find('*').BPBMremoveAttributes();

        });

        var new_html = '';
        $.each( message_html, function(){
            new_html += this.outerHTML;
        } );

        if(new_html === '<p></p>') new_html = '';

        return new_html;
    }

    $(window).on('focus',function() {
        ifvisible.focus();
    });

    $(window).on('blur', function() {
        ifvisible.blur();
    });

    var isMobile = false; //initiate as false
    if( /iPad|iPhone|iPod/.test(navigator.userAgent)
        || /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
        || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;

    if( BP_Messages['mobileFullScreen'] === '0' ) isMobile = false;

    if( BP_Messages['forceMobile'] === '1' ) isMobile = true;

    $(document).ready(function () {
        isRtl = $('html[dir="rtl"]').length !== 0;
        bpMessagesWrap          = $(".bp-messages-wrap:not(.bp-better-messages-list, #bp-better-messages-mini-mobile-open)");
        var miniMobileContainer = $('#bp-better-messages-mini-mobile-container');
        var mobileViewContainer = '#bp-better-messages-mobile-view-container';
        var mobileOpenButton    = $('#bp-better-messages-mini-mobile-open');
        var holderClass         = 'bp-better-messages-mobile-holder';

        ion.sound({
            sounds: [
                {name: "calling"},
                {name: "notification"},
            ],

            // main config
            path: BP_Messages.assets,
            preload: true,
            multiplay: true,
            volume: 0.75
        });

        function openMobile( wrap ){
            if( $('.' + holderClass ).length === 0 ){
                $('<div class="bp-better-messages-mobile-holder"></div>').insertBefore(wrap);
            }

            var source = wrap;
            source.addClass('bp-messages-mobile');
            source.attr('id', 'bp-better-messages-mobile-view-container');

            var windowHeight = window.innerHeight;
            $('html').addClass('bp-messages-mobile').css('overflow', 'hidden');
            $('body').addClass('bp-messages-mobile').css('min-height', windowHeight);
            //bpMessagesWrap.addClass('bp-messages-mobile').css('min-height', windowHeight);

            var _mobileViewContainer = source.appendTo( $('body') );
            _mobileViewContainer.show();

            var usedHeight = 0;
            usedHeight = usedHeight + _mobileViewContainer.find('.chat-header').outerHeight();

            if( _mobileViewContainer.find('.reply').length > 0 ) {
                usedHeight = usedHeight + _mobileViewContainer.find('.reply').outerHeight();
            }

            var resultHeight = windowHeight - usedHeight;


            _mobileViewContainer.find('.scroller').css({
                'max-height': '',
                'height': resultHeight
            });

            calculateTitle(_mobileViewContainer);
            scrollBottom();

            hidePossibleBreakingElements();

            blockScroll = true;
        }



        if(isMobile){
            var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

            $(document).on('touchmove', '.bp-messages-wrap .chat-header,.bp-messages-wrap .reply', function(event) {
                if(blockScroll){
                    event.preventDefault();
                }
            });

            $(document).on('focus blur', '.bp-messages-wrap textarea, .bp-messages-wrap input', function( event ){
                setTimeout(function(){
                    mobileResize();
                    scrollBottom();
                }, 300);
            });

            $( window ).resize(function() {
                if( bpMessagesWrap.hasClass('bp-messages-mobile') ){
                    mobileResize();
                }
            });

            function mobileResize(){
                if( iOS ) {
                    return false;
                }

                var windowHeight = window.innerHeight;
                var usedHeight = 0;
                usedHeight = usedHeight + bpMessagesWrap.find('.chat-header').outerHeight();
                usedHeight = usedHeight + bpMessagesWrap.find('.reply').outerHeight();

                $('.scroller').css({
                    'max-height': '',
                    'height' : windowHeight - usedHeight
                });
            }

            bpMessagesWrap.addClass('mobile-ready');

            var touchmoved;
            var blockOpen = false;

            if( BP_Messages['disableTapToOpen'] === '0' ){
                bpMessagesWrap.find('.bp-messages-mobile-tap').css('line-height', bpMessagesWrap.height() + 'px');

                bpMessagesWrap.on('touchend', '.bp-messages-mobile-tap', function(event){

                    if(touchmoved != true && blockOpen === false){
                        var wrap = jQuery(event.target).closest('.bp-messages-wrap');

                        if( ! wrap.hasClass('bp-messages-mobile') ){
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            openMobile( wrap );
                        }
                    }
                }).on('touchmove', function(e){
                    touchmoved = true;
                }).on('touchstart', function(e){
                    touchmoved = false;
                });
            } else {
                bpMessagesWrap.on('touchend', function(event){
                    if(touchmoved != true && blockOpen === false){
                        var wrap = jQuery(event.target).closest('.bp-messages-wrap');
                        if( ! wrap.hasClass('bp-messages-mobile') ){
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            openMobile( wrap );

                            var clickedElement = $(event.originalEvent.target);
                            var threadClicked = clickedElement.closest('.thread');

                            if( threadClicked.length > 0 ){
                                threadClicked.click();
                            }

                        }
                    }
                }).on('touchmove', function(e){
                    touchmoved = true;
                }).on('touchstart', function(e){
                    touchmoved = false;
                });
            }
        }

        $(document).on('click', '.bp-messages-wrap .mobileClose', function(event){
            var wrap = jQuery(event.target).closest('.bp-messages-wrap');
            if( wrap.hasClass('bp-messages-mobile') ){
                event.preventDefault();
                event.stopImmediatePropagation();

                blockOpen = true;
                $('html').removeClass('bp-messages-mobile').css('overflow', 'auto');
                wrap.removeClass('bp-messages-mobile').css('min-height', '');
                $('body').removeClass('bp-messages-mobile').css('min-height', '');

                var height = $( window ).height() - 250;
                if(height > BP_Messages['max_height']) height = BP_Messages['max_height'];

                $('.scroller').css({
                    'max-height' : height,
                    'height'     : ''
                });

                blockScroll = false;

                wrap.find('.bp-messages-mobile-tap').css('line-height', wrap.height() + 'px');

                if( wrap.is(miniMobileContainer) ){
                    miniMobileContainer.hide();
                }

                if( wrap.is(mobileViewContainer) ){
                    var source = wrap;
                    source.removeClass('bp-messages-mobile');
                    source.removeAttr('id');

                    var holder = $('.' + holderClass );
                    source.insertBefore( holder );
                    holder.remove();
                }

                $(window).trigger('resize');

                setTimeout(function () {
                    blockOpen = false;
                }, 100);
            }
        });

        mobileOpenButton.click(function(event){
            event.preventDefault();

            if( ! mobileOpenButton.hasClass('loading') ) {
                mobileOpenButton.addClass('loading');

                openMobileFullScreen( BP_Messages['baseUrl'] );
            }
        });

        var __thread = false;
        var __threads = false

        if ($(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .thread.scroller[data-users-json]").length > 0) {
            __thread = $(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .thread.scroller[data-users-json]").attr('data-id');
        } else if ($(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list,#bp-better-messages-mini-mobile-container) .threads-list").length > 0) {
            __threads = true;
        }

        if( __thread || __threads ){
            $('#bp-better-messages-mini-mobile-open, #bp-better-messages-mini-mobile-container').remove();

            if( isMobile && BP_Messages['autoFullScreen'] === '1' ){
                var wrap = $('.bp-messages-wrap.bp-messages-wrap-main.mobile-ready');
                openMobile( wrap );
            }
        }

        /**
         * Disable scroll page when scrolling chats
         */
        if( BP_Messages['blockScroll'] === '1' ){
            $('.bp-messages-wrap').on( 'mousewheel DOMMouseScroll', '.scroll-content', function (e) {
                var e0 = e.originalEvent;
                var delta = e0.wheelDelta || -e0.detail;
                var max = e.currentTarget.scrollHeight - $(e.currentTarget).height();

                this.scrollTop += ( delta < 0 ? 1 : -1 ) * 5;

                if(this.scrollTop < 5 || (max - this.scrollTop) < 5){
                    e.preventDefault();
                }
            });
        }

        if (store.enabled) {
            openThreads = store.get('bp-better-messages-open-threads') || {};
            miniChats = store.get('bp-better-messages-mini-chats') || {};
            miniMessages = store.get('bp-better-messages-mini-messages') || false;
            setInterval(updateOpenThreads, 1000);
        }

        
/* Premium Code Stripped by Freemius */


        reInit();

        if(typeof BP_Messages['socket_server'] === 'undefined' ) {
            setInterval(function () {
                updateLastActivity()
            }, 60000 * 5);

            function updateLastActivity() {
                $.post(BP_Messages.ajaxUrl, {
                    'action': 'bp_messages_last_activity_refresh'
                });
            }
        }

        /**
         * Go to thread from thread list
         */
        bpMessagesWrap.on('click', '.threads-list .thread:not(.blocked)', function (event) {
            if ( $(event.target).closest('.pic').length === 0 &&  $(event.target).closest('.delete').length === 0 && $(event.target).closest('.deleted').length === 0 ) {
                event.preventDefault();
                var href = $(this).attr('data-href');
                var container = $(this).closest('.bp-messages-wrap');
                ajaxRefresh(href, container);
            }
        });

        /**
         * Delete thread! :)
         */
        $(document).on('click', '.bp-messages-wrap .threads-list .thread span.delete', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var thread = $(this).parent().parent();
            var scroller = thread.parent().parent();
            var thread_id = $(thread).attr('data-id');
            var height = $(thread).height();

            var nonce = $(this).attr('data-nonce');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_delete_thread',
                'thread_id': thread_id,
                'nonce': nonce
            }, function (data) {
                if (!data.result) {
                    BBPMShowError(data['errors'][0]);
                } else {
                    var top = thread.position().top;
                    //top = top + scroller.scrollTop();

                    $(thread).addClass('blocked');
                    $(thread).find('.deleted').show().css({
                        'height': height,
                        'line-height': height + 'px',
                        'top': top + 'px'
                    });
                }
            });
        });

        /**
         * UnDelete thread! :)
         */
        $(document).on('click', '.bp-messages-wrap .threads-list .thread a.undelete', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var thread = $(this).parent().parent();
            var thread_id = $(thread).attr('data-id');
            $(thread).removeClass('blocked');

            var nonce = $(this).attr('data-nonce');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_un_delete_thread',
                'thread_id': thread_id,
                'nonce': nonce
            }, function (data) {
                if (!data.result) {
                    BBPMShowError(data['errors'][0]);
                } else {
                    $(thread).removeClass('blocked');
                    $(thread).find('.deleted').hide();
                }
            });
        });

        /**
         * Messages actions
         */
        bpMessagesWrap.on('click', '.messages-list li .favorite', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var message_id = $(this).parentsUntil('.messages-list', 'li').attr('data-id');
            var type = 'star';
            if ($(this).hasClass('active')) type = 'unstar';

            $(this).toggleClass('active');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_favorite',
                'message_id': message_id,
                'thread_id': thread,
                'type': type
            }, function (bool) {});
        });


        var sendingMessage = false;
        var lastForm = '';
        var lastFormTimeout;

        /*
         * Reply submit
         */
        bpMessagesWrap.on('submit', '.reply > form', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if( sendingMessage === true ) return;

            var _form = $(this);
            if(_form.serialize() === lastForm) return false;
            var textarea = $(this).find('textarea[name="message"]');

            var message = BPBMformatTextArea(textarea);

            var form = $(this).serialize();

            sendingMessage = true;
            var _thread = thread;

            var message_id =  $(this).find('input[name="message_id"]');
            var isEdit =  (message_id.val().trim().length > 0) ? true : false;

            var container = $(this).parent().parent();
            var threadDiv = container.find('.thread.scroller[data-users-json]');
            var participants = $.parseJSON(threadDiv.attr('data-users-json'));
            var maybeMini = $(this).parent().parent().hasClass('chat open');
            if(maybeMini) _thread = $(this).parent().parent().attr('data-thread');

            if( BP_Messages['encryption'] === '1' ){
                var secret_key = threadDiv.data('secret');
                message = BPBMAES256.encrypt(message, secret_key);

                $.each(participants, function(index, item){
                    participants[index]['avatar'] = BPBMAES256.encrypt(item['avatar'], secret_key);
                    participants[index]['link']   = BPBMAES256.encrypt(item['link'], secret_key);
                    participants[index]['name']   = BPBMAES256.encrypt(item['name'], secret_key);
                });
            }

            if( _thread && BP_Messages['realtime'] == "1" && ! isEdit ) {

                socket.emit( 'message', _thread, message, participants, function(response){
                    lastForm = form;

                    clearInterval(lastFormTimeout);
                    lastFormTimeout = setInterval(function(){lastForm = ''}, 3000);

                    $(document).trigger("bp-better-messages-message-sent");
                    _form.find('input[name="message_id"]').val('');
                    //editing = false;
                    form += '&tempID=' + response;
                    $.post(BP_Messages.ajaxUrl, form, function (data) {
                        if (typeof data.result == 'undefined') return;

                        $.each(data['errors'], function(){
                            BBPMShowError(this);
                        });

                        if( typeof data.redirect !== 'undefined' ){
                            if( data.redirect === 'refresh' ){
                                var wrap = _form.closest('.bp-messages-wrap');
                                if( wrap.hasClass('bp-better-messages-mini') ){
                                    $('.bp-better-messages-mini .chats .chat[data-thread="' + _thread + '"]').remove();
                                    openMiniChat( _thread, true );
                                } else {
                                    ajaxRefresh(location.href, _form.closest('.bp-messages-wrap'));
                                }
                            }
                        }
                    }).always(function() {
                        sendingMessage = false;
                    });
                });
            } else {
                $.post(BP_Messages.ajaxUrl, form, function (data) {
                    if (typeof data.result == 'undefined') return;
                    if (data.result) {
                        refreshThread();
                        $(document).trigger("bp-better-messages-message-sent");
                        lastForm = form;
                        clearInterval(lastFormTimeout);
                        lastFormTimeout = setInterval(function(){lastForm = ''}, 3000);
                        _form.find('input[name="message_id"]').val('');
                        //editing = false;
                    } else {
                        $.each(data['errors'], function(){
                            BBPMShowError(this);
                        });

                        if( typeof data.redirect !== 'undefined' ){
                            if( data.redirect === 'refresh' ){
                                ajaxRefresh(location.href, _form.closest('.bp-messages-wrap'));
                            }
                        }

                    }
                }).always(function() {
                    sendingMessage = false;
                });
            }

            $(this).find('textarea, .bp-emojionearea-editor').html('').val('');
        });

        bpMessagesWrap.on('click ', '.bpbm-stickers-selector .bpbm-stickers-selector-sticker', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var sticker  = $(this);
            var stickersDiv = sticker.closest('.bpbm-stickers-selector');
            var container   = stickersDiv.parent();
            var formEl      = container.find('.reply form');

            var stickerId  = sticker.data('sticker-id');
            var stickerImg = sticker.find('img').attr('src');
            var threadId   = formEl.find('input[name="thread_id"]').val();
            var nonce      = formEl.find('input[name="_wpnonce"]').val();

            stickersDiv.hide();
            makeHeightBeautiful();

            $.post(BP_Messages.ajaxUrl, {
                'action'      : 'bpbm_messages_send_sticker',
                'thread_id'   : threadId,
                'sticker_id'  : stickerId,
                'sticker_img' : stickerImg,
                '_wpnonce'    : nonce,
            }, function (data) {
            });
        });

        bpMessagesWrap.on('click touchstart', '.reply .bpbm-stickers-btn', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var replyDiv = $(this).closest('.reply');
            var wrapper  = replyDiv.parent();
            var formEl   = replyDiv.find('form');
            var textarea = formEl.find('textarea');
            var stickersDiv = wrapper.find('.bpbm-stickers-selector');
            textarea.blur();

            if( ! stickersDiv.is(':visible') ){
                stickersDiv.show();
                //makeHeightBeautiful();
            }
        });


        bpMessagesWrap.on('click', '.bpbm-stickers-selector .bpbm-stickers-close', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var stickersDiv = $(this).closest('.bpbm-stickers-selector');
            var container   = stickersDiv.parent();
            var formEl      = container.find('.reply form');

            stickersDiv.hide();
        });

        bpMessagesWrap.on('click', '.bpbm-stickers-selector .bpbm-stickers-head .bpbm-stickers-tabs span', function(event){
            event.preventDefault();
            event.stopPropagation();

            var tab      = $(this);
            var stickersDiv = tab.closest('.bpbm-stickers-selector');
            var stickersContainer = stickersDiv.find('.bpbm-stickers-selector-sticker-container');

            stickersDiv.find('.bpbm-stickers-tabs span').removeClass('bpbm-stickers-tabs-active');
            tab.addClass('bpbm-stickers-tabs-active');
            stickersContainer.html(loadingHtml);

            var selected = tab.data('package-id');
            $.post(BP_Messages.ajaxUrl, {
                'action'  : 'bpbm_messages_get_sticker_tab',
                'package' : selected,
            }, function (data) {
                stickersContainer.html( data );
            });
        });

        var stickersSearchLoading;

        bpMessagesWrap.on('keyup change', '.bpbm-stickers-selector .bpbm-stickers-search input', function(event){
            event.preventDefault();
            event.stopPropagation();

            var input        = $(this);
            var searchDiv    = input.closest('.bpbm-stickers-search');
            var stickersDiv  = input.closest('.bpbm-stickers-selector');
            var stickersList = stickersDiv.find('.bpbm-stickers-selector-sticker-list');

            var loadingSearch = searchDiv.data('search-term');
            var newSearch = input.val();

            if( loadingSearch !== newSearch ) {
                searchDiv.data('search-term', newSearch);
                if (stickersSearchLoading) {
                    stickersSearchLoading.abort();
                }

                stickersSearchLoading = $.post(BP_Messages.ajaxUrl, {
                    'action': 'bpbm_messages_search_stickers',
                    'search': newSearch
                }, function (data) {
                    stickersList.replaceWith(data);
                    stickersList = stickersDiv.find('.bpbm-stickers-selector-sticker-list');

                    stickersList.BPBMoverlayScrollbars({
                        'sizeAutoCapable': false,
                        callbacks : {
                            onScroll : function( arg1 ){
                                var position  = this.scroll();
                                var scroll    = position.position.y;
                                var height    = position.max.y;

                                if( height - scroll <= 10 ) {
                                    var elements     = this.getElements();
                                    var host         = jQuery(elements.host);
                                    var loadingClass = 'bpbm-loading-stickers';
                                    if( ! host.hasClass(loadingClass) ){
                                        var maxPages   = host.data('pages');
                                        var pageToLoad = host.data('pages-loaded') + 1;
                                        if( pageToLoad <= maxPages ){
                                            host.addClass(loadingClass);
                                            $.post(BP_Messages.ajaxUrl, {
                                                'action' : 'bpbm_messages_search_stickers',
                                                'search' : newSearch,
                                                'page'   : pageToLoad
                                            }, function(response){
                                                host.data('pages-loaded', pageToLoad);
                                                $(response).appendTo( jQuery(elements.content) );
                                                host.removeClass(loadingClass);
                                            });
                                        }
                                    }

                                }
                            }
                        }
                    });
                });
            }
        });


        /**
         * New Thread Submit
         */
        bpMessagesWrap.on('submit', '.new-message form', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var container = jQuery(event.target).closest('.bp-messages-wrap');

            $('.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .preloader').show();

            var textarea = $(this).find('textarea[name="message"]');

            BPBMformatTextArea(textarea);

            var form = $(this).serialize();

            $.post(BP_Messages.ajaxUrl, form, function (data) {
                if (data.result) {
                    ajaxRefresh(BP_Messages.threadUrl + data['result'], container);
                } else {
                    $('.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .preloader').hide();
                    $.each(data['errors'], function(){
                        BBPMShowError(this);
                    });
                }
            });
        });

        /**
         * Switches screens without page reloading
         */
        bpMessagesWrap.on('click', 'a.ajax', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var href = $(this).attr('href');
            var container = $(this).closest('.bp-messages-wrap');

            ajaxRefresh(href, container);
        });

        /**
         *  Search form functions
         */
        bpMessagesWrap.on('click', '.bpbm-search a.search', function (event) {
            event.preventDefault();
            event.stopPropagation();

            $(this).hide();
            $('.bpbm-search form').show();
            $('.bpbm-search form input').trigger('focus');

            if( isMobile ){
                $('.bp-messages-wrap .chat-header .settings').hide();
            }
        });

        bpMessagesWrap.on('click', '.bpbm-search form span.close', function (event) {
            event.preventDefault();
            event.stopPropagation();

            $('.bpbm-search form').hide();
            $('.bpbm-search a.search').show();
            $('.bpbm-search form input').val('');

            if( isMobile ){
                $('.bp-messages-wrap .chat-header .settings').show();
            }
        });


        bpMessagesWrap.on('submit', '.bpbm-search form', function (event) {
            event.preventDefault();
            var container = $(this).closest('.bp-messages-wrap');
            ajaxRefresh(BP_Messages['url'] + '?' + $(this).serialize(), container );
        });

        /**
         * Send message on Enter
         */
        if( (BP_Messages['disableEnterForTouch'] === '1' && isMobile || BP_Messages['disableEnterForDesktop'] === '1' && !isMobile) === false ){

            bpMessagesWrap.on('keydown', '.reply .bp-emojionearea-editor', function (event) {
                if ( ! event.shiftKey && event.keyCode == 13 ) {
                    event.preventDefault();
                    $(this).trigger('blur')
                    $(this).parent().parent().trigger("submit");
                    $(this).trigger('focus')
                }
            });
        }

        bpMessagesWrap.on('change', '.new-message .send-to-input', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var href = $(this).attr('href');
            var container = $(this).closest('.bp-messages-wrap');

            ajaxRefresh(href, container);
        });


        if( isMobile ){
            bpMessagesWrap.on('touchend', '.scroll-wrapper.starred .messages-list li, .scroll-wrapper.search .messages-list li', function(event){
                if(touchmoved != true && blockOpen === false){
                    event.preventDefault();
                    event.stopPropagation();

                    var thread_id = $(this).attr('data-thread');
                    var message_id = $(this).attr('data-id');
                    ajaxRefresh(BP_Messages.threadUrl + thread_id + '&message_id=' + message_id, $(this).closest('.bp-messages-wrap'));
                }
            }).on('touchmove', function(e){
                touchmoved = true;
            }).on('touchstart', function(e){
                touchmoved = false;
            });
        } else {
            bpMessagesWrap.on('click', '.scroll-wrapper.starred .messages-list li, .scroll-wrapper.search .messages-list li', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var thread_id = $(this).attr('data-thread');
                var message_id = $(this).attr('data-id');
                ajaxRefresh(BP_Messages.threadUrl + thread_id + '&message_id=' + message_id, $(this).closest('.bp-messages-wrap'));
            });
        }

        /**
         * Remove users from group thread
         */
        bpMessagesWrap.on('click', '.participants-panel .bp-messages-user-list .user .actions a.remove-from-thread', function (event) {
            event.preventDefault();
            var container = $(this).closest('.bp-messages-wrap');
            var user = $(this).parent().parent();
            var user_id = user.data('id');
            var thread_id = user.data('thread-id');
            var username = user.find('.name').text();

            var exclude = confirm('Exclude ' + username + ' from this thread?');

            if(exclude){
                $.post(BP_Messages.ajaxUrl, {
                    action: 'bp_better_messages_exclude_user_from_thread',
                    user_id: user_id,
                    thread_id: thread_id
                }, function(response){
                    if(response.result === true){
                        var url = BP_Messages['url'] + '?' + $.param({thread_id: thread_id, participants: "1"});
                        ajaxRefresh(url, container);
                    }
                });
            }
        });

        /**
         * Mute thread
         */
        bpMessagesWrap.on('click', '.chat-header .bpbm-mute-thread', function (event) {
            event.preventDefault();
            var container = $(this).closest('.bp-messages-wrap');

            var thread_id = parseInt(container.attr('data-thread-id'));
            var url       = location.href;

            if( container.is('#bp-better-messages-mini-mobile-container') ){
                thread_id = container.attr('data-thread');
                url = BP_Messages['baseUrl'] + '?thread_id=' + thread_id;
            }

            var confirmDelete = confirm(BP_Messages['strings']['mute_thread']);

            if( confirmDelete ){

                container.find('.preloader').show();

                $.post( BP_Messages[ 'ajaxUrl' ], {
                    'action'       : 'bp_messages_mute_thread',
                    'thread_id'    : thread_id
                }, function (response) {
                    ajaxRefresh(url, container);

                    var counterRow = $('.bp-messages-wrap .threads-list .thread[data-id="' + thread_id + '"] .bpbm-counter-row');
                    counterRow.prepend('<span class="bpbm-thread-muted"><i class="fas fa-bell-slash"></i></span>');

                    BP_Messages.mutedThreads[ thread_id ] = Date.now();
                }).always(function() {
                });
            }
        });

        /**
         * Mute thread
         */
        bpMessagesWrap.on('click', '.chat-header .bpbm-unmute-thread', function (event) {
            event.preventDefault();
            var container = $(this).closest('.bp-messages-wrap');

            var thread_id = parseInt(container.attr('data-thread-id'));
            var url       = location.href;

            if( container.is('#bp-better-messages-mini-mobile-container') ){
                thread_id = container.attr('data-thread');
                url = BP_Messages['baseUrl'] + '?thread_id=' + thread_id;
            }


            var confirmDelete = confirm(BP_Messages['strings']['unmute_thread']);

            if( confirmDelete ){

                container.find('.preloader').show();

                $.post( BP_Messages[ 'ajaxUrl' ], {
                    'action'       : 'bp_messages_unmute_thread',
                    'thread_id'    : thread_id
                }, function (response) {
                    if( typeof BP_Messages.mutedThreads[ thread_id ] !== 'undefined' ){
                        delete BP_Messages.mutedThreads[ thread_id ];
                    }

                    $('.bp-messages-wrap .threads-list .thread[data-id="' + thread_id + '"] .bpbm-counter-row .bpbm-thread-muted').remove();
                    ajaxRefresh(url, container);
                }).always(function() {
                });
            }
        });

        /**
         * Add new users to group thread
         */
        bpMessagesWrap.on('click', '.participants-panel .add-user button', function (event) {
            event.preventDefault();
            var container = $(this).closest('.bp-messages-wrap');
            var form = $(this).parent();
            var thread_id = form.data('thread-id');
            var users = [];

            form.find( 'input[name="recipients[]"]' ).each(function(){
                users.push( $(this).val() );
            });

            $.post(BP_Messages.ajaxUrl, {
                action: 'bp_better_messages_add_user_to_thread',
                users: users,
                thread_id: thread_id
            }, function(response){
                var url = BP_Messages['url'] + '?' + $.param( { thread_id: thread_id, participants: "1" });
                ajaxRefresh(url, container);
            });
        });

        if( BP_Messages['allowDeleteMessages'] === '1' ){
            bpMessagesWrap.on('click', '.list .messages-stack.outgoing .content .messages-list li', function(event){
                if( ( $(event.target).hasClass('message-content') ||  $(event.target).is('li') ||  $(event.target).hasClass('images') )
                    && ! $(this).closest('.bp-messages-wrap').hasClass('bp-better-messages-mini')
                ){
                    selectMessage(this);
                }
            });
        }


        bpMessagesWrap.on('click', '.chat-controls a.bpbm-edit', function(event) {
            event.preventDefault();

            var wrap     = $(this).closest('.bp-messages-wrap');
            var nonce    = $(this).data('wp-nonce');
            var selected = wrap.find('.messages-stack li.selected');

            var message_id = selected.data('id');
            //var content  = selected.find('.message-content').html();

            $.post( BP_Messages[ 'ajaxUrl' ], {
                'action'     : 'bp_messages_get_edit_message',
                'message_id' : message_id,
                '_wpnonce'   : nonce
            }, function (response) {
                wrap.find('.reply form input[name="message_id"]').val( message_id );
                wrap.find('.bp-emojionearea-editor').html(response);
            });
        });

        bpMessagesWrap.on('click', '.chat-controls a.bpbm-delete', function(event){
            event.preventDefault();

            var nonce    = $(this).data('wp-nonce');
            var string   = BP_Messages['strings']['confirm_delete'];
            var wrap     = $(this).closest('.bp-messages-wrap');
            var selected = wrap.find('.messages-stack li.selected');

            string = string.replace('%s', selected.length);
            var confirmDelete = confirm(string);

            if( confirmDelete ){
                var messages_ids = [];

                $.each( selected, function() {
                    messages_ids.push( $( this ).data( 'id' ) );
                } );

                $.post( BP_Messages[ 'ajaxUrl' ], {
                    'action'       : 'bp_messages_delete_message',
                    'thread_id'    : thread,
                    'messages_ids' : messages_ids,
                    '_wpnonce'     : nonce
                }, function (response) {
                    if( response.result ){
                        BBPMNotice(response.message);
                        deleteMessage( messages_ids );
                    } else {
                        BBPMShowError(response.errors[0]);
                    }

                    wrap.find('.chat-controls').hide();
                });
            }
        });

        function selectMessage(message, _selected) {
            if(typeof _selected === 'undefined'){
                $(message).toggleClass('selected');
            } else {
                if(_selected === true){
                    $(message).addClass('selected');
                } else {
                    $(message).removeClass('selected');
                }
            }

            var wrap = $(message).closest('.bp-messages-wrap');
            var controls = wrap.find('.chat-controls');
            var selected = wrap.find('.messages-stack li.selected').length;

            if(selected > 0){
                controls.show();

                var canEdit = true;
                var canDelete = true;

                /**
                 * We can delete or edit only own messages
                 */
                var incoming = wrap.find('.messages-stack.incoming li.selected').length;

                if(incoming > 0){
                    canEdit = false;
                    canDelete = false;
                }

                /**
                 * We can edit only single message
                 */
                if(selected > 1){
                    canEdit = false;
                }

                if( ! canEdit ) {
                    controls.find('.bpbm-edit').hide();
                } else {
                    controls.find('.bpbm-edit').show();
                }
                if( ! canDelete ) {
                    controls.find('.bpbm-delete').hide();
                } else {
                    controls.find('.bpbm-delete').show();
                }
            } else {
                controls.hide();
            }
        }

        
/* Premium Code Stripped by Freemius */


        
/* Premium Code Stripped by Freemius */

        $(document).on('click', '.bp-better-messages-list .tabs > div', function (event) {
            event.preventDefault();
            var tab = $(this).data('tab');
            if (! $(this).hasClass('active') ) {
                $('.bp-better-messages-list .tabs > div, .bp-better-messages-list .tabs-content > div').removeClass('active');
                $(this).addClass('active');
                $('.bp-better-messages-list .tabs-content .' + tab).addClass('active');
                miniMessages = tab;
            } else {
                $(this).removeClass('active');
                $('.bp-better-messages-list .tabs-content .' + tab).removeClass('active');
                miniMessages = false;
            }

            store.set('bp-better-messages-mini-messages', miniMessages);
        });


        $(document).on('click', '.bpbm-deleted-user-link', function (event) {
            event.preventDefault();
        });

        $(document).on('click', '.bp-better-messages-list .bp-messages-user-list .user:not(.blocked)', function(event){
           if($(event.target).is('div')){
               event.preventDefault();
               var user = $(this);
               var user_id = $(this).data('id');
               var username = $(this).data('username');

               if (BP_Messages['miniChats'] == '1' && BP_Messages['fastStart'] == '1') {
                   var scroller = user.parent().parent();
                   var height = $(user).height();
                   var top = user.position().top;
                   top = top + scroller.scrollTop();
                   $(user).find('.loading').css({
                       'height': height,
                       'line-height': height + 'px',
                       'top': top + 'px'
                   });

                   user.addClass('blocked loading');

                   openPrivateThread(user_id).always(function (done) {
                        user.removeClass('blocked loading');
                   });
               } else {
                   var redirect = BP_Messages['url'] + '?new-message&to=' + username;
                   if(BP_Messages['fastStart'] == '1') redirect += '&fast=1';
                   location.href = redirect;
               }
           }
        });
        
/* Premium Code Stripped by Freemius */

    });

    function deleteMessage( messages_ids ){
        $.each(messages_ids, function(){
           var message_id = this;

           var li = $('.bp-messages-wrap .list .messages-stack .content .messages-list li[data-id="' + message_id + '"]');
           var stack = li.closest('.messages-stack');
           li.remove();
           var messages = stack.find('.messages-list > li');
           if( messages.length === 0 ){
               stack.remove();
           }

           $('.bp-messages-wrap .threads-list .thread[data-message="' + message_id + '"] .info p').text('...');
        });

    }

    function changeMaxHeight(){
        var height = $( window ).height() - 50;
        var admin_bar = $('#wpadminbar');
        if( admin_bar.length > 0 ){
            if( admin_bar.is(':visible') ){
                height = height - admin_bar.height();
            }
        }

        if(height > BP_Messages['max_height']) height = BP_Messages['max_height'];
        height = parseInt(height);

        jQuery('.bp-messages-wrap .scroller').each(function(){
            var scroller = $(this);

            var bpMessagesColumn = scroller.closest('.bp-messages-column');

            if( bpMessagesColumn.length > 0 && bpMessagesColumn.parent().find('.bp-messages-side-threads').is(':visible') ){
                var _height = bpMessagesColumn.outerHeight() - bpMessagesColumn.find('.reply').outerHeight();

                scroller.css( 'max-height', _height );
                scroller.css( 'height', _height );
            } else {
                scroller.css( 'max-height', height );
                scroller.css( 'height', height );
            }
        });

        jQuery('.bp-messages-wrap.bp-better-messages-list .scroll-wrapper').css( 'max-height', height - 50 );
        jQuery('.bp-messages-wrap.bp-better-messages-mini .scroll-wrapper').css( 'max-height', height - 50 );
    }

    function makeHeightBeautiful(){
        $.each(bpMessagesWrap, function( index, item ){
            var wrap = $(item);

            if( wrap.is('.bp-messages-wrap-main') ) {
                var sideThreads = wrap.find('> .bp-messages-side-threads-wrapper .bp-messages-side-threads');
                if (sideThreads.length > 0) {
                    var threadWrapper = sideThreads.closest('.bp-messages-side-threads-wrapper');
                    var neededWidth = sideThreads.width() * 2;

                    if( wrap.width() < neededWidth ){
                        threadWrapper.addClass('threads-hidden');
                        sideThreads.hide();
                    } else {
                        threadWrapper.removeClass('threads-hidden');
                        sideThreads.show();
                    }
                }
            }
        });

        if( bpMessagesWrap.hasClass('bp-messages-mobile') ) {
            var windowHeight = window.innerHeight;
            var usedHeight = 0;
            usedHeight = usedHeight + bpMessagesWrap.find('.chat-header').outerHeight();
            usedHeight = usedHeight + bpMessagesWrap.find('.reply').outerHeight();

            $('.scroller').css('height', windowHeight - usedHeight);
        } else {
            changeMaxHeight();
        }
    }

    jQuery(document).on('bp-better-messages-update-unread', function( event, unread ) {
        var _unread = jQuery('.bp-better-messages-unread');

        unread = parseInt(unread);
        if( isNaN ( unread ) || unread < 0 ) unread = 0;

        if( unread === 0 ){
            _unread.addClass('no-count');
        } else {
            _unread.removeClass('no-count');
        }

        _unread.text(unread);
    });

    
/* Premium Code Stripped by Freemius */


    function unique(list) {
        var result = [];
        $.each(list, function(i, e) {
            if ($.inArray(e, result) === -1) result.push(e);
        });
        return result;
    }

    /**
     * Function to determine where we now and what we need to do
     */
    function reInit() {
        thread = false;
        threads = false;
        reIniting = true;
        clearTimeout(checkerTimer);

        // Only initialize new media elements.

        if( typeof $.fn.mediaelementplayer === 'function') {
            $( '.bp-messages-wrap .wp-audio-shortcode, .bp-messages-wrap .wp-video-shortcode' )
                .not( '.mejs-container' )
                .filter(function () {
                    return ! $( this ).parent().hasClass( '.mejs-mediaelement' );
                }).mediaelementplayer();
        }

        $(document).trigger("bp-better-messages-update-unread", BP_Messages.total_unread);
        $(document).trigger("bp-better-messages-reinit-start");

        updateOpenThreads();

        onlineInit();

        if ($(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .thread.scroller[data-users-json]").length > 0) {
            thread = $(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .thread.scroller[data-users-json]").attr('data-id');
        } else if ($(".bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list,#bp-better-messages-mini-mobile-container) .threads-list").length > 0) {
            threads = true;
        }

        if (thread) {
            checkerTimer = setTimeout(refreshThread, BP_Messages.threadRefresh);
        } else {
            checkerTimer = setTimeout(refreshSite, BP_Messages.siteRefresh);
        }

        
/* Premium Code Stripped by Freemius */


        var direction = 'ltr';
        if( isRtl ) direction = 'rtl';

        var isEmojiEnabledOnMobile = BP_Messages['mobileEmojiEnable'] === '1';

        if( isEmojiEnabledOnMobile || ( ! isMobile && ! $('body').hasClass('bp-messages-mobile') ) ) {
            var selector = ".bp-messages-wrap .reply .message textarea, " +
                ".bp-messages-wrap .new-message #message-input, " +
                ".bp-messages-wrap .bulk-message #message-input";

            initializeEmojiArea(selector);
        } else {
            var selector = ".bp-better-messages-mini .chats .chat .reply .message textarea";

            initializeEmojiArea(selector);

            $.fn.BPBMloadEmojione(123);
            makeHeightBeautiful();
        }

        function initializeEmojiArea(selector){
            var emojionearea = $(selector).BPemojioneArea({
                tones: true,
                tonesStyle: 'bullet',
                saveEmojisAs : "unicode",
                autocomplete: false,
                stayFocused: false,
                attributes: {
                    dir: direction
                }
            });

            if( typeof emojionearea[0] !== 'undefined' ) {
                emojionearea.closest('form').addClass('bp-emoji-enabled');

                var areaLoaded = false;

                try {
                    emojionearea[0].BPemojioneArea.on("onLoad", function () {
                        afterAreaLoaded();
                    });
                } catch (e){}

                setTimeout(afterAreaLoaded, 333);

                function afterAreaLoaded(){
                    if( areaLoaded === true ) {
                        return false;
                    }

                    var areaExists = emojionearea.next('.bp-emojionearea').length > 0;

                    if( areaExists ) {
                        areaLoaded = true;
                        makeHeightBeautiful();

                        var opts = {
                            toolbar: {
                                allowMultiParagraphSelection: false,
                                buttons: ['bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'removeFormat'],
                                diffLeft: 0,
                                diffTop: -10,
                                firstButtonClass: 'medium-editor-button-first',
                                lastButtonClass: 'medium-editor-button-last',
                                relativeContainer: null,
                                standardizeSelectionStart: false,
                                static: false,
                                align: 'center',
                                sticky: false,
                                updateOnEmptySelection: true,
                            },
                            placeholder: false,
                            imageDragging: false
                        };

                        new BPBM_MediumEditor('.bp-emojionearea-editor', opts);

                        $(emojionearea).html('<p></p>');
                    } else {
                        setTimeout(afterAreaLoaded, 333);
                    }
                }
            }
        }

        if( typeof $.fn.BPBMoverlayScrollbars === 'function' ) {

            jQuery('.bp-better-messages-list .tabs-content .friends .scroller,.scroller.search, .scroller.starred').BPBMoverlayScrollbars({
                'sizeAutoCapable': false,
            });

            jQuery('.bpbm-stickers-selector .bpbm-stickers-head .bpbm-stickers-tabs').BPBMoverlayScrollbars({
                'sizeAutoCapable': false,
                overflowBehavior : {
                    y: 'hidden'
                }
                });

            jQuery('.scroller.thread').BPBMoverlayScrollbars({
                'sizeAutoCapable': false,
                callbacks : {
                    onInitialized: function(){
                        var elements    = this.getElements();
                        var position  = this.scroll();
                        var height    = position.max.y;

                        if( jQuery(elements.host).is(':visible') && height === 0 ){
                            loadMoreMessages(this, true);
                        } else {
                            this.scroll({y: '100%'});
                        }
                    },
                    onScroll : function( arg1 ){
                        var position  = this.scroll();
                        var scroll    = position.position.y;
                        var height    = position.max.y;

                        if( height === 0 ){
                            loadMoreMessages(this, true);
                        } else if( ! jQuery(this.getElements().host).hasClass('user-scrolled') ){
                            this.scroll({y: '100%'}, 0);
                        } else if( scroll === 0 ) {
                            loadMoreMessages(this);
                        }
                    },
                    onContentSizeChanged : function( arg1 ){
                        var elements    = this.getElements();
                        var position    = this.scroll();
                        var height      = position.max.y;
                        var scroll      = position.position.y;
                        var host        = jQuery(elements.host);
                        var content     = jQuery(elements.content);
                        var last        = content.find('.list .messages-stack:last .content .messages-list li:last')
                        var last_height = last.outerHeight();
                        if( last_height < 100 ) last_height = 100;

                        if( ! host.hasClass('user-scrolled') || ( height - scroll ) < last_height + 50 ){
                            this.scroll({y: '100%'}, 100);
                        }

                    }
                }
            });


            jQuery('.scroller.threads-list-wrapper').BPBMoverlayScrollbars({
                'sizeAutoCapable': false,
                callbacks : {
                    onInitialized: function(){},
                    onScroll : function( arg1 ){
                        var elements  = this.getElements();
                        var position  = this.scroll();
                        var scroll    = position.position.y;
                        var height    = position.max.y;
                        var host      = jQuery(elements.host);

                        if(height - scroll <= 100 ) {
                            loadMoreThreads(host);
                        }
                    },
                    onContentSizeChanged : function( arg1 ){
                    }
                }
            });
        }

        var threads_list = jQuery('.bp-messages-wrap:not(.bp-better-messages-list,.bp-better-messages-mini) .scroll-wrapper .scrollbar-inner.scroll-content.threads-list-wrapper');
        if( threads_list.length === 0 ) {
            threads_list = jQuery('.bp-messages-wrap:not(.bp-better-messages-list,.bp-better-messages-mini) .scroller.scrollbar-inner.threads-list-wrapper');
        }

        if( threads_list.length > 0 ){
            if( Math.ceil(threads_list.get(0).scrollHeight) <= Math.ceil(threads_list.innerHeight())  ) {
                //loadMoreThreads(threads_list, true);
            }
        }

        jQuery('.scrollbar-inner').on('touchstart click mousewheel DOMMouseScroll', function(){
            $(this).addClass('user-scrolled');
        });

        if( typeof $.fn.BPBMmagnificPopup === 'function') {
            $('.bp-messages-wrap .list .messages-stack .content .messages-list li .images').BPBMmagnificPopup({
                delegate: 'a',
                type: 'image'
            });
        }

        if(BP_Messages['realtime'] == '1') {

            // Load Statuses
            var messages_ids = [];
            var threadsIds   = [];
            $(  '.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list) .list .messages-stack .content .messages-list li:not(.seen), ' +
                '.bp-messages-wrap.bp-better-messages-mini .chat.open .list .messages-stack .content .messages-list li:not(.seen)'
            ).each(function () {
                var id = $(this).data('id');
                var thread_id = $(this).data('thread');
                messages_ids.push(id);
                threadsIds.push(thread_id);
            });

            socket.emit('getStatuses', messages_ids, function(statuses){
                var requestedIds = {};
                $.each( messages_ids, function () {
                    requestedIds[this] = true;
                });

                $.each(statuses, function(index){
                    delete requestedIds[index];
                    var message = $('.bp-messages-wrap .list .messages-stack .content .messages-list li[data-id="' + index + '"]');
                    message.removeClass('sent delivered seen');
                    if( ! message.hasClass('my') && ! message.hasClass('fast') ){
                        if(this.toString() !== 'seen'){
                            socket.emit('seen', [index]);
                        }
                    } else {
                        var status = 'sent';
                        $.each(this, function(){
                           if(status == 'seen') return false;
                            if(this == 'delivered' && status != 'seen') status = 'delivered';
                            if(this == 'seen') status = 'seen';
                        });

                        message.addClass(status);
                        var statusTitle = '';
                        switch(status){
                            case 'sent':
                                statusTitle = BP_Messages['strings']['sent'];
                                break;
                            case 'delivered':
                                statusTitle = BP_Messages['strings']['delivered'];
                                break;
                            case 'seen':
                                statusTitle = BP_Messages['strings']['seen'];
                                break;
                        }

                        message.find('.status').attr('title', statusTitle);
                    }
                });

                $.each(requestedIds, function(message_id){
                    var message = $('.bp-messages-wrap .list .messages-stack .content .messages-list li[data-id="' + message_id + '"]');
                    message.removeClass('sent delivered seen');
                    message.addClass('seen');
                    message.find('.status').attr('title', BP_Messages['strings']['seen']);
                });
            });

            if(thread) {
                socket.emit('threadOpen', thread);
            }

            threadsIds = unique(threadsIds);
            $.each(threadsIds, function () {
                socket.emit('threadOpen', this);
            });

            socket.emit('requestUnread');
        }


        function loadMoreThreads(element, loadUntilScroll) {
            if( typeof loadUntilScroll === 'undefined' ) loadUntilScroll = false;
            var wrapper = $( element );
            if( wrapper.hasClass('loading-more') || wrapper.hasClass('all-loaded') ) return false;

            wrapper.addClass('loading-more');
            wrapper.find('.loading-messages').show();

            var loadedThreads = [];
            var loader = wrapper.find('.loading-messages');

            wrapper.find('.threads-list > .thread').each(function () {
                loadedThreads.push( $(this).data('id') );
            });

            var args = {
                'action'         : 'bp_messages_get_more_threads',
                'loaded_threads' : loadedThreads,
                'user_id'        : BP_Messages['displayed_user_id']
            };

            if( wrapper.closest('.bp-messages-wrap').hasClass('bp-better-messages-list') ){
                args.user_id = BP_Messages['user_id']
            }

            $.post(
                BP_Messages.ajaxUrl,
                args
                , function (html) {
                    $(html).insertBefore( loader );
                    wrapper.removeClass('loading-more');
                    wrapper.find('.loading-messages').hide();

                    if( html.trim() === '' ){
                        wrapper.addClass('all-loaded');
                    } else {
                        if( loadUntilScroll && Math.ceil(element.get(0).scrollHeight) <= Math.ceil(element.innerHeight()) ) {
                            loadMoreThreads(element, true);
                        }
                    }

                }
            );

        }


        makeHeightBeautiful();

        $( window ).on('resize', function() {
            makeHeightBeautiful();
        });

        if ($('.bp-messages-wrap #send-to:not(.ready)').length > 0) {
            var cache = [];

            var sentTo = new Taggle('send-to', {
                placeholder: '',
                tabIndex: 2,
                hiddenInputName: 'recipients[]'
            });
            var container = sentTo.getContainer();
            var input     = sentTo.getInput();

            var to = $('input[name="to"]');

            if (to.length > 0) {
                $(to).each(function () {
                    var img   = $(this).data('img');
                    var label =  $(this).data('label');
                    sentTo.add($(this).val());
                    $(container).find('.taggle_sizer').text('');
                    $('#send-to li.taggle:last .taggle_text').html('<span class="bpbm-avatar"><img src="' + img + '" class="avatar photo" width="50" height="50"></span><span class="bpbm-name">' + label + '</span>');
                    $(this).remove();
                });
            }

            $(input).on( 'blur', function( event ) {
               var placeholder = $(container).find('.taggle_sizer').text();
               sentTo.add( placeholder );
            });

            $(input).autocomplete({
                source: function (request, response) {
                    var term = request.term;
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }

                    $.getJSON(BP_Messages.ajaxUrl + "?q=" + term + "&limit=10&action=bp_messages_autocomplete&cookie=" + getAutocompleteCookies(), request, function (data, status, xhr) {
                        cache[term] = data;
                        response(data);
                    });
                },
                minLength: 2,
                appendTo: container,
                position: {at: "left bottom", of: container},
                open: function (event, ui) {
                    var autocomplete = $(".bp-messages-wrap #send-to .ui-autocomplete");
                    var oldTop = parseInt(autocomplete.css('top'));
                    var newTop = oldTop - 3;
                    autocomplete.css("top", newTop);
                },
                select: function (event, data) {
                    event.preventDefault();
                    //Add the tag if user clicks
                    if (event.which === 1) {
                        sentTo.add(data.item.value);
                        $(container).find('.taggle_sizer').text('');
                        $('#send-to li.taggle:last .taggle_text').html('<span class="bpbm-avatar">' + data.item.img + '</span><span class="bpbm-name">' + data.item.label + '</span>');
                    }
                },
                response: function( event, data ){
                    $('.ui-helper-hidden-accessible').hide()
                }
            }).autocomplete( "instance" )._renderItem = function( ul, item ) {
                return $( "<li>" )
                    .attr( "data-value", item.value )
                    .attr( "data-label", item.label )
                    .append( '<span class="bpbm-avatar">' + item.img + '</span><span class="bpbm-name">' + item.label + '</span>' )
                    .appendTo( ul );
            };

            $('#send-to').addClass('ready');
        }

        bpMessagesWrap.find('.bp-messages-mobile-tap').css('line-height', bpMessagesWrap.height() + 'px');

        $('.bp-messages-side-threads .threads-list .thread.bp-messages-active-thread').removeClass('bp-messages-active-thread');
        if( thread ){
            $('.bp-messages-side-threads .threads-list .thread[data-id="' + thread + '"]').addClass('bp-messages-active-thread');
        }
        /**
         * JetPack Lazy Load
         */
        $(document.body).trigger('post-load');


        
/* Premium Code Stripped by Freemius */


        calculateTitle(bpMessagesWrap);

        reIniting = false;
    }

    function loadMoreMessages(scroller, loadUntilScroll) {
        if( typeof loadUntilScroll === 'undefined' ){
            loadUntilScroll = false;
        }

        var elements = scroller.getElements();
        var wrap = jQuery(elements.host);

        if( wrap.hasClass('loadingAtTheMoment')
            || wrap.hasClass('allMessagesWasLoaded')
        ) return false;


        var thread_id = wrap.data('id');

        wrap.find('.loading-messages').show();
        wrap.addClass('loadingAtTheMoment');
        var last_message = wrap.find('.messages-stack:first-child .messages-list li:first-child');
        var last_message_id = last_message.attr('data-id');

        $.post(
            BP_Messages.ajaxUrl,
            {
                'action'     : 'bp_messages_thread_load_messages',
                'thread_id'  : thread_id,
                'message_id' : last_message_id
            }, function (data) {
                wrap.find('.loading-messages').hide();
                if(data.trim() === '') wrap.addClass('allMessagesWasLoaded');

                $(data).prependTo( wrap.find('.list') );
                wrap.addClass('hasLoadedMessages');

                scroller.scroll( { el: last_message.closest('.messages-stack'), margin : 15 } );

                reInit();
                wrap.removeClass('loadingAtTheMoment');


                if( loadUntilScroll ){
                    if( scroller.scroll().max.y === 0){
                        loadMoreMessages(scroller, true);
                    }
                }
           }
        );
    }

    function scrollBottom(target) {
        if(typeof target == 'undefined') target = '.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list)';
        if($(target + " .list").length == 0) return;

        var scroller = $(target + ' .scroller[data-id]');
        var thread_id = scroller.data('id');
        if(typeof loadingMore[thread_id] !== 'undefined') return false;

        var list = $(target + " .list");
        var scroll = list[0].offsetHeight;

        if (getParameterByName('message_id').length > 0) {
            var message_id = getParameterByName('message_id');
            var message = $(target + " .messages-list li[data-id='" + message_id + "']");

            if (message.length > 0) {
                scroll = message[0].offsetTop - message[0].offsetHeight - 100;
            }
        }

        var scrollWrapper = list.closest('.scroller');
        var userScrolled = scrollWrapper.hasClass('user-scrolled');

        var currentScroll = scrollWrapper[0].scrollHeight - scrollWrapper.scrollTop() - scrollWrapper.height();
        //if(  scrollWrapper.scrollTop() === 0 ) currentScroll = 0;

        if(scroll < scrollWrapper.outerHeight()) return;
        if( ! userScrolled || (100 >= currentScroll)){
            var scrollbar = scroller.BPBMoverlayScrollbars();
            if(typeof scrollbar !== 'undefined'){
                scrollbar.update();
                scrollbar.scroll({y: '100%'});
            }
        }
    }

    /**
     * Check for new messages on all sites page
     */
    var refreshSiteRunning = false;
    function refreshSite() {
        clearInterval(checkerTimer);
        checkerTimer = setTimeout(refreshSite, BP_Messages.siteRefresh);

        if (BP_Messages['realtime'] == "1" || refreshSiteRunning) return;

        var last_check = readCookie('bp-messages-last-check');
        refreshSiteRunning = true;
        $.post(BP_Messages.ajaxUrl, {
            'action': 'bp_messages_check_new',
            'last_check': last_check
        }, function (response) {

            if (response.threads.length > 0) {
                $.each(response.threads, function () {
                    var message = this;
                    if (threads) {
                        updateThreads(message);
                    } else {
                        showMessage(message.thread_id, message['message'], message['name'], message['avatar']);
                    }
                });
            }

            $(document).trigger("bp-better-messages-update-unread", response.total_unread);

            refreshSiteRunning = false;
        });
    }


    /**
     * Check for new messages on open thread screen
     */
    var refreshThreadRunning = false;
    function refreshThread() {
        clearInterval(checkerTimer);
        checkerTimer = setTimeout(refreshThread, BP_Messages.threadRefresh);
        if (BP_Messages['realtime'] == "1" || refreshThreadRunning) return;

        var last_check = readCookie('bp-messages-last-check');
        var last_message = $('.messages-stack:last-child .messages-list li:last-child').attr('data-time');
        refreshThreadRunning = true;
        $.post(BP_Messages.ajaxUrl, {
            'action': 'bp_messages_thread_check_new',
            'last_check': last_check,
            'thread_id': thread,
            'last_message': last_message
        }, function (response) {

            $.each(response.messages, function () {
                renderMessage(this);
            });

            $.each(response.threads, function () {
                showMessage(this.thread_id, this['message'], this['name'], this['avatar']);
            });

            $(document).trigger("bp-better-messages-update-unread", response.total_unread);

            refreshThreadRunning = false;
        });
    }

    function updateOpenThreads() {

        if ( ! store.enabled )  return false;

        openThreads = store.get('bp-better-messages-open-threads') || {};

        if (thread !== false) {
            openThreads[thread] = Date.now();
        }

        $.each(openThreads, function (index) {
            if ((this + 2000) < Date.now()) delete openThreads[index];
        });

        store.set('bp-better-messages-open-threads', openThreads);

    }

    /**
     * Simple function to avoid page reloading
     *
     * @param url
     */
    function ajaxRefresh(url, container) {
        if( typeof container === 'undefined' ){
            var target = jQuery(event.target);
            if( target.hasClass('bp-messages-wrap') ){
                container = target;
            } else {
                container = target.closest('.bp-messages-wrap');
            }
        }

        if( isInCall ){
            if ( confirm(BP_Messages['strings']['you_are_in_call']) === false ) {
                return false;
            } else {
                jQuery(document).trigger('bpbm-end-call');
            }
        }

        if( container.hasClass('bp-messages-wrap-main') ){
            try {
                window.history.pushState("", "", url);
            } catch(e){}
        }

        container.find('.preloader').show();

        $(window).off( ".bp-messages" );

        var target_url = '?action=bp_messages_load_via_ajax';
        if( typeof url.split('?')[1] !== 'undefined' ){
            target_url = '?' + url.split('?')[1] + '&action=bp_messages_load_via_ajax';
        }

        var side_threads = container.find('.bp-messages-side-threads-wrapper');
        var ajax_url = BP_Messages['ajaxUrl'] + target_url;

        if( side_threads.length > 0 ){
            ajax_url += '&ignore_threads';
        }

        $.ajax({
            method: "GET",
            url: ajax_url,
            cache: false,
            success: function (html) {
                html = $(html);

                var side_threads_in_return = html.find('.bp-messages-side-threads-wrapper');

                if( side_threads.length > 0 && side_threads_in_return.length > 0 ) {
                    container.find('.bp-messages-side-threads-wrapper .bp-messages-column').html(html.find('.bp-messages-side-threads-wrapper .bp-messages-column').html());
                    container.find('.bp-messages-side-threads-wrapper').closest('.bp-messages-wrap').find('.chat-header').html(html.find('.bp-messages-side-threads-wrapper').closest('.bp-messages-wrap').find('.chat-header').html());
                } else {
                    var newWrapper = html.html();
                    container.html(newWrapper);
                }

                if( container.hasClass('bp-messages-wrap-main') ){
                    container.attr('data-thread-id', html.attr('data-thread-id') );
                }

                if(container.is('#bp-better-messages-mini-mobile-container')){
                    container.attr('data-thread', html.attr('data-thread-id') );
                }

                reInit();
            }
        });
    }

    /**
     * Online avatars init
     */
    function onlineInit() {
        $('.bp-messages-wrap img.avatar[data-user-id]').each(function () {
            var user_id = $(this).attr('data-user-id');
            var parent = false;

            if ($(this).parent().hasClass('bbpm-avatar')) parent = $(this).parent();

            if (!parent) {
                var width = $(this).height();
                var height = $(this).height();
                var marginTop = $(this).css('marginTop');
                var marginLeft = $(this).css('marginLeft');
                var marginBottom = $(this).css('marginBottom');
                var marginRight = $(this).css('marginRight');
                $(this).css({
                    marginTop: 0,
                    marginLeft: 0,
                    marginRight: 0,
                    marginBottom: 0
                });
                $(this).wrap('<span class="avatar bbpm-avatar" data-user-id="' + user_id + '"></span>');
                parent = $(this).parent();
                parent.css({
                    marginTop: marginTop,
                    marginLeft: marginLeft,
                    marginRight: marginRight,
                    marginBottom: marginBottom,
                    width: width,
                    height: height
                });
            }

            if (online.indexOf(user_id) > -1) {
                $(parent).addClass('online');
            } else {
                $(parent).removeClass('online');
            }

        });
    }

    /**
     * Refreshes threads on thread list screen
     * @param message
     */
    function updateThreads(message) {
        if( message.fast || message.edit == '1' ) return false;
        $(".bp-messages-wrap .threads-list .empty").remove();
        var thread_id = message['thread_id'];

        //$(".bp-messages-wrap .threads-list .thread[data-id='" + thread_id + "']").remove();

        var html = message['html'];
        html = html.replace('loading="lazy"', '');
        var exist = $(".bp-messages-wrap .threads-list .thread[data-id='" + thread_id + "']");

        if( exist.length > 0 ){
            var message_content = message.content_site;
            exist.each(function(){
                var __thread = $(this);
                var __container = __thread.closest('.threads-list');
                __thread.find('.info p').html( message_content );
                __thread.attr('data-message', message.id);
                __thread.prependTo(__container);
            });
        } else {
            $(html).prependTo(".bp-messages-wrap .threads-list");
        }

        if (typeof BP_Messages.mutedThreads[thread_id] === 'undefined' && BP_Messages.user_id != message.user_id) {
            playSound( message.id );
        }

        onlineInit();
    }

    /**
     * Properly placing new message on thread screen
     * @param message
     */
    function renderMessage(message, selector) {
        var replaceTemp = false;
        var firstMessage = false;
        if(typeof selector === 'undefined') selector = '.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list)';

        if( message.message.trim() == '' ) return false;
        var isEdit = (message['edit'] == '1') ? true : false;

        if( BP_Messages['realtime'] == "1" && ! message.fast ) {
            if( isEdit ){
                replaceTemp = true;
                message.temp_id = message.id;
            } else if ( ! message.temp_id ) {
                //return false;
            } else {
                replaceTemp = true;
            }
        }

        var readableDate = moment(newTime).format('YYYY-MM-DD HH:mm:ss').toString();
        var stack = $(selector + ' .messages-stack:last-child');
        if(stack.length === 0 && $(selector + ' .empty-thread').length > 0) {
            firstMessage = true;
        }
        var same_message = $(selector + ' .messages-list li[data-id="' + message.id + '"]');
        var className = '';
        if( ! ifvisible.now('active') && BP_Messages['realtime'] == "1" ) className += ' unread';
        if(message.user_id == BP_Messages['user_id']) className += ' my';
        if( !! message.fast ) className += ' fast';
        if(message.user_id == BP_Messages['user_id'] && BP_Messages['realtime'] == "1") className += ' sent';
        className = className.trim();
        var findMessage = $(selector + ' .messages-list li[data-id="' + message.temp_id + '"]');
        if(isEdit && findMessage.length > 0) className = findMessage.attr('class');

        var messageHtml = '<li class="' + className + '" data-thread="' + message.thread_id + '" title="' + readableDate + '" data-time="' + message.timestamp + '" data-id="' + message.id + '">' +
            '<span class="favorite"><i class="fas" aria-hidden="true"></i></span>';
        if(message.user_id == BP_Messages['user_id']) messageHtml += '<span class="status" title="' + BP_Messages['strings']['sent'] + '"></span>';
        messageHtml += '<span class="message-content">' + message.message + '</span>' +
            '</li>';

        if(replaceTemp){
            if(findMessage.length > 0){
                if(message.message !== findMessage.find('.message-content').html()){
                    findMessage.replaceWith(messageHtml);
                } else {
                    findMessage.attr('data-id', message.id);
                    findMessage.removeClass('fast');
                }
                return true;
            }
        }

        if (same_message.length === 0 && (stack.length > 0 || firstMessage)) {
            if(firstMessage == true) $(selector + ' .empty-thread').remove();
            var newStack = true;
            var lastTime = new Date( stack.find('.messages-list > li:last-child').attr('data-time') * 1000 );
            var lastTimeString = lastTime.getFullYear() + '-' + lastTime.getMonth() + '-' + lastTime.getDate() + '-' + lastTime.getHours() + '-' + lastTime.getMinutes();
            var newTime = new Date( message.timestamp * 1000 );
            var newTimeString = newTime.getFullYear() + '-' + newTime.getMonth() + '-' + newTime.getDate() + '-' + newTime.getHours() + '-' + newTime.getMinutes();

            if (stack.attr('data-user-id') === message.user_id) newStack = false;
            if (lastTimeString !== newTimeString) newStack = true;

            if ( newStack === false ) {
                stack.find('.messages-list').append(messageHtml);
            } else {
                var stackClass = 'messages-stack';
                if(message.user_id == BP_Messages['user_id']) {
                    stackClass += ' outgoing';
                } else {
                    stackClass += ' incoming';
                }
                var newStackHtml = '<div class="' + stackClass + '" data-user-id="' + message.user_id + '">' +
                    '<div class="pic">' + message.avatar + '</div>' +
                    '<div class="content">' +
                    '<div class="info">' +
                    '<div class="name">' +
                    '<a href="' + message.link + '">' + message.name + '</a>' +
                    '</div>' +
                    '<div class="time" title="' + readableDate + '" data-livestamp="' + message.timestamp + '"></div>' +
                    '</div>' +
                    '<ul class="messages-list">' +
                    '</ul>' +
                    '</div>' +
                    '</div>';

                $(selector + ' .list').append(newStackHtml);

                $(selector + ' .messages-stack:last-child .messages-list').append(messageHtml);
            }

            /**
             * Preventing scroll down if scrolled to top
             * @type {number}

            var maxScroll = parseInt($(selector + " .scroller[data-users] .list").outerHeight()) - parseInt($(selector + " .scroller[data-users]").outerHeight());
            var scroll = parseInt($(selector + " .scroller[data-users]").scrollTop());

            if( (maxScroll - scroll) < 100 ){
                scrollBottom(selector);
            }*/

            $(selector + " .wp-audio-shortcode, " + selector + " .wp-video-shortcode").not(".mejs-container").filter(function(){return!$(this).parent().hasClass(".mejs-mediaelement")}).mediaelementplayer();

            if( typeof $.fn.BPBMmagnificPopup === 'function') {
                $('.bp-messages-wrap .list .messages-stack .content .messages-list li .images').BPBMmagnificPopup({
                    delegate: 'a',
                    type: 'image'
                });
            }

            if ( typeof BP_Messages.mutedThreads[message.thread_id] === 'undefined' && BP_Messages.user_id != message.user_id) {
                playSound( message.id );
            }
        }

        onlineInit();
    }

    /**
     * Show message notification popup
     *
     * @param thread_id
     * @param message
     * @param name
     * @param avatar
     */
    function showMessage(thread_id, message, name, avatar) {
        if (typeof openThreads[thread_id] !== 'undefined') return;
        if( typeof BP_Messages.mutedThreads[thread_id] !== 'undefined' ) return;

        if( $('body').hasClass('bp-messages-mobile') ) return;

        var findSrc = avatar.match(/src\="([^\s]*)"\s/);
        var findSrc2 = avatar.match(/src\='([^\s]*)'\s/);

        if (findSrc != null) {
            avatar = findSrc[1];
        }

        if (findSrc2 != null) {
            avatar = findSrc2[1];
        }

        var popupExist = $('.amaran.thread_' + thread_id);
        if(popupExist.length > 0){
            popupExist.find('.icon > img').attr('src', avatar);
            var msg  = '<b>'+ name +'</b>';
            msg += message;
            popupExist.find('.info').html(msg);
            return true;
        }

        if(BP_Messages['miniMessages'] == '1' && miniMessages === 'messages'){
            // add animation here later
        } else {
            $.amaran({
                'theme': 'user message thread_' + thread_id,
                'content': {
                    img: avatar,
                    user: name,
                    message: message
                },
                'sticky': true,
                'closeOnClick': false,
                'closeButton': true,
                'delay': 10000,
                'thread_id': thread_id,
                'position': 'bottom right',
                onClick: function () {
                    
/* Premium Code Stripped by Freemius */

                        if( isMobile ){
                            var mobilePopup = $('#bp-better-messages-mini-mobile-open');
                            if( mobilePopup.length > 0 ){
                                var originalUrl = BP_Messages['url'];
                                BP_Messages['url'] = BP_Messages['threadUrl'] + this.thread_id;
                                mobilePopup.click();
                                BP_Messages['url'] = originalUrl;
                                $('.amaran.user.message.thread_' + thread_id).remove();
                            } else {
                                location.href = BP_Messages.threadUrl + this.thread_id;
                            }
                        } else {
                            location.href = BP_Messages.threadUrl + this.thread_id;
                        }
                    
/* Premium Code Stripped by Freemius */

                }
            });
        }

        playSound(message.id);
    }

    function hidePossibleBreakingElements(){
        var fixed_elements = $('*:not(.bp-messages-hide-on-mobile)').filter(function () {
            return ( $(this).css('position') === 'fixed' || $(this).css('position') === 'absolute' )
                && ! $(this).hasClass('bp-messages-wrap')
                && $(this).closest('.uppy').length === 0
                && $(this).closest('.bp-messages-wrap').length === 0;
        });

        fixed_elements.addClass('bp-messages-hide-on-mobile');

    }

    function openMobileFullScreen( url ) {
        var _miniMobileContainer = $('#bp-better-messages-mini-mobile-container');
        _miniMobileContainer.addClass('bp-messages-mobile');
        _miniMobileContainer.find('.bp-messages-mobile-tap').remove();

        var windowHeight = window.innerHeight;
        $('html').addClass('bp-messages-mobile').css('overflow', 'hidden');
        $('body').addClass('bp-messages-mobile').css('min-height', windowHeight);
        bpMessagesWrap.addClass('bp-messages-mobile').css('min-height', windowHeight);

        var usedHeight = 0;
        usedHeight = usedHeight + bpMessagesWrap.find('.chat-header').outerHeight();
        usedHeight = usedHeight + bpMessagesWrap.find('.reply').outerHeight();

        var resultHeight = windowHeight - usedHeight;

        $('.scroller').css({
            'max-height': '',
            'height': resultHeight
        });

        hidePossibleBreakingElements();

        _miniMobileContainer.show().html(
            '<div class="loading-messages" style="display: block;line-height: ' + resultHeight + 'px">\n' +
            '<div class="bounce1"></div>\n' +
            '<div class="bounce2"></div>\n' +
            '<div class="bounce3"></div>\n' +
            '</div>'
        );

        $.get(url, function (html) {
            var newWrapper = $(html).find('.bp-messages-wrap:not(.bp-better-messages-mini,.bp-better-messages-list)').html();
            _miniMobileContainer.html(newWrapper).show();

            blockScroll = true;

            reInit();

            $('#bp-better-messages-mini-mobile-open').removeClass('loading');
        });
    }

    
/* Premium Code Stripped by Freemius */


    function updateUnreadCounters(thread_id, unread){
        var unreadText;

        if(unread < 1) {
            unreadText = '';
            $('.threads-list .thread[data-id="' + thread_id + '"], .bp-better-messages-mini .chat[data-thread="' + thread_id + '"]').removeClass('unread');
            $('.bp-messages-wrap .messages-list li[data-thread="' + thread_id + '"]').removeClass('unread');
        } else {
            unreadText = '+' + unread;
            $('.threads-list .thread[data-id="' + thread_id + '"], .bp-better-messages-mini .chat[data-thread="' + thread_id + '"]').addClass('unread');
        }
        $('.threads-list .thread[data-id="' + thread_id + '"] .time .unread-count').text(unreadText);
        $('.bp-better-messages-mini .chat[data-thread="' + thread_id + '"] .unread-count').attr('class', 'unread-count count-' + unread).text(unread);
    }

    /**
     * Playing notification sound!
     */
    function playSound( message_id ) {
        if( typeof message_id === 'string' && message_id.substr(0, 4) !== 'tmp_' ){
            ion.sound.play("notification");
        }
    }

    function createCookie(name, value, days) {
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            var expires = "; expires=" + date.toUTCString();
        }
        else var expires = "";
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }


    function getAutocompleteCookies() {
        var allCookies = document.cookie.split(';'),  // get all cookies and split into an array
            bpCookies = {},
            cookiePrefix = 'bp-',
            i, cookie, delimiter, name, value;

        // loop through cookies
        for (i = 0; i < allCookies.length; i++) {
            cookie = allCookies[i];
            delimiter = cookie.indexOf('=');
            name = jQuery.trim(unescape(cookie.slice(0, delimiter)));
            value = unescape(cookie.slice(delimiter + 1));

            // if BP cookie, store it
            if (name.indexOf(cookiePrefix) === 0) {
                bpCookies[name] = value;
            }
        }

        // returns BP cookies as querystring
        return encodeURIComponent(jQuery.param(bpCookies));
    }

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regexS = "[\\?&]" + name + "=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.search);
        if (results == null)
            return "";
        else
            return decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    function msToTime(duration) {
        var milliseconds = parseInt((duration % 1000) / 100),
            seconds = Math.floor((duration / 1000) % 60),
            minutes = Math.floor((duration / (1000 * 60)) % 60),
            hours = Math.floor((duration / (1000 * 60 * 60)) % 24);

        hours = (hours < 10) ? "0" + hours : hours;
        minutes = (minutes < 10) ? "0" + minutes : minutes;
        seconds = (seconds < 10) ? "0" + seconds : seconds;

        if( hours !== '00' ) {
            return hours + ":" + minutes + ":" + seconds;
        } else {
            return minutes + ":" + seconds;
        }
    }
    
/* Premium Code Stripped by Freemius */


    function calculateTitle(wrap){
        var head       = wrap.find('.chat-header');
        var title      = head.find('> strong');
        var user       = head.find('> .user');

        var iconsWidth = 0;
        head.find('> a:visible:not(.user)').each(function () {
            iconsWidth = iconsWidth + $(this).width();
        });

        var resultWidth = iconsWidth + 10;

        if( title.length > 0 ) {
            title.css( 'width', 'calc(100% - ' + resultWidth + 'px)' )
        }

        if( user.length > 0 ){
            user.css( 'max-width', 'calc(100% - ' + resultWidth + 'px)' );
        }
    }
})(jQuery);

/* Premium Code Stripped by Freemius */


function BPBMurlBase64ToUint8Array( base64String ) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

/**
 * Show notice popup
 */
function BBPMNotice(notice) {
    jQuery.amaran({
        'theme': 'colorful',
        'content': {
            bgcolor: 'black',
            color: '#fff',
            message: notice
        },
        'sticky': false,
        'closeOnClick': true,
        'closeButton': true,
        'delay': 10000,
        'position': 'bottom right'
    });
}

/**
 * Show error popup
 */
function BBPMShowError(error) {
    jQuery.amaran({
        'theme': 'colorful',
        'content': {
            bgcolor: '#c0392b',
            color: '#fff',
            message: error
        },
        'sticky': false,
        'closeOnClick': true,
        'closeButton': true,
        'delay': 10000,
        'position': 'bottom right'
    });
}

function BBPMOpenMiniChat(thread_id, open) {
    jQuery(document).trigger("bp-better-messages-open-mini-chat", [thread_id, open]);
}

function BBPMOpenPrivateThread(user_id) {
    jQuery(document).trigger("bp-better-messages-open-private-thread", [user_id]);
}

