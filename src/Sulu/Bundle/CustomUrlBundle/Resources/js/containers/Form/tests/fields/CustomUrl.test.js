// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import CustomUrl from '../../fields/CustomUrl';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function() {
        this.getValueByPath = jest.fn();
    }),
    ResourceFormStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Pass correct props to CustomUrl component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/baseDomain':
                return '*.sulu.io/*';
        }
    });

    const customUrl = shallow(
        <CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={['a', 'b']} />
    );

    expect(customUrl.find('CustomUrl').prop('baseDomain')).toEqual('*.sulu.io/*');
    expect(customUrl.find('CustomUrl').prop('value')).toEqual(['a', 'b']);
});

test('Pass correct props with empty value to CustomUrl component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/baseDomain':
                return 'sulu.io/*';
        }
    });

    const customUrl = shallow(
        <CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={undefined} />
    );

    expect(customUrl.find('CustomUrl').prop('baseDomain')).toEqual('sulu.io/*');
    expect(customUrl.find('CustomUrl').prop('value')).toEqual([]);
});

test('Call onChange when if a value changes', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/baseDomain':
                return 'sulu.io/*';
        }
    });

    const customUrl = shallow(
        <CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} onChange={changeSpy} value={undefined} />
    );

    customUrl.find('CustomUrl').prop('onChange')(['test']);

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onFinish when if the field is blurred', () => {
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/baseDomain':
                return 'sulu.io/*';
        }
    });

    const customUrl = shallow(
        <CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} onFinish={finishSpy} value={undefined} />
    );

    customUrl.find('CustomUrl').prop('onBlur')();

    expect(finishSpy).toBeCalledWith();
});
