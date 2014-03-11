/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @module husky/components/preview
 */

/**
 * @class Preview
 * @constructor
 *
 * @param {Object}  [options] Configuration object
 * @param {String}  [options.mainContentElementIdentifier] ID / tagname of the element which will be next to the preview
 * @param {Number}  [options.marginLeft] margin in pixles from the left for the wrapper
 * @param {Object}  [options.iframeSource] configuration object for the source of the iframe
 * @param {String}  [options.iframeSource.url] url used for the iframe
 * @param {String}  [options.iframeSource.webspace] webspace section of the url
 * @param {String}  [options.iframeSource.language] language section of the url
 * @param {String}  [options.id] id of the element
 * @param {Object}  [options.toolbar] options for the toolbar
 * @param {Array}   [options.toolbar.resolutions] options for the toolbar
 * @param {Boolean} [options.toolbar.showLeft] show the left part of the toolbar
 * @param {Boolean} [options.toolbar.showRight] show the right part of the toolbar
 *
 */
define([], function() {

        'use strict';

        /**
         * Default values for options
         */
        var defaults = {
                toolbar: {
                    resolutions: [
                        '1920x1080',
                        '1680x1050',
                        '1440x1050',
                        '1024x768',
                        '800x600',
                        '600x480',
                        '480x320'
                    ],
                    showLeft: true,
                    showRight: true
                },
                mainContentElementIdentifier: '',
                marginLeft: 30,
                iframeSource: {
                    url: '',
                    webspace: '',
                    language: '',
                    id: ''
                }
            },

            eventNamespace = 'husky.preview.',

            /**
             * raised after initialization
             * @event husky.preview.initialized
             */
                INITIALIZED = eventNamespace + 'initialized',

            /**
             * Returns an object with a height and width property from  a string in pixles
             * @param dimension {String} a string with dimensions e.g 1920x1080
             * @return {Object} object with width and height property
             */
                parseHeightAndWidthFromString = function(dimension) {
                var tmp = dimension.split('x');

                if (tmp.length == 2) {
                    return {width: tmp[0], height: tmp[1]}
                } else {
                    this.sandbox.logger.error('Dimension string has invalid format -> 1920x1080');
                    return '';
                }
            },

            /**
             * Concatenates the given strings to an url
             * @param {String} url
             * @param {String} webspace
             * @param {String} language
             * @param {String} id
             * @return {String} url string
             */
                getUrl = function(url, webspace, language, id) {

                // '/admin/content/preview/' + this.options.data.id+'?webspace=' + this.options.webspace + '&language='+ this.options.language

                if (!url ||!id || !webspace || !language) {
                    this.sandbox.logger.error('not all url params for iframe definded!');
                    return '';
                }

                url = url[url.length - 1] === '/' ? url : url + '/';
                url += id + '?';
                url += 'webspace='+webspace;
                url += '&language='+language;

                return url;
            };

        return {

            initialize: function() {

                this.options = this.sandbox.util.extend({}, defaults, this.options);

                // component vars
                this.currentSize = parseHeightAndWidthFromString.call(this, this.options.toolbar.resolutions[0]);
                this.previewWidth = 0;

                // dom elements
                this.$wrapper = null;
                this.$iframe = null;
                this.$toolbar = null;

                this.render();

                this.sandbox.emit(INITIALIZED);
            },

            /**
             * Initializes the rendering process
             */
            render: function() {
                var url = getUrl.call(this, this.options.iframeSource.url, this.options.iframeSource.webspace, this.options.iframeSource.language, this.options.iframeSource.id);

                this.renderWrapper(this.currentSize.height);
                this.renderIframe(this.currentSize.width, this.currentSize.height, url);
                this.renderToolbar();
            },

            /**
             * Renders the div which contains the iframe
             * with the maximum available space
             * @param height {Integer} height of wrapper
             */
            renderWrapper: function(height) {

                var mainWidth, mainMarginLeft, totalWidth, wrapperWidth,
                    $main = this.sandbox.dom.$(this.options.mainContentElementIdentifier)[0];

                if (!$main) {
                    this.sandbox.logger.error('main content element could not be found!')
                }

                // caculate the available space next to the
                mainWidth = this.sandbox.dom.outerWidth($main);
                mainMarginLeft = $main.offsetLeft;
                totalWidth = this.sandbox.dom.width(document);
                this.previewWidth = totalWidth - (mainWidth + mainMarginLeft + this.options.marginLeft);

                this.$wrapper = this.sandbox.dom.$('<div class="previewWrapper" id="previewWrapper" style=""></div>');
                this.sandbox.dom.css(this.$wrapper, 'width', this.previewWidth + 'px');

                this.sandbox.dom.append(this.$el, this.$wrapper);
            },

            /**
             * Renders iframe
             * @param {Number} width of iframe
             * @param {Number} height of iframe
             * @param {String} url for iframe target
             */
            renderIframe: function(width, height, url) {
                this.$iframe = this.sandbox.dom.$('<iframe id="previewIframe" class="previewIframe" src="' + url + '" width="' + width + 'px" height="' + height + 'px"></iframe>');
                this.sandbox.dom.append(this.$wrapper, this.$iframe);
            },

            /**
             * Renders toolbar on top of the iframe
             */
            renderToolbar: function() {
                this.$toolbar = this.sandbox.dom.$([
                    '<div id="previewToolbar" class="previewToolbar">',
                        '<div id="previewToolbarLeft" class="left pointer"><span class="icon-eye-open"></span></div>',
                        '<div id="previewToolbarRight" class="right"></div>',
                    '</div>'
                ].join(''));

                this.sandbox.dom.css(this.$toolbar,'width', this.previewWidth+30+'px');
                this.sandbox.dom.append(this.$el, this.$toolbar);
            }
        };
    }
);
