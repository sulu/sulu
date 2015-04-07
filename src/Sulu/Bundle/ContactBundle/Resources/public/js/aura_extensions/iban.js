(function() {

    'use strict';

    define(['vendor/iban-converter'], function(IBAN) {

        return {

            name: 'iban',

            initialize: function(app) {
                var sandbox = app.sandbox;

                sandbox.iban = {};

                /**
                 * Checks if the iban param is a valid iban
                 * @param ibanString
                 * @returns boolean
                 */
                sandbox.iban.isValid = function(ibanString) {
                    return IBAN.isValid(ibanString);
                };

                /**
                 * Formats an iban string
                 * @param ibanString
                 * @param separator (optional)
                 * @returns string
                 */
                sandbox.iban.printFormat = function(ibanString, separator) {
                    return IBAN.printFormat(ibanString, separator);
                };
            }
        };
    });
})();
