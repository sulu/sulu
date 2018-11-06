// @flow
import React from 'react';
import {shallow} from 'enzyme';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const focusPointOverlay = shallow(
        <CropOverlay
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    focusPointOverlay.find('Overlay').prop('onClose')();

    expect(closeSpy).toBeCalledWith();
});
