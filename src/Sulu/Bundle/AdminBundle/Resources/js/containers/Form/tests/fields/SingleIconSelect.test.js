// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {SingleIconSelection} from '../../index';
import SingleListOverlay from '../../../SingleListOverlay';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../SingleListOverlay', () => jest.fn(() => null));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: {locale: observable.box('en')},
    ...props,
});

test('Pass props correctly to SingleIconSelect', () => {
    const schemaOptions = observable({
        icon_set: {
            value: 'website',
        },
    });

    render(
        <SingleIconSelection
            {...createProps()}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(screen.getByText('test')).toBeInTheDocument();
    expect(getLatestMockProps(SingleListOverlay).options).toEqual({icon_set: 'website'});
});

test('Pass undefined as icon_set to SingleIconSelect', () => {
    const schemaOptions = observable({});

    expect(() => render(
        <SingleIconSelection
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"icon_set"/);
});
