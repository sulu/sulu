// @flow
import ruleRegistry from '../../registries/ruleRegistry';

beforeEach(() => {
    ruleRegistry.clear();
});

test('Clear all rules from RuleRegistry', () => {
    ruleRegistry.setRules({
        browser: {
            name: 'Browser',
            type: {
                name: 'select',
                options: {},
            },
        },
    });
    expect(Object.keys(ruleRegistry.rules)).toHaveLength(1);

    ruleRegistry.clear();
    expect(Object.keys(ruleRegistry.rules)).toHaveLength(0);
});

test('Add rules to RuleRegistry', () => {
    const browser = {
        name: 'Browser',
        type: {
            name: 'select',
            options: {},
        },
    };

    const locale = {
        name: 'Locale',
        type: {
            name: 'input',
            options: {},
        },
    };

    ruleRegistry.setRules({
        browser,
        locale,
    });

    expect(ruleRegistry.get('browser')).toBe(browser);
    expect(ruleRegistry.get('locale')).toBe(locale);
});

test('Get rule with existing key', () => {
    const browser = {
        name: 'Browser',
        type: {
            name: 'select',
            options: {},
        },
    };

    ruleRegistry.setRules({
        browser,
    });

    expect(ruleRegistry.get('browser')).toBe(browser);
});

test('Get rule of not existing key', () => {
    expect(() => ruleRegistry.get('XXX')).toThrow();
});
