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
            url="mailto:test@example.org?subject=Subject&body=Body"
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

test('Do not call onUrlChange handler if input did not loose focus', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            onUrlChange={urlChangeSpy}
            open={true}
            target="_blank"
            title={undefined}
            url={undefined}
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('http://www.sulu.io');
    expect(urlChangeSpy).not.toBeCalled();
});

test('Fields should change immediately after protocol was changed', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = mount(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            onUrlChange={urlChangeSpy}
            open={true}
            target="_blank"
            title={undefined}
            url={undefined}
        />
    );

    expect(externalLinkOverlay.find('Field[label="sulu_admin.link_target"]')).toHaveLength(1);
    expect(externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"]')).toHaveLength(0);
    expect(externalLinkOverlay.find('Field[label="sulu_admin.mail_body"]')).toHaveLength(0);

    externalLinkOverlay.find('Url').prop('onProtocolChange')('mailto:');

    externalLinkOverlay.update();
    expect(externalLinkOverlay.find('Field[label="sulu_admin.link_target"]')).toHaveLength(0);
    expect(externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"]')).toHaveLength(1);
    expect(externalLinkOverlay.find('Field[label="sulu_admin.mail_body"]')).toHaveLength(1);
});

test('Call onUrlChange with all mail values', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            onUrlChange={urlChangeSpy}
            open={true}
            target="_blank"
            title={undefined}
            url="mailto:bla@example.org"
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('mailto:test@example.org');
    externalLinkOverlay.find('Url').prop('onProtocolChange')('mailto:');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org');
    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');

    externalLinkOverlay.update();
    externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"] Input').prop('onChange')('Subject');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject');
    externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"] Input').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject');

    externalLinkOverlay.update();
    externalLinkOverlay.find('Field[label="sulu_admin.mail_body"] TextArea').prop('onChange')('Body');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject&body=Body');
    externalLinkOverlay.find('Field[label="sulu_admin.mail_body"] TextArea').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject&body=Body');
});

test('Reset target to self when a mailto link is entered', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            onUrlChange={urlChangeSpy}
            open={true}
            target="_blank"
            title={undefined}
            url="http://www.sulu.io"
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('mailto:test@example.org');
    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');
});

test('Should not reset target to self when a non-mail URL is entered', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkOverlay
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            onUrlChange={urlChangeSpy}
            open={true}
            target="_blank"
            title={undefined}
            url="http://www.sulu.io"
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('http://sulu.io');
    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('http://sulu.io');
    expect(targetChangeSpy).not.toBeCalled();
});
