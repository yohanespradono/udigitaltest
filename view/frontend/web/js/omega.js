define([
    'jquery',
    'underscore',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($, _, mageTemplate) {
    'use strict';

    function isEmpty(value) {
        return (value.length === 0) || (value == null) || /^\s+$/.test(value);
    }

    $.widget('mage.omegasearch', {
        options: {
            minSearchLength: 3,
            formSelector: 'form#omega-search-form',
            submitBtn: 'button[type="submit"]',
            minicartSelector: '[data-block="minicart"]',
            typingTimer: null,
            doneTypingInterval: 500,
            template: '<li class="product item" data-add-url="<%- data.add_url %>" data-sku="<%- data.sku %>" data-entity-id="<%- data.entity_id %>" data-qty=1>' +
                    '<div class="wrapper">' +
                        '<div class="image"><img src="<%- data.image %>"></div>' +
                        '<div class="info">' +
                            '<h3 class="title"><%- data.title %></h3>' +
                            '<p class="description"><%- data.description %></p>' +
                            '<div class="rating-price">' +
                                '<div class="rating-summary"><div class="rating-result" id="rating-result_36" title="<%- data.rating %>%"><span style="width: <%- data.rating %>%;"><span><%- data.rating %>%</span></span></div></div>' +
                                '<div class="price"><%- data.price %></div>' +
                            '</div>' +
                        '</div>' + // info
                    '</div>' +
                '</li>'

        },
        _create: function () {
            this.responseList = {
                indexList: null,
                selected: null
            };
            this.searchForm = $(this.options.formSelector);
            this.autoComplete = $(this.options.destinationSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn);
            this.miniCart = $(this.options.minicartSelector);

            _.bindAll(this, '_onPropertyChange', '_onSubmit');

            this.submitBtn.attr('disabled','disabled');

            // bind keypress
            this.element.on('keydown', this._onKeyDown);
            this.element.on('input propertychange', this._onPropertyChange);
            this.autoComplete.on('click', '.item.product' , function(){
                var addUrl = $(this).data('add-url');
                $('#omega-search-form').attr('action', addUrl);
                $('#autocomplete .item').removeClass('selected');
                $(this).addClass('selected');
                if($('#autocomplete .item.selected').length === 1) {
                    $('#omega-search-form button[type=submit]').prop('disabled', false);
                } else {
                    $('#omega-search-form button[type=submit]').prop('disabled', true);
                }
            });
            this.autoComplete.on('click', '.item.word a' , function(e){
                e.preventDefault();
                var word = $(this).html();
                $('#omega-search').val(word).trigger('input');
            });

            //bind submit as add to cart
            this.searchForm.on('submit', $.proxy(function (e) {
                this._onSubmit(e);
            }, this));
        },
        _onPropertyChange: function () {
            $('#omega-search-form').attr('action', '#');
            this.submitBtn.attr('disabled','disabled');
            clearTimeout(this.options.typingTimer);
            this.options.typingTimer = setTimeout($.proxy(this._runAjaxRequest, this), this.options.doneTypingInterval);
        },
        _runAjaxRequest: function(){
            var dropdown = $('<ul></ul>'),
                clonePosition = {
                    position: 'absolute',
                    width: '30%'
                },
                source = this.options.template,
                template = mageTemplate(source),
                value = this.element.val();

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                this.request = $.get(this.options.url, {q: value}, $.proxy(function (data) {
                    if( ! data.items.length) {
                        let suggestions = '<dl class="block">';
                        if(data.suggestions.length) {
                            suggestions += '<dt class="title">Did you mean</dt>';
                            $.each(data.suggestions, function(index, word){
                                suggestions += '<dd class="item word"><a href="#">'+word+'</a></dd>';
                            });
                        }
                        suggestions += '</dl>';
                        this.autoComplete.html('<div class="message notice"></div>');
                        this.autoComplete.find('.message').append('<div>'+$.mage.__('Your search returned no results.') + suggestions + '</div>');
                        return;
                    }

                    $.each(data.items, function (index, element) {
                        element.description = element.description.substring(0, 100);
                        element.index = index;
                        var html = template({
                            data: element
                        });
                        dropdown.append(html);
                    });

                    this.responseList.indexList = this.autoComplete.html(dropdown)
                        .css(clonePosition)
                        .show()
                        .find(this.options.responseFieldElements + ':visible');

                }, this));
            }
        },
        _onSubmit: function (e) {
            e.preventDefault();
            this.miniCart.trigger('contentLoading');
            var formData = new FormData(this.searchForm[0]);
            $.ajax({
                showLoader: true,
                url: this.searchForm.prop('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    console.log(data);
                },
                error: function (request, error) {
                    console.log("Error");
                }
            });
        }
    });
    return $.mage.omegasearch;
});
