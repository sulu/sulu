// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import ExternalLinkTypeOverlay from '../../overlays/ExternalLinkTypeOverlay';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render overlay with an undefined URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with mailto URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href="mailto:test@example.org?subject=Subject&body=Body"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with a URL', () => {
    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    expect(externalLinkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Pass correct props to Dialog', () => {
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={cancelSpy}
            onConfirm={confirmSpy}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={false}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    expect(externalLinkOverlay.find('Dialog').prop('onCancel')).toEqual(cancelSpy);
    expect(externalLinkOverlay.find('Dialog').prop('onConfirm')).toEqual(confirmSpy);
    expect(externalLinkOverlay.find('Dialog').prop('open')).toEqual(false);
});

test('Display given URL with query parameters in href input', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io/contact-us?param=value-1"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            target="_blank"
            title={undefined}
        />
    );

    expect(externalLinkOverlay.find('Url').prop('value')).toEqual('http://www.sulu.io/contact-us?param=value-1');
});

test('Do not call onHrefChange handler if input did not loose focus', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target="_blank"
            title={undefined}
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('http://www.sulu.io');
    expect(urlChangeSpy).not.toBeCalled();
});

test('Fields should change immediately after protocol was changed', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target="_blank"
            title={undefined}
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

test('Call onHrefChange with URL that includes mail subject and mail body for mailto links', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkTypeOverlay
            href="mailto:bla@example.org"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target="_blank"
            title={undefined}
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('mailto:test@example.org');
    externalLinkOverlay.find('Url').prop('onProtocolChange')('mailto:');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org');
    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');

    externalLinkOverlay.update();
    externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"] Input').prop('onChange')('Subject Line');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');
    externalLinkOverlay.find('Field[label="sulu_admin.mail_subject"] Input').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');

    externalLinkOverlay.update();
    externalLinkOverlay.find('Field[label="sulu_admin.mail_body"] TextArea').prop('onChange')('Body Text');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');
    externalLinkOverlay.find('Field[label="sulu_admin.mail_body"] TextArea').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');
});

test('Should not include mail subject and body in URL after switching to another protocol', () => {
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href="mailto:test@example.org?subject=Subject&body=Body%20Text"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            target="_blank"
            title={undefined}
        />
    );

    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toHaveBeenNthCalledWith(1, 'mailto:test@example.org?subject=Subject&body=Body%20Text');

    externalLinkOverlay.find('Url').find('SingleSelect').prop('onChange')('https://');
    expect(urlChangeSpy).toHaveBeenNthCalledWith(2, 'https://test@example.org');
});

test('Reset target to self when a mailto link is entered', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    const externalLinkOverlay = shallow(
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target="_blank"
            title={undefined}
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
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={jest.fn()}
            onTargetChange={targetChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel={undefined}
            target="_blank"
            title={undefined}
        />
    );

    externalLinkOverlay.find('Url').prop('onChange')('http://sulu.io');
    externalLinkOverlay.find('Url').prop('onBlur')();
    expect(urlChangeSpy).toBeCalledWith('http://sulu.io');
    expect(targetChangeSpy).not.toBeCalled();
});

test('Rel value should be transformed correctly', () => {
    const urlChangeSpy = jest.fn();
    const relChangeSpy = jest.fn();

    const externalLinkOverlay = mount(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={urlChangeSpy}
            onRelChange={relChangeSpy}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: [],
                    resourceKey: '',
                }
            }
            rel="noopener  noreferrer "
            target={undefined}
            title={undefined}
        />
    );

    externalLinkOverlay.find('Toggler').prop('onChange')(true);
    externalLinkOverlay.update();
    expect(relChangeSpy).toBeCalledWith('noopener noreferrer nofollow');

    externalLinkOverlay.find('Toggler').prop('onChange')(false);
    externalLinkOverlay.update();
    expect(relChangeSpy).toBeCalledWith('noopener noreferrer');
});
