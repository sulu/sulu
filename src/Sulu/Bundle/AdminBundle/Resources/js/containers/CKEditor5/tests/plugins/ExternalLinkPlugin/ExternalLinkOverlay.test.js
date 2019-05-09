// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import ExternalLinkOverlay from '../../../plugins/ExternalLinkPlugin/ExternalLinkOverlay';

jest.mock('../../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render overlay with an undefined URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            onUrlChange={jest.fn()}
            open={true}
            target={undefined}
            title={undefined}
            url={undefined}
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with mailto URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            onUrlChange={jest.fn()}
            open={true}
            target={undefined}
            title={undefined}
            url="mailto:test@example.org"
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with a URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            onUrlChange={jest.fn()}
            open={true}
            target={undefined}
            title={undefined}
            url="http://www.sulu.io"
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Pass correct props to Dialog', () => {
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkOverlay
            onCancel={cancelSpy}
            onConfirm={confirmSpy}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            onUrlChange={jest.fn()}
            open={false}
            target={undefined}
            title={undefined}
            url={undefined}
        />
    );

    expect(externalLinkOverlay.find('Dialog').prop('onCancel')).toEqual(cancelSpy);
    expect(externalLinkOverlay.find('Dialog').prop('onConfirm')).toEqual(confirmSpy);
    expect(externalLinkOverlay.find('Dialog').prop('open')).toEqual(false);
});
