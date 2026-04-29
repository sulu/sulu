// @flow
import React from 'react';
import {render} from '@testing-library/react';
import AddressCardPreview from '../AddressCardPreview';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render AddressCardPreview with minimal information', () => {
    const {asFragment} = render(
        <AddressCardPreview
            billingAddress={false}
            city={undefined}
            country={undefined}
            deliveryAddress={false}
            number={undefined}
            primaryAddress={false}
            state={undefined}
            street={undefined}
            title={undefined}
            type="Home"
            zip={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render AddressCardPreview with every available information', () => {
    const {asFragment} = render(
        <AddressCardPreview
            billingAddress={true}
            city="Dornbirn"
            country="Austria"
            deliveryAddress={true}
            number="13a"
            primaryAddress={true}
            state="Vorarlberg"
            street="Steinebach"
            title="Headquarter"
            type="Home"
            zip="6850"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});
