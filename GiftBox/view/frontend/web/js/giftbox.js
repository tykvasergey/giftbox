define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'jquery/ui'
], function($, customerData, _) {
    'use strict';

    var chosenItems        = {},
        prodsConfig        = {},
        activeParentId     = 0,
        gettingProdsUrl    = '',
        typesConf          = {},
        restrictionQty     = {},
        restrictionProduct = 3,
        priceMsg           = 0,
        currencySymbol     = '',
        isEdit             = false;


    $.widget('mage.shoppingJsWidget', {
        options: {
            conf: {},
            product_data: {}
        },

        /** @inheritdoc */
        _create: function () {
            this._bindClick();

            var self = this;

            if(this.options.conf.edit_item_info && isEdit == false) {
                isEdit = true;
                activeParentId = this.options.conf.edit_item_info.activeParentId;
                this.setActiveParent(activeParentId);
                this.getProducts(activeParentId);

                chosenItems = self.options.conf.edit_item_info.chosenItems;

            };
        },

        /**
         * @private
         */
        _bindClick: function () {
            var self = this;

            prodsConfig = (self.options.conf.products) ? self.options.conf.products : prodsConfig;
            typesConf = (self.options.conf.types) ? self.options.conf.types : typesConf;
            restrictionQty = (self.options.conf.restriction_qty) ? self.options.conf.restriction_qty : restrictionQty;
            gettingProdsUrl = (self.options.conf.urls && self.options.conf.urls.get_prods) ? self.options.conf.urls.get_prods : gettingProdsUrl;

            $(this.element).click(function() {

                var type_item = $(this).attr('type_item');
                switch (type_item) {
                    case 'parent':

                        let id_parent = $(this).attr('id_item');
                        self.getProducts(id_parent);

                        if(self.validateParentItem(id_parent)) {
                            if(activeParentId != id_parent) {
                                self.setActiveParent(id_parent);
                            }
                        };
                        break;
                    case 'child':

                        if(activeParentId == 0) {
                            return;
                        }

                        let id_child = $(this).attr('id_item');
                        let id_type = $(this).attr('id_type');

                        let element = self.getCurrentChildElement(id_type, id_child);
                        if(element.hasClass('notavailable')) {
                            return;
                        }

                        var availQty = self.getAvailabelProductQty(id_type, id_child),
                        currentProductQty = (chosenItems[id_type] && chosenItems[id_type][id_child]) ? chosenItems[id_type][id_child] : 0;
                        if(availQty > 0) {
                            self.setChildActive(id_type, id_child, null);
                            self.bindClickOnCheckbox();
                        } else {
                            self.setChildAvailabelFromActive(id_type, id_child);
                        }

                        if(self.isLastActive(id_type, id_child)) {
                            self.setRestChildrenToNotAvailabel(id_type);
                        } else {
                            self.setRestChildrenToAvailabel(id_type);
                        }

                        break;

                    case 'tocart':
                        self.submitForm(this);
                        break;
                }
            });
        },

        /**
         *
         */
        bindClickOnCheckbox: function() {
            var self = this;

            if(Object.keys(chosenItems).length == 0) {
                return false;
            }
            self.setCheckBoxPriceMsgVisible();

            $("#addmsg").click(function () {
                let status = self.isCheckBoxPriceMsgActive();
                if(status == true) {
                    $(".msg-text").show();
                } else {
                    $(".msg-text").hide();
                }
            });
        },

        /**
         *
         * @param id_type
         * @returns {number}
         */
        getItemsQty: function(id_type) {
            var qty_by_type = 0;
            if(!chosenItems[id_type]) {
                return qty_by_type;
            }
            Object.keys(chosenItems[id_type]).forEach(function(id_child, i, children) {
                if(chosenItems[id_type][id_child]) {
                    qty_by_type += parseInt(chosenItems[id_type][id_child]);
                }
            });
            return qty_by_type;
        },

        /**
         *
         * @param id_type
         */
        getAvailabelTypeQty: function(id_type) {

            var avail = 0,
                fact = parseInt(Object.keys(prodsConfig[activeParentId][id_type]).length),
                inSettings = parseInt(restrictionQty[activeParentId][id_type]),
                chosen = 0;

            if(chosenItems[id_type]) {
                // chosen = parseInt((chosenItems[id_type]).length);
                chosen = this.getItemsQty(id_type);
            }
            avail = inSettings - chosen;

            return avail;
        },

        /**
         *
         * @param id_type
         * @param id_child
         * @returns {*}
         */
        getAvailabelProductQty: function(id_type, id_child) {
            var avail = 0;
            var availabelTypeQty = this.getAvailabelTypeQty(id_type);

            if(chosenItems[id_type] && chosenItems[id_type][id_child]) {
                var now = chosenItems[id_type][id_child];
                var rest = 0;

                if(availabelTypeQty <= (restrictionProduct - now)) {
                    avail = availabelTypeQty;
                } else {
                    avail = (restrictionProduct - now);
                }
            } else {
                if(availabelTypeQty <= restrictionProduct) {
                    avail = availabelTypeQty;
                } else {
                    avail = restrictionProduct;
                }
            }

            return (avail > 0) ? avail : 0;
        },

        /**
         *
         * @param id_type
         */
        setRestChildrenToNotAvailabel: function(id_type) {
            var self = this;
            Object.keys(prodsConfig).map(function(parent, index) {
                Object.keys(prodsConfig[parent]).map(function(type, index) {
                    Object.keys(prodsConfig[parent][type]).map(function(child, index) {
                        let isChildActive = self.isChildActive(id_type, child);
                        if(parent == activeParentId && type == id_type && isChildActive == false) {
                            self.setChildNotAvailabel(id_type, child);
                        }
                    });
                });
            });
        },

        /**
         *
         * @param id_type
         */
        setRestChildrenToAvailabel: function(id_type) {
            var elements = this.getChidrenElementsByType(id_type);
                elements.removeClass('notavailable');
        },

        /**
         *
         * @param id_type
         * @param id_child
         */
        setChildNotAvailabel: function(id_type, id_child) {
            var element = this.getCurrentChildElement(id_type, id_child);
                element.addClass('notavailable');
        },

        /**
         *
         * @param id_type
         * @param id_child
         * @returns {boolean}
         */
        isLastActive: function(id_type, id_child) {
             var qty = this.getAvailabelTypeQty(id_type);

             if(qty == 0) {
                return true;
             } else {
                return false;
             }
        },

        /**
         * Get active jQuery element of child product
         *
         * @param id_type
         * @param id_child
         * @returns {*|jQuery|(() => (Node | null))|ActiveX.IXMLDOMNode|(Node & ParentNode)|(function(*, *): (number|*))}
         */
        getCurrentChildElement: function (id_type, id_child) {
           return $(".space-wrap-content-item[type_item = 'child'][id_item = " + id_child + "][id_type = " + id_type + "]");
        },

        /**
         * Get all children elements
         *
         * @returns {*|jQuery|(() => (Node | null))|ActiveX.IXMLDOMNode|(Node & ParentNode)|(function(*, *): (number|*))}
         */
        getChildrenElements: function () {
            return  $(".space-wrap-content-item[type_item = 'child']");
        },

        /**
         *
         * @param id_type
         * @returns {never|jQuery}
         */
        getChidrenElementsByType: function(id_type) {
            return $(".space-wrap-content-item[type_item = 'child'][id_type = " + id_type + "]");
        },

        /**
         *
         * @returns {*|jQuery.fn.init|jQuery|HTMLElement}
         */
        getCheckBoxElement: function() {
          return $("#addmsg");
        },

        /**
         * Set active on child
         *
         * @param id_type
         * @param id_child
         */
        setChildActive: function (id_type, id_child, qty) {
            var element = this.getCurrentChildElement(id_type, id_child);
            if(this.addTochosenItems(id_type, id_child, qty)) {
                element.removeClass("available")
                    .removeClass("notavailable")
                    .addClass("active");
            }
        },

        /**
         *
         * @param id_type
         * @param id_child
         */
        setQty: function (id_type, id_child) {
            // remaining by type
            var elementType = $(".qty-child[id_group = " + id_type + "]");
            // var qty = (chosenItems[id_type]).length;
            $(".remaning").show();
            var qty = this.getAvailabelTypeQty(id_type);
            elementType.empty().text(qty);

            // total by product
            var elementProduct = this.getCurrentChildElement(id_type, id_child);
            var qtyProduct = (chosenItems[id_type] && chosenItems[id_type][id_child]) ? parseInt(chosenItems[id_type][id_child]) : 0;
            elementProduct.find(".qty-product").text(qtyProduct);

            if(qtyProduct == 0) {
                elementProduct.find(".qty-product").hide();
            } else {
                elementProduct.find(".qty-product").show();
            }
        },

        /**
         * Clear classes on child product ( available, notavailable, active )
         *
         * @param id_type
         * @param id_child
         */
        clearAllClassOnChild: function (id_type, id_child) {
            var element = this.getCurrentChildElement(id_type, id_child);
            element.removeClass("active")
                   .removeClass('available')
                   .removeClass('notavailable');
        },

        /**
         * Change status of child from active to availabel
         *
         * @param id_type
         * @param id_child
         */
        setChildAvailabelFromActive: function (id_type, id_child) {
            var element = this.getCurrentChildElement(id_type, id_child);
            if(this.deleteFromchosenItems(id_type, id_child)) {
                this.clearAllClassOnChild(id_type, id_child);
                // element.addClass('available');
            }
        },

        /**
         * Add child to chousen elements
         *
         * @param id_type
         * @param id_child
         * @returns {boolean}
         */
        addTochosenItems: function (id_type, id_child, qty) {
            if(!chosenItems[id_type]) {
                chosenItems[id_type] = {}
            }
            if(!chosenItems[id_type][id_child]) {
                chosenItems[id_type][id_child] = 0;
            }

            if(!qty) {
                chosenItems[id_type][id_child]++;
            }

            this.setQty(id_type, id_child);

            return true;
        },

        /**
         * Delete child from chousen elements
         *
         * @param id_type
         * @param id_child
         * @returns {boolean}
         */
        deleteFromchosenItems: function (id_type, id_child) {
            var result = false;
            if(chosenItems[id_type] && chosenItems[id_type][id_child]) {

                delete chosenItems[id_type][id_child];
                if(Object.keys(chosenItems[id_type]).length == 0) {
                    delete chosenItems[id_type];
                }

                this.setQty(id_type, id_child);
                result = true;
            }
            return result;
        },

        /**
         * Check for child is availabel
         *
         * @param id_type
         * @param id_child
         * @returns {boolean}
         */
        isChildAvailabel: function (id_type, id_child) {
            var result = false;
            Object.keys(prodsConfig).map(function(parent, index) {
                Object.keys(prodsConfig[parent]).map(function(type, index) {
                    Object.keys(prodsConfig[parent][type]).map(function(child, index) {
                        if(parent == activeParentId && type == id_type && child == id_child) {
                            result = true;
                        }
                    });
                });
            });

            return result;
        },

        /**
         * Check for child is active
         *
         * @param id_type
         * @param id_child
         * @returns {boolean}
         */
        isChildActive: function (id_type, id_child) {
            if(chosenItems[id_type] && chosenItems[id_type][id_child] && chosenItems[id_type][id_child] > 0) {
                return true;
            } else {
                return false;
            }
        },

        clearAllClassOnParents: function () {
            $("[type_item = 'parent']").removeClass("active");
        },

        /**
         * Set parent to active
         *
         * @param id_parent
         */
        setActiveParent: function (id_parent) {
            $("[type_item = 'parent']").removeClass("active");
            $("[type_item = 'parent'][id_item = " + id_parent + "] ").addClass("active");

            activeParentId = id_parent;

            if(isEdit == false) {
                chosenItems = {};
            }
        },

        /**
         * Clear all children of any class ( available notavailable active )
         */
        clearAllClassOnChildren: function () {
            let elements = this.getChildrenElements();
            elements.removeClass("available")
                    .removeClass("notavailable")
                    .removeClass("active");
        },

        /**
         * Check id_parent is valid
         *
         * @param id_parent
         * @returns {boolean}
         */
        validateParentItem: function (id_parent) {
            if((id_parent in prodsConfig)) {
                return true;
            } else {
                return false;
            }
        },

        /**
         * Handle for hide message error
         */
        hideMessageErrHandle: function() {
            $(document).mouseup(function (e){
                e.preventDefault();
                var modal = $(".msg-err");
                if (!modal.is(e.target)
                    && modal.has(e.target).length === 0) {
                    modal.hide();
                }
            });
        },

        /**
         *
         * @param inputtext
         * @returns {boolean}
         */
        validGiftMessage: function (inputtext){
            var self = this;

            if(inputtext.val().length == 0) {
                return true;
            }

            var alphaExp = /^[а-яА-ЯёЁa-zA-Z0-9\s]{1,500}$/i;
            if(inputtext.val().match(alphaExp)){
                if(self.isCheckBoxPriceMsgActive()) {
                    return true;
                } else {
                    $(".msg-err").empty().append($.mage.__('You should choose "Add message for..."')).show();
                    self.hideMessageErrHandle();

                    return false;
                }
            }else{
                $(".msg-err").empty().append($.mage.__('Message text is not valid. Message must be numeric or/and letters and not more 500 simbols')).show();
                self.hideMessageErrHandle();

                inputtext.focus();
                return false;
            }
        },

        clearForm: function () {
            this.clearAllClassOnChildren();
            this.clearAllClassOnParents();
            $(".msg-err").empty().hide();
            $("form.space-form textarea").val('');
            this.setCheckBoxPriceMsgNotActive();
            $(".qty-product").text(0).hide();

            var chosenItems = {};
            var prodsConfig = {};
            var activeParentId = 0;
        },

        deleteTypes: function() {
            // $(".space-block").remove();
            $(".sections-types").empty();
        },

        getProductHtml: function(id_type, item) {

            var data_mage_init="data-mage-init = '{\"BroSolutions_GiftBox/js/giftbox\":{\"product_data\": {\"type_item\": \"child\",\"id_item\": \"" + item + "\",\"id_type\": \"" + id_type + "\"}}}'";

            var html = '<div class="space-wrap-content-item" id_type = "' + id_type + '" id_item="' + item.product_id +'" type_item="child" ' + data_mage_init + '>' +
                            '<div class="space-descr-wrap">' +
                            '<div class="space-descr">' +
                                '<strong class="descr-text">?</strong>' +
                                '<span class="description-message">' + item.description + '</span>' +
                            '</div>' +
                            '<div class="qty-product"></div>' +
                            '</div>' +
                            '<div class="space-wrap-content-item-data" type_item="child" id_item="' + item.product_id +'" id_type="' + id_type + '">' +
                                '<img width="150" height="150" src="' + item.image + '" alt="' + item.name + '">' +
                                '<div class="product details product-item-details">' +
                                    '<strong class="product name product-item-name">' +
                                        '<span>' + item.name + '</span>' +
                                    '</strong>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

            return html;
        },

        getTypeHtml: function(id_type, productsHtml) {

            var html = '<div class="space-block" id_type="' + id_type + '">' +
                            '<h2>You have <span><span class="qty-child" id_group="' + id_type + '">' +

                                this.getAvailabelTypeQty(id_type) +

                            '</span> ' + typesConf[id_type]['label'] + ' items</span> remaning</h2>' +
                            '<div class="space-wrap">' +
                                '<h2>' + typesConf[id_type]['label'] + ' gifts</h2>' +
                                '<div class="space-wrap-content child">' +
                                    productsHtml +
                                '</div>' +
                            '</div>' +
                        '</div>';

            return html;
        },

        issetTypeInDom: function(id_type) {
            var isssetType = false;
            $(".space-block").each(function(index, element) {
                if(id_type == $(this).attr('id_type')) {
                    isssetType = true;
                }
            });

            return isssetType;
        },

        /**
         *
         * @returns {*|jQuery}
         */
        isCheckBoxPriceMsgActive: function() {
            var element = this.getCheckBoxElement();
            return element.prop('checked');
        },

        /**
         * set checkBox PriceMsg to Active
         */
        setCheckBoxPriceMsgActive: function() {
            var element = this.getCheckBoxElement();
            element.prop('checked', true);
        },

        setCheckBoxPriceMsgNotActive: function() {
            var element = this.getCheckBoxElement();
            element.prop('checked', false);
        },

        /**
         *
         */
        setCheckBoxPriceMsgVisible: function() {
            $(".price-message span").html(currencySymbol + ''  + priceMsg);
            $(".price-message").show();
        },

        /**
         *
         */
        setCheckBoxPriceMsgNoVisible: function() {
            $(".price-message").hide();
        },

        submitForm: function(button) {
            $(".msg-err").empty().hide();
            var self = this,
                formkey = $("form.space-form input[name='form_key']").val(),
                url_submit = $("form button.tocart").attr("url_submit"),
                gift_message_element = $("form.space-form textarea"),
                gift_msg = "",
                isMessagePay = self.isCheckBoxPriceMsgActive();

            if(!self.validGiftMessage(gift_message_element)) {
                return false;
            } else {
                gift_msg = gift_message_element.val();
            }

            if(!url_submit || !activeParentId || Object.keys(chosenItems).length == 0) {
                return false;
            }

            var editParentQuoteItem = '';
            if(self.options.conf.edit_item_info.quote &&
                self.options.conf.edit_item_info.quote.parent_quote_item_id) {
                editParentQuoteItem = self.options.conf.edit_item_info.quote.parent_quote_item_id;
            }

            $.ajax({
                url: url_submit,
                data: { product: activeParentId,
                        qty         : 1,
                        giftMsg     : gift_msg,
                        chosenItems : chosenItems,
                        isMessagePay: isMessagePay,
                        isAjax      : true,
                        isEdit      : isEdit,
                        editQuoteItemId  : editParentQuoteItem,
                        form_key    : formkey
                },
                type: 'post',
                dataType: 'json',
                showLoader: true,

                success: function (response) {

                    if(isEdit == true && location.pathname.indexOf('/giftbox/edit/item/id') >= 0 ) {
                        document.location.href = '/giftbox';
                    }

                    customerData.reload();
                    self.clearForm();
                    $(".msg-text").hide();
                },
                error:function(){
                    console.log('error');
                }
            });
        },

        /**
         *
         * @param parent
         */
        getProducts: function (parent) {
            var self = this;
            var formkey = $("form.space-form input[name='form_key']").val();

            $.ajax({
                url: gettingProdsUrl,
                data: { parentProduct: parent,
                        isAjax: true,
                        form_key: formkey},
                type: 'post',
                dataType: 'json',
                showLoader: true,

                success: function (response) {
                    if(response.products) {

                        Object.keys(response.products).sort();
                        self.deleteTypes();
                        var typesHtml = "";
                        Object.keys(response.products).forEach(function(id_type, i) {
                            var productsHtml = "";
                            Object.keys(response.products[id_type]).forEach(function(id_product, j) {
                                productsHtml += self.getProductHtml(id_type, response.products[id_type][id_product]);
                            });

                            typesHtml += self.getTypeHtml(id_type, productsHtml);
                        });

                        $(".sections-types").html(typesHtml).trigger('contentUpdated');
                    }

                    priceMsg = (response.price_message) ? parseFloat(response.price_message) : 0;
                    currencySymbol = (response.currency_symbol) ? response.currency_symbol : '';

                    if(isEdit == true) {
                        var items = self.options.conf.edit_item_info.chosenItems;
                        Object.keys(items).map(function(type_id, index) {
                            Object.keys(items[type_id]).map(function(product_id, index) {
                                if(items[type_id] && items[type_id][product_id]) {
                                    self.setChildActive(type_id, product_id, parseInt(items[type_id][product_id]));
                                }
                            });
                        });

                        self.bindClickOnCheckbox();
                        self.setCheckBoxPriceMsgVisible();
                        if(self.options.conf.edit_item_info.message) {
                            self.setCheckBoxPriceMsgActive();
                            $(".msg-text textarea").text(self.options.conf.edit_item_info.message);
                            if(self.isCheckBoxPriceMsgActive() == true) {
                                $(".msg-text").show();
                            }
                        }
                    }

                    // customerData.reload();
                    // self.clearForm();
                },
                error:function(){
                    console.log('error');
                }
            });
        }

    });

    return $.mage.shoppingJsWidget;
});
