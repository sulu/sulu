// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {SingleIconSelection} from '../../index';
import SingleItemSelection from '../../../../components/SingleItemSelection';
import SingleListOverlay from '../../../SingleListOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/SingleItemSelection', () => jest.fn(() => null));
jest.mock('../../../SingleListOverlay', () => jest.fn(() => null));

function getLatestSingleItemSelectionProps() {
    const calls = (SingleItemSelection: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestSingleListOverlayProps() {
    const calls = (SingleListOverlay: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass props correctly to SingleIconSelect', () => {
    const formInspector = ({locale: undefined}: any);
    const schemaOptions = observable({
        icon_set: {
            value: 'website',
        },
    });

    render(
        <SingleIconSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(getLatestSingleItemSelectionProps().value).toBe('test');
    expect(getLatestSingleListOverlayProps().options).toEqual({icon_set: 'website'});
});

test('Pass undefined as icon_set to SingleIconSelect', () => {
    const formInspector = ({locale: undefined}: any);
    const schemaOptions = observable({});
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => undefined);

    try {
        expect(() => render(
            <SingleIconSelection
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )).toThrow(/"icon_set"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});
