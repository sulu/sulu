/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */
define([
    'type/default'
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {
                id: 'id',
                label: 'value',
                required: false
            },

            /**
             * Validates a european vat number
             * From https://github.com/bcit-ci/CodeIgniter/wiki/European-Vat-Checker
             * @param number
             * @returns boolean
             */
            validateVatNumber = function(number) {

                if (!!number) {
                    number = number.toUpperCase();

                    var countryCode = number.substr(0, 2),
                        regex = '',
                        vat = number.substr(2);

                    switch (countryCode) {
                        case 'AT':
                            regex = /^U[0-9]{8}$/;
                            break;
                        case 'BE':
                        case 'BG':
                            regex = /^0?[0-9]{9,10}$/;
                            break;
                        case 'CH':
                            regex = /^.{6,}$/;
                            break;
                        case 'CZ':
                            regex = /^[0-9]{8,10}$/;
                            break;
                        case 'DE':
                        case 'EE':
                        case 'GR':
                        case 'PT':
                            regex = /^[0-9]{9}$/;
                            break;
                        case 'CY':
                            regex = /^[0-9]{8}[A-Z]$/;
                            break;
                        case 'DK':
                        case 'FI':
                        case 'HU':
                        case 'LU':
                        case 'MT':
                        case 'SI':
                            regex = /^[0-9]{8}$/;
                            break;
                        case 'ES':
                            regex = /^[0-9A-Z][0-9]{7}[0-9A-Z]$/;
                            break;
                        case 'FR':
                            regex = /^[0-9A-Z]{2}[0-9]{9}$/;
                            break;
                        case 'GB':
                            regex = /^([A-Z0-9]{5}|[0-9]{9,12})$/;
                            break;
                        case 'IE':
                            regex = /^[0-9][A-Z0-9]{7,8}$/;
                            break;
                        case 'IT':
                        case 'LV':
                        case 'HR':
                            regex = /^[0-9]{11}$/;
                            break;
                        case 'LT':
                            regex = /^([0-9]{9}|[0-9]{12})$/;
                            break;
                        case 'NL':
                            regex = /^[0-9]{9}B[0-9]{2}$/;
                            break;
                        case 'PL':
                        case 'SK':
                            regex = /^[0-9]{10}$/;
                            break;
                        case 'SE':
                            regex = /^[0-9]{12}$/;
                            break;
                        case 'RO':
                            regex = /^0?[0-9]{2,10}$/;
                            break;
                        default:
                            return true;
                    }

                    return regex.test(vat);
                } else {
                    return false;
                }
            },

            typeInterface = {
                setValue: function(data) {
                    this.$el.data({
                        value: data
                    }).trigger('data-changed');
                },

                getValue: function() {
                    return this.$el.find('input').val();
                },

                needsValidation: function() {
                    return this.getValue() !== '';
                },

                validate: function() {
                    return validateVatNumber(this.getValue());
                }
            };

        return new Default($el, defaults, options, 'vat-input', typeInterface);
    };
});
