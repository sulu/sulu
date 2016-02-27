define(function(require) {
    var registerSuite = require('intern!object');
    var assert = require('intern/chai!assert');
    var securityChecker = require('../../../services/security-checker');

    registerSuite({
        name: 'securityChecker',

        'grants permission to objects without permission data': function() {
            assert.strictEqual(true, securityChecker.hasPermission({}, 'view'));
        },

        'grants permission to object with permission data and the required permission set to true': function() {
            assert.strictEqual(
                true,
                securityChecker.hasPermission(
                    {
                        _permissions: {
                            view: true
                        }
                    },
                    'view'
                )
            );
        },

        'revokes permission to object with permission data and the required permission set to false': function() {
            assert.strictEqual(
                false,
                securityChecker.hasPermission(
                    {
                        _permissions: {
                            view: false
                        }
                    },
                    'view'
                )
            );
        },

        'revokes permission to object with permission data and the required permission missing': function() {
            assert.strictEqual(
                false,
                securityChecker.hasPermission(
                    {
                        _permissions: {
                            view: false
                        }
                    },
                    'edit'
                )
            );
        }
    });
});
