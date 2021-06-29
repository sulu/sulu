// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Link from '../../fields/Link';
import type {LinkTypeValue} from '../../../Link/types';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass props correctly to Link component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const value = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale,
        provider: 'page',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        target: {
            name: 'target',
            value: true,
        },
        anchor: {
            name: 'anchor',
            value: true,
        },
    };

    const link = shallow(
        <Link
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value={value}
        />
    );

    expect(link.find('Link').props()).toEqual({
        'disabled': true,
        'enableAnchor': true,
        'enableTarget': true,
        'locale': 'en',
        'onChange': changeSpy,
        'onFinish': finishSpy,
        'types': [],
        'value': {
            'anchor': 'anchorTest',
            'href': '123-asdf-123',
            locale,
            'provider': 'page',
            'target': '_blank',
            'title': 'Test',
        },
    });
});

test('Pass props correctly to Link component filtered types', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const value: LinkTypeValue = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale,
        provider: 'page',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        target: {
            name: 'target',
            value: true,
        },
        anchor: {
            name: 'anchor',
            value: true,
        },
        types: {
            name: 'types',
            value: 'external,page',
        },
    };

    const link = shallow(
        <Link
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value={value}
        />
    );

    expect(link.find('Link').props()).toEqual({
        'disabled': true,
        'enableAnchor': true,
        'enableTarget': true,
        'locale': 'en',
        'onChange': changeSpy,
        'onFinish': finishSpy,
        'types': ['external', 'page'],
        'value': {
            'anchor': 'anchorTest',
            'href': '123-asdf-123',
            locale,
            'provider': 'page',
            'target': '_blank',
            'title': 'Test',
        },
    });
});

test('Pass props correctly to Link component disabled anchor and target', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const value: LinkTypeValue = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale,
        provider: 'page',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        types: {
            name: 'types',
            value: 'external,page',
        },
    };

    const link = shallow(
        <Link
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value={value}
        />
    );

    expect(link.find('Link').props()).toEqual({
        'disabled': true,
        'enableAnchor': false,
        'enableTarget': false,
        'locale': 'en',
        'onChange': changeSpy,
        'onFinish': finishSpy,
        'types': ['external', 'page'],
        'value': {
            'anchor': 'anchorTest',
            'href': '123-asdf-123',
            locale,
            'provider': 'page',
            'target': '_blank',
            'title': 'Test',
        },
    });
});
