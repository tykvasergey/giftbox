define([
    'jquery'
], function ($) {
    'use strict';
    var widgetSidebarMixin = {
        _removeItemAfter: function(elem) {
            this._super(elem);
            if(location.pathname.indexOf('/giftbox') >= 0 ) {
                document.location.href = '/giftbox';
            }
        }
    };
    return function (parentWidget) {
        $.widget('mage.sidebar', parentWidget, widgetSidebarMixin);
        return $.mage.sidebar;
    };
});