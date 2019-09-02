// @flow
import React from 'react';
import ruleTypeRegistry from '../../registries/ruleTypeRegistry';

beforeEach(() => {
    ruleTypeRegistry.clear();
});

test('Clear all rule types from RuleTypeRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    ruleTypeRegistry.add('test1', component1);
    expect(Object.keys(ruleTypeRegistry.ruleTypes)).toHaveLength(1);

    ruleTypeRegistry.clear();
    expect(Object.keys(ruleTypeRegistry.ruleTypes)).toHaveLength(0);
});

test('Add rule type to RuleTypeRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    ruleTypeRegistry.add('test1', component1);
    ruleTypeRegistry.add('test2', component2);

    expect(ruleTypeRegistry.get('test1')).toBe(component1);
    expect(ruleTypeRegistry.get('test2')).toBe(component2);
});

test('Add rule type with existing key should throw', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    ruleTypeRegistry.add('test1', component1);
    expect(() => ruleTypeRegistry.add('test1', component2)).toThrow(/test1/);
});

test('Get rule type with existing key', () => {
    const component1 = () => (<h1>Test1</h1>);
    ruleTypeRegistry.add('test1', component1);
    expect(ruleTypeRegistry.get('test1')).toBe(component1);
});

test('Get rule type of not existing key', () => {
    expect(() => ruleTypeRegistry.get('XXX')).toThrow();
});

test('Has a rule type with an existing key', () => {
    ruleTypeRegistry.add('test', () => null);
    expect(ruleTypeRegistry.has('test')).toEqual(true);
});

test('Has a rule type with an not existing key', () => {
    expect(ruleTypeRegistry.has('test')).toEqual(false);
});
