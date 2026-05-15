// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import log from 'loglevel';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Link from '../../fields/Link';
import LinkContainer from '../../../Link/Link';
import type {LinkValue} from '../../../Link/types';

jest.mock('../../../Link/Link', () => jest.fn(() => null));

function createFormInspector(locale) {
    return ({
        locale,
    }: any);
}

function getLatestLinkContainerProps() {
    const calls = ((LinkContainer: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function renderLink(props: Object = {}) {
    return render(
        <Link
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector(observable.box('en'))}
            {...props}
        />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props correctly to Link component', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const value = {
        anchor: 'anchorTest',
        query: 'queryTest',
        href: '123-asdf-123',
        locale: 'en',
        provider: 'page',
        target: '_blank',
        rel: 'noopener noreferrer',
        title: 'Test',
    };

    const options = {
        enable_attributes: {
            name: 'enable_attributes',
            value: true,
        },
        enable_anchor: {
            name: 'enable_anchor',
            value: true,
        },
        enable_query: {
            name: 'enable_query',
            value: true,
        },
    };

    renderLink({
        disabled: true,
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions: options,
        value,
    });

    expect(getLatestLinkContainerProps()).toEqual({
        disabled: true,
        enableAnchor: true,
        enableQuery: true,
        enableTarget: true,
        enableTitle: true,
        enableRel: true,
        excludedTypes: [],
        locale,
        onChange: changeSpy,
        onFinish: finishSpy,
        types: undefined,
        value: {
            anchor: 'anchorTest',
            query: 'queryTest',
            href: '123-asdf-123',
            locale: 'en',
            provider: 'page',
            rel: 'noopener noreferrer',
            target: '_blank',
            title: 'Test',
        },
    });
});

test('Pass props correctly to Link component with deprecated options', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const value = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale: 'en',
        provider: 'page',
        target: '_blank',
        rel: 'noopener noreferrer',
        title: 'Test',
    };

    const options = {
        enable_target: {
            name: 'enable_target',
            value: true,
        },
        enable_anchor: {
            name: 'enable_anchor',
            value: true,
        },
        enable_title: {
            name: 'enable_title',
            value: true,
        },
    };

    renderLink({
        disabled: true,
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions: options,
        value,
    });

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "enable_target" schema option is deprecated'));
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "enable_title" schema option is deprecated'));

    expect(getLatestLinkContainerProps()).toEqual({
        disabled: true,
        enableAnchor: true,
        enableQuery: undefined,
        enableTarget: true,
        enableTitle: true,
        enableRel: false,
        excludedTypes: [],
        locale,
        onChange: changeSpy,
        onFinish: finishSpy,
        types: undefined,
        value: {
            anchor: 'anchorTest',
            href: '123-asdf-123',
            locale: 'en',
            provider: 'page',
            rel: 'noopener noreferrer',
            target: '_blank',
            title: 'Test',
        },
    });
});

test('Pass props correctly to Link component filtered types', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const value: LinkValue = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale: 'en',
        provider: 'page',
        rel: 'noopener noreferrer',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        enable_attributes: {
            name: 'enable_attributes',
            value: true,
        },
        enable_anchor: {
            name: 'enable_anchor',
            value: true,
        },
        types: {
            name: 'types',
            value: [
                {name: 'external'},
                {name: 'page'},
            ],
        },
    };

    renderLink({
        disabled: true,
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions: options,
        value,
    });

    expect(getLatestLinkContainerProps()).toEqual({
        disabled: true,
        enableAnchor: true,
        enableQuery: undefined,
        enableTarget: true,
        enableTitle: true,
        enableRel: true,
        excludedTypes: [],
        locale,
        onChange: changeSpy,
        onFinish: finishSpy,
        types: ['external', 'page'],
        value: {
            anchor: 'anchorTest',
            href: '123-asdf-123',
            locale: 'en',
            provider: 'page',
            rel: 'noopener noreferrer',
            target: '_blank',
            title: 'Test',
        },
    });
});

test('Pass props correctly to Link component filtered excluded_types', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const value: LinkValue = {
        anchor: 'anchorTest',
        href: '123-asdf-123',
        locale: 'en',
        provider: 'page',
        rel: 'noopener noreferrer',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        enable_attributes: {
            name: 'enable_attributes',
            value: true,
        },
        enable_anchor: {
            name: 'enable_anchor',
            value: true,
        },
        excluded_types: {
            name: 'types',
            value: [
                {name: 'external'},
                {name: 'page'},
            ],
        },
    };

    renderLink({
        disabled: true,
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions: options,
        value,
    });

    expect(getLatestLinkContainerProps()).toEqual({
        disabled: true,
        enableAnchor: true,
        enableQuery: undefined,
        enableTarget: true,
        enableTitle: true,
        enableRel: true,
        excludedTypes: ['external', 'page'],
        locale,
        onChange: changeSpy,
        onFinish: finishSpy,
        types: undefined,
        value: {
            anchor: 'anchorTest',
            href: '123-asdf-123',
            locale: 'en',
            provider: 'page',
            rel: 'noopener noreferrer',
            target: '_blank',
            title: 'Test',
        },
    });
});

test('Pass props correctly to Link component disabled anchor, query, target and rel', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const value: LinkValue = {
        anchor: 'anchorTest',
        query: 'queryTest',
        href: '123-asdf-123',
        locale: 'en',
        provider: 'page',
        rel: 'noopener noreferrer',
        target: '_blank',
        title: 'Test',
    };

    const options = {
        types: {
            name: 'types',
            value: [
                {name: 'external'},
                {name: 'page'},
            ],
        },
    };

    renderLink({
        disabled: true,
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions: options,
        value,
    });

    expect(getLatestLinkContainerProps()).toEqual({
        disabled: true,
        enableAnchor: undefined,
        enableQuery: undefined,
        enableTarget: false,
        enableTitle: false,
        enableRel: false,
        excludedTypes: [],
        locale,
        onChange: changeSpy,
        onFinish: finishSpy,
        types: ['external', 'page'],
        value: {
            anchor: 'anchorTest',
            query: 'queryTest',
            href: '123-asdf-123',
            locale: 'en',
            provider: 'page',
            rel: 'noopener noreferrer',
            target: '_blank',
            title: 'Test',
        },
    });
});
